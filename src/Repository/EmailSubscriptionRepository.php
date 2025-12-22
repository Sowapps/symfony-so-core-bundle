<?php

namespace Sowapps\SoCore\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Entity\EmailSubscription;

/**
 * @method EmailSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSubscription[]    findAll()
 * @method EmailSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<EmailSubscription>
 */
class EmailSubscriptionRepository extends AbstractRepository {
	
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, EmailSubscription::class, 'emailSubscription');
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
	
	/**
	 * @param string $email
	 * @param string $purpose
	 * @return EmailSubscription|null
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function findByEmail(string $email, string $purpose): ?EmailSubscription {
		return $this->query()
			->andWhere('emailSubscription.email = :email')
			->andWhere('emailSubscription.purpose = :purpose')
			->setParameter('email', $email)
			->setParameter('purpose', $purpose)
			->getQuery()
			->getOneOrNullResult();
	}
	
}
