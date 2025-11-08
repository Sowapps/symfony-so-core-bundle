<?php

namespace Sowapps\SoCore\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCore\Core\Repository\AbstractEntityRepository;
use Sowapps\SoCore\Entity\File;

/**
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends AbstractEntityRepository {
	
	public function __construct(ManagerRegistry $registry)
    {
    }
	
	public function getAlias(): string {
		return 'file';
	}
	
}
