<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Sowapps\SoCore\Core\Entity\EntitySearch;

abstract class AbstractEntityRepository extends ServiceEntityRepository {
	
	// Avoid orderBy Clause in search query
	public function querySearch(): QueryBuilder {
		return $this->query();
	}
	
	public function query(): QueryBuilder {
		return $this->createQueryBuilder($this->getAlias())
			->orderBy($this->getAlias() . '.id', 'DESC');
	}
	
	public function getAlias(): string {
		throw new RuntimeException('Undefined getAlias() method');
	}
	
	public function applySearchCondition(EntitySearch $search, string $field) {
		// Default is to compare as LIKE '%...%'
		$search->setFieldLike($field);
	}
	
	public function configureQueryForSearch(QueryBuilder $query): void {
		$query->resetDQLPart('orderBy');
	}
	
	public function getSearchFields(): array {
		return [];
	}
	
	public function filterPubliclyAvailable(QueryBuilder $query): void {
	}
	
}
