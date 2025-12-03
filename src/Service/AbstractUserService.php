<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use DateInterval;
use DateTime;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Repository\AbstractUserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * Please, override this service in your application
 */
abstract class AbstractUserService extends AbstractEntityService {
	
	/*
	 * Muse be overridden
	 */
	protected string $userClass;
	
	protected array $roles = [
		AbstractUser::ROLE_USER      => ['user.roleState.user', false],
		AbstractUser::ROLE_ADMIN     => ['user.roleState.admin', AbstractUser::ROLE_ADMIN],
		AbstractUser::ROLE_DEVELOPER => ['user.roleState.developer', AbstractUser::ROLE_DEVELOPER],
	];
	
	protected ?AbstractUser $superUser = null;
	
	/**
	 * UserService constructor
	 *
	 * @param UserPasswordHasherInterface $passwordEncoder
	 * @param AccessDecisionManagerInterface $accessDecisionManager
	 * @param Security $security
	 * @param StringHelper $stringHelper
	 * @param array $config
	 */
	public function __construct(
		protected readonly UserPasswordHasherInterface    $passwordEncoder,
		protected readonly AccessDecisionManagerInterface $accessDecisionManager,
		protected readonly Security                       $security,
		protected readonly StringHelper                   $stringHelper,
		protected readonly array                          $config
	) {
	}
	
	public function getUserClass(): string {
		return $this->config['class'];
	}
	
	/**
	 * @return bool
	 */
	public function isAuthenticated(): bool {
		return !!$this->getCurrent();
	}
	
	/**
	 * @return AbstractUser|null
	 */
	public function getCurrent(): ?AbstractUser {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->security->getUser();
	}
	
	public function isAdmin(?AbstractUser $user): bool {
		return $this->isGranted($user, AbstractUser::ROLE_ADMIN);
	}
	
	public function isGranted(?AbstractUser $user, $attribute, $object = null): bool {
		if( !$user ) {
			return false;
		}
		$token = new UsernamePasswordToken($user, 'none', $user->getRoles());
		
		return ($this->accessDecisionManager->decide($token, [$attribute], $object));
	}
	
	public function isRecoverable(?AbstractUser $user, $recoveryKey): bool {
		return $user &&
			$user->getRecoveryKey() === $recoveryKey &&
			(new DateTime())->sub(DateInterval::createFromDateString(sprintf('%s hours', $this->config['recover']['expire_hours']))) < $user->getRecoverRequestDate();
	}
	
	public function requestRecover(AbstractUser $user) {
		// Store recovery information
		$user->setRecoverRequestDate(new DateTime());
		$user->setRecoveryKey($this->stringHelper->generateKey());
	}
	
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
	 * @param $criteria
	 * @return AbstractUser[]
	 */
	public function getUserByIds($ids): array {
		return $this->getUserBy(['id' => $ids]);
	}
	
	/**
	 * @param $criteria
	 * @return AbstractUser[]
	 */
	public function getUserBy($criteria) {
		return $this->getUserRepository()->findBy($criteria);
	}
	
	/**
	 * @return AbstractUserRepository
	 */
	abstract function getUserRepository(): AbstractUserRepository;
	
	/**
	 * @param string $email
	 * @return AbstractUser|null
	 */
	public function getUserByEmail($email): ?AbstractUser {
		return $this->getUserRepository()->findOneBy(['email' => $email]);
	}
	
	public function isCurrentUserAdmin(): bool {
		return $this->security->isGranted(AbstractUser::ROLE_ADMIN);
	}
	
	public function isCurrentHavingRole($role): bool {
		return $this->security->isGranted($role);
	}
	
	/**
	 * @return array
	 */
	public function getHighestRole(AbstractUser $user): array {
		$highestRole = AbstractUser::ROLE_USER;
		foreach( $this->roles as $role => $roleAttributes ) {
			if( in_array($role, $user->getRoles()) ) {
				$highestRole = $role;
			}
		}
		
		return array_merge([$highestRole], $this->roles[$highestRole]);
	}
	
	public function getRoles() {
		return $this->roles;
	}
	
	public function getRoleRestriction($role) {
		return $this->roles[$role][1];
	}
	
	public function encodePassword(string $plainPassword, AbstractUser $user) {
		return $this->passwordEncoder->hashPassword($user, $plainPassword);
	}
	
	/**
	 * @param AbstractUser|int $user
	 * @param string|null $activationKey
	 * @return AbstractUser
	 */
	public function activate($user, ?string $activationKey = null) {
		/** @var AbstractUser $user */
		if( is_int($user) ) {
			// AbstractUser activation by himself with id + activationKey
			$user = $this->getUser($user);
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
	 * @param int $id
	 * @return AbstractUser|null
	 */
	public function getUser(int $id): ?AbstractUser {
		return $this->getUserRepository()->find($id);
	}
	
	public function startNewActivation(AbstractUser $user) {
		$user->setActivationDate(null);
		$user->setActivationExpireDate(new DateTime(sprintf('+%d hours', $this->config['activation']['expire_hours'])));
		$user->setActivationKey($this->stringHelper->generateKey());
	}
	
}
