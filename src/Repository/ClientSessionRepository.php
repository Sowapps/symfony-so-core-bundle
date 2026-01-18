<?php

namespace Sowapps\SoCore\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Entity\ClientSession;

/**
 * @extends AbstractRepository<ClientSession>
 */
class ClientSessionRepository extends AbstractRepository {
	
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, ClientSession::class, 'session');
	}
	
}
