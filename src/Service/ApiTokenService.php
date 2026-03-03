<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use DateTime;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Entity\UserApiToken;
use Sowapps\SoCore\Repository\UserApiTokenRepository;

/**
 * Service to manage user api tokens
 */
readonly class ApiTokenService {
	public function __construct(
		private EntityService          $entityService,
		private UserService            $userService,
		private UserApiTokenRepository $userApiTokenRepository,
	) {
	}
	
	public function useToken(UserApiToken $token): void {
		$token->setLastUseDate(new DateTime());
		if( $token->getExpireDate() ) {
			// The used token is expiring, we delay the expiration
			$expire = $this->userService->getUserApiTokenConfig()['expire'];
			$token->setExpireDate(new DateTime($expire));
		}
		
		$this->entityService->update($token)->flush();
	}
	
	public function getToken(string $apiToken): ?UserApiToken {
		$tokenHash = $this->hashToken($apiToken);
		$token = $this->userApiTokenRepository->findOneBy(['tokenHash' => $tokenHash]);
		// Check token
		if( $token && $this->isTokenExpired($token) ) {
			// Expired token is immediately removed
			$this->entityService->remove($token)->flush();
			$token = null;
		}
		
		return $token;
	}
	
	/**
	 * @param AbstractUser $user
	 * @param string $ip
	 * @param DateTime|null $expireDate
	 * @return array{token: string, expiresAt: ?DateTime}
	 */
	public function createToken(AbstractUser $user, string $ip, ?DateTime $expireDate = null): array {
		$rawToken = $this->generateToken();
		
		// Add new user api token
		$entity = new UserApiToken();
		$entity->setUser($user);
		$entity->setTokenHash($this->hashToken($rawToken));// Hashed 256-bit token
		$entity->setExpireDate($expireDate);
		$entity->setIp($ip);
		
		$this->entityService->create($entity)->flush();
		
		// Clean expired and out of limits
		$this->cleanUserTokens($user);
		
		return ['token' => $rawToken, 'expireDate' => $expireDate];
	}
	
	/**
	 * Remove expired and older token out of limits
	 */
	public function cleanUserTokens(AbstractUser $user): array {
		$config = $this->userService->getUserApiTokenConfig();
		// Tokens from the most recent to the oldest
		$userTokens = $this->userApiTokenRepository->findByUser($user);
		$removed = [];
		
		// Remove expired tokens
		foreach( $userTokens as $index => $token ) {
			if( $this->isTokenExpired($token) ) {
				$removed[] = $token;
				$this->entityService->remove($token)->flush();
				unset($userTokens[$index]);
			}
		}
		
		// Remove older ones out of limits
		$toDelete = max(count($userTokens) - $config['limit'], 0);
		for( $i = 0; $i < $toDelete; $i++ ) {
			$token = array_pop($userTokens);
			if( $token ) {
				$removed[] = $token;
				$this->entityService->remove($token)->flush();
			} else {
				// There is no more token, this should be not possible, but it's just in case
				break;
			}
		}
		
		
		return $removed;
	}
	
	public function isTokenExpired(UserApiToken $token, DateTime $at = new DateTime()): bool {
		$date = $token->getExpireDate();
		return $date && $at >= $date;
	}
	
	/**
	 * Return a 32-bit token
	 */
	public function generateToken(): string {
		return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
	}
	
	public function hashToken(string $rawToken): string {
		return hash('sha256', $rawToken . $this->getSalt());
	}
	
	public function getSalt(): string {
		return '$4lT';
	}
	
}
