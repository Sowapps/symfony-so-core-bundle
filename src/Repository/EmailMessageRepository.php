<?php

namespace Sowapps\SoCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCoreBundle\Entity\EmailMessage;

/**
 * @method EmailMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailMessage[]    findAll()
 * @method EmailMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailMessageRepository extends ServiceEntityRepository {
	
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, EmailMessage::class);
	}
	
	public function query(): QueryBuilder {
		return $this->createQueryBuilder('emailMessage')
			->orderBy('emailMessage.id', 'DESC');
	}
	
}
