<?php

namespace Sowapps\SoCore\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Entity\File;
use Sowapps\SoCore\Entity\UserApiToken;

/**
 * @method UserApiToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserApiToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserApiToken[]    findAll()
 * @method UserApiToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserApiTokenRepository extends AbstractRepository {
	
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, UserApiToken::class, 'userToken');
	}
	
}
