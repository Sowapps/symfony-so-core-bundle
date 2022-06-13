<?php

namespace Sowapps\SoCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCoreBundle\Entity\EmailSubscription;

/**
 * @method EmailSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSubscription[]    findAll()
 * @method EmailSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailSubscriptionRepository extends ServiceEntityRepository {
	
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, EmailSubscription::class);
	}
	
	/**
	 * @param string $purpose
	 * @return QueryBuilder
	 */
	public function queryAllByPurpose(string $purpose): QueryBuilder {
		return $this->query()
			->where('emailSubscription.purpose = :purpose')
			->andWhere('emailSubscription.disabled = false')
			->setParameter('purpose', $purpose);
	}
	
	public function query(): QueryBuilder {
		return $this->createQueryBuilder('emailSubscription')
			->orderBy('emailSubscription.id', 'DESC');
	}
	
	/**
	 * @param string $email
	 * @param string $purpose
	 * @return EmailSubscription|null
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function findByEmail(string $email, string $purpose) {
		return $this->query()
			->andWhere('emailSubscription.email = :email')
			->andWhere('emailSubscription.purpose = :purpose')
			->setParameter('email', $email)
			->setParameter('purpose', $purpose)
			->getQuery()
			->getOneOrNullResult();
	}
	
}
