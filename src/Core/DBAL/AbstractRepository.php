<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\DBAL;

use Sowapps\SoCore\Core\ProcessOption\CriteriaOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use RuntimeException;
use Sowapps\SoCore\Core\Entity\EntitySearch;

abstract class AbstractRepository extends ServiceEntityRepository {
	
	const FILTER_TYPE_STRING = 'string';
	const FILTER_TYPE_BOOL = 'bool';
	const FILTER_TYPE_ID = 'id';
	const FILTER_TYPE_RELATION = 'relation';
	
	protected string $alias;
	
	public function __construct(ManagerRegistry $registry, string $entityClass, string $alias) {
		parent::__construct($registry, $entityClass);
		
		$this->alias = $alias;
	}
	
	public function queryBy(?CriteriaOptions $criteria): QueryBuilder {
		$query = $this->query();
		// Add filters here
		
		if( $criteria ) {
			$this->filterQuery($query, $criteria);
			
			// Sort by dynamic fields
			$this->sort($criteria->getSorting(), $query);
			
			// Limit results
			$limit = $criteria->getLimit();
			if( $limit ) { // Ignore null or 0
				$query->setMaxResults($limit);
			}
		}
		
		return $query;
	}
	
	protected function filterQuery(QueryBuilder $query, CriteriaOptions $criteria): void {
		$this->filterIdList($criteria, $query);
		$this->filterExcludedIdList($criteria, $query);
	}
	
	/**
	 * Filter by field name with same name and simple equality
	 */
	protected function filterQueryByEquality(QueryBuilder $query, CriteriaOptions $criteria, string $name, string $type = self::FILTER_TYPE_STRING, ?string $property = null): void {
		if( !$criteria->hasFilter($name) ) {
			return;
		}
		$property ??= $name;
		$paramName = 'filter_' . $property;
		if( $type === self::FILTER_TYPE_RELATION ) {
			$where = sprintf('IDENTITY(%s.%s) = :%s', $this->alias, $property, $paramName);
		} else {
			$where = sprintf('%s.%s = :%s', $this->alias, $property, $paramName);
		}
		$query
			->andWhere($where)
			->setParameter($paramName, $this->convertStringValue($criteria->getFilter($name), $type));
	}
	
	/**
	 * Filter by at least one of the terms in list
	 */
	protected function filterQueryByTerms(QueryBuilder $query, CriteriaOptions $criteria, string $name, string $property): void {
		if( !$criteria->hasFilter($name) ) {
			return;
		}
		$terms = $criteria->getFilter($name);
		if( is_string($terms) ) {
			$terms = preg_split('#[ ,]#', $terms);
		}
		$e = $query->expr();
		$orX = $e->orX();
		foreach( $terms as $index => $term ) {
			$paramName = sprintf('filter_%s_%s', $property, $index);
			$orX->add(sprintf('%s.%s LIKE :%s', $this->alias, $property, $paramName));
			$query->setParameter($paramName, '%' . $term . '%');
		}
		
		$query->andWhere($orX);
	}
	
	private function convertStringValue(string $value, string $type) {
		return match ($type) {
			self::FILTER_TYPE_STRING => $value,
			self::FILTER_TYPE_ID, self::FILTER_TYPE_RELATION => intval($value),
			self::FILTER_TYPE_BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN),
			default => throw new InvalidArgumentException(sprintf('Filter type %s unknown', $type)),
		};
	}
	
	public function query(): QueryBuilder {
		return $this->createQueryBuilder($this->alias)
			->orderBy($this->field('id'), 'ASC');
	}
	
	protected function filterIdList(CriteriaOptions $criteria, QueryBuilder $query): void {
		if( $criteria->hasFilter('id') ) {
			$query
				->andWhere($this->field('id') . ' IN (:ids)')
				->setParameter('ids', $criteria->getFilterAsArray('ids'));
		}
	}
	
	protected function filterExcludedIdList(CriteriaOptions $criteria, QueryBuilder $query): void {
		if( $criteria->hasFilter('exclude') ) {
			$query
				->andWhere($this->field('id') . ' NOT IN (:ids)')
				->setParameter('ids', $criteria->getFilterAsArray('exclude'));
		}
	}
	
	protected function sort(array $sorting, QueryBuilder $query): void {
		if( $sorting ) {
			$query->resetDQLPart('orderBy');// Remove current orderBy
			foreach( $sorting as $field => $direction ) {
				$query->addOrderBy($this->field($field), $direction);
			}
		}
	}
	
	/**
	 * Format field name with alias
	 */
	protected function field(string $name): string {
		return $this->alias . '.' . $name;
	}
	
	// Avoid orderBy Clause in search query
	public function querySearch(): QueryBuilder {
		return $this->query();
	}
	
	public function getAlias(): string {
		throw new RuntimeException('Undefined getAlias() method');
	}
	
	public function applySearchCondition(EntitySearch $search, string $field): void {
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
