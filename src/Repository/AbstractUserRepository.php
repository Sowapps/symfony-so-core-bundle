<?php

namespace Sowapps\SoCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Sowapps\SoCoreBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

abstract class AbstractUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface {
	
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
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
		}
		
		$user->setPassword($newHashedPassword);
		
		$this->add($user, true);
	}
	
	//    /**
	//     * @return User[] Returns an array of User objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('u')
	//            ->andWhere('u.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('u.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }
	
	//    public function findOneBySomeField($value): ?User
	//    {
	//        return $this->createQueryBuilder('u')
	//            ->andWhere('u.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
