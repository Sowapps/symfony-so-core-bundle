<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\Entity\AbstractUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class SecurityService {
	const ROLE_USER = 'ROLE_USER';
	const ROLE_ADMIN = 'ROLE_ADMIN';
	const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
	const ROLE_IMPERSONATE = 'ROLE_IMPERSONATE';
	
	protected readonly array $roles;
	private ?AbstractUser $currentUser = null;
	
	public function __construct(
		private readonly Security                       $security,
		private readonly AccessDecisionManagerInterface $accessDecisionManager,
		private readonly UserPasswordHasherInterface    $passwordHasher
	) {
		$this->roles = [
			// Role => [Translation key, Role restriction]
			// Role restriction: Required role to assign this one
			self::ROLE_USER        => ['user.roleState.user', false],
			self::ROLE_ADMIN       => ['user.roleState.admin', self::ROLE_ADMIN],
			self::ROLE_SUPER_ADMIN => ['user.roleState.superAdmin', self::ROLE_SUPER_ADMIN],
			self::ROLE_IMPERSONATE => ['user.roleState.impersonate', self::ROLE_SUPER_ADMIN],
		];
	}
	
	/**
	 * Encode password using Symfony tools
	 */
	public function encodePassword(string $clearPassword, AbstractUser $user): string {
		return $this->passwordHasher->hashPassword($user, $clearPassword);
	}
	
	/**
	 * TODO Deprecated ? Document or remove
	 */
	public function getHighestRole(AbstractUser $user): array {
		$highestRole = self::ROLE_USER;
		foreach( $this->roles as $role => $roleAttributes ) {
			if( in_array($role, $user->getRoles()) ) {
				$highestRole = $role;
			}
		}
		
		return array_merge([$highestRole], $this->roles[$highestRole]);
	}
	
	/**
	 * TODO Deprecated ? Document or remove
	 */
	public function getRoleRestriction($role) {
		return $this->roles[$role][1];
	}
	
	/**
	 * @return string[]
	 */
	public function getAllRoles(): array {
		return array_keys($this->roles);
	}
	
	/**
	 * Resolve all granted roles for user
	 *
	 * @param AbstractUser $user
	 * @return string[]
	 */
	public function getUserRoles(AbstractUser $user): array {
		return array_filter($this->getAllRoles(), fn(string $role) => $this->isGranted($user, $role));
	}
	
	public function isAuthenticated(): bool {
		return !!$this->getCurrentUser();
	}
	
	public function isAdmin(?AbstractUser $user): bool {
		return $this->isGranted($user, self::ROLE_ADMIN);
	}
	
	public function isGranted(?AbstractUser $user, $attribute, $object = null): bool {
		if( !$user ) {
			return false;
		}
		$token = new UsernamePasswordToken($user, 'main', $user->getRoles());
		
		return $this->accessDecisionManager->decide($token, [$attribute], $object);
	}
	
	public function getCurrentUser(): ?AbstractUser {
		if( $this->currentUser ) {
			return $this->currentUser;
		}
		$user = $this->security->getUser();
		
		return $user instanceof AbstractUser ? $user : null;
	}
	
	public function setCurrentUser(?AbstractUser $currentUser): void {
		$this->currentUser = $currentUser;
	}
	
	public function getRemoteIp(): string {
		return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	}
	
}
