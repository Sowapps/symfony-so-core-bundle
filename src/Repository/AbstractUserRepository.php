<?php

namespace Sowapps\SoCore\Repository;

use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Entity\AbstractUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

abstract class AbstractUserRepository extends AbstractRepository implements PasswordUpgraderInterface {

	public function add(AbstractUser $entity, bool $flush = false): void {
		$this->getEntityManager()->persist($entity);

		if( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(AbstractUser $entity, bool $flush = false): void {
		$this->getEntityManager()->remove($entity);

		if( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * Used to upgrade (rehash) the user's password automatically over time.
	 */
	public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
		if( !$user instanceof AbstractUser ) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
		}

		$user->setPassword($newHashedPassword);

		$this->add($user, true);
	}
	
}
