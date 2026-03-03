<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Repository\AbstractUserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Sowapps UserService
 */
readonly class UserService {
	public function __construct(
		protected EntityManagerInterface      $entityManager,
		protected UserPasswordHasherInterface $passwordEncoder,
		protected StringService               $stringService,
		#[Autowire('%so_core.user%')]
		protected array                       $configUser
	) {
	}
	
	/**
	 * TODO Use a factory with AbstractUserRepository
	 */
	function getUserRepository(): AbstractUserRepository {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->entityManager->getRepository($this->getUserClass());
	}
	
	public function getUserClass(): string {
		return $this->configUser['class'];
	}
	
	/**
	 * @return array{expire: string, limit: int}
	 */
	public function getUserApiTokenConfig(): array {
		return $this->configUser['apiToken'];
	}
	
	/**
	 * TODO Deprecated ? Document or remove
	 */
	public function isRecoverable(?AbstractUser $user, $recoveryKey): bool {
		return $user &&
			$user->getRecoveryKey() === $recoveryKey &&
			(new DateTime())->sub(DateInterval::createFromDateString($this->configUser['recover']['expire'])) < $user->getRecoverRequestDate();
	}
	
	/**
	 * TODO Deprecated ? Document or remove
	 */
	public function requestRecover(AbstractUser $user) {
		// Store recovery information
		$user->setRecoverRequestDate(new DateTime());
		$user->setRecoveryKey($this->stringService->generateKey());
	}
	
	/**
	 * TODO Deprecated ? Document or remove
	 */
	public function intersectRoles(AbstractUser $user, array $filterRoles): array {
		$roles = [];
		foreach( $user->getRoles() as $userRole ) {
			if( !isset($filterRoles[$userRole]) ) {
				continue;
			}
			$roles[] = $filterRoles[$userRole];
		}
		
		return $roles;
	}
	
	/**
	 * @param AbstractUser|int $user
	 * @param string|null $activationKey
	 * @return AbstractUser
	 * TODO Deprecated ? Document or remove
	 */
	public function activate($user, ?string $activationKey = null) {
		/** @var AbstractUser $user */
		if( is_int($user) ) {
			// AbstractUser activation by himself with id + activationKey
			/** @var AbstractUser|null $user */
			$user = $this->getUserRepository()->find($user);
			if( !$user ) {
				throw new NotFoundHttpException('user.activate.notFound');
			}
			if( !$user->getActivationExpireDate() || $user->getActivationExpireDate() < new DateTime('now') ) {
				throw new NotFoundHttpException('user.activate.expired');
			}
			if( $user->getActivationKey() !== $activationKey ) {
				throw new NotFoundHttpException('user.activate.wrongKey');
			}
		} // Else Admin activation
		if( $user->isActivated() ) {
			throw new NotFoundHttpException('user.activate.alreadyActivated');
		}
		// Activate user account
		$user->setActivationDate(new DateTime());
		$user->setActivationExpireDate(null);
		$user->setActivationKey(null);
		
		// Save into db
		//		$this->update($user);
		
		return $user;
	}
	
	/**
	 * TODO Deprecated ? Document or remove
	 */
	public function startNewActivation(AbstractUser $user) {
		$user->setActivationDate(null);
		$user->setActivationExpireDate((new DateTime())->add(DateInterval::createFromDateString($this->configUser['activation']['expire'])));
		$user->setActivationKey($this->stringService->generateKey());
	}
	
}
