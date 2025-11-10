<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Entity;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Sowapps\SoCore\Core\Repository\AbstractEntityRepository;

class EntitySearch {
	
	protected string $alias;
	
	protected ?QueryBuilder $query;
	
	protected bool $publicOnly;
	
	protected array $terms;
	
	protected ?int $processingTerm;
	
	protected ?array $fields;
	
	protected ?Orx $searchConditions;
	
	/**
	 * EntitySearch constructor
	 *
	 * @param AbstractEntityRepository $repository
	 * @param string|null $alias
	 */
	public function __construct(protected AbstractEntityRepository $repository, ?string $alias = null) {
		$this->alias = $alias ?? $this->repository->getAlias();
		$this->query = null;
		$this->terms = [];
		$this->publicOnly = true;
		$this->processingTerm = null;
		$this->fields = null;
		$this->searchConditions = null;
	}
	
	public function prepare(): EntitySearch {
		$this->fields = $this->repository->getSearchFields();
		$this->query ??= $this->repository->querySearch();
		$this->query->distinct();
		$this->repository->configureQueryForSearch($this->query);
		if( $this->publicOnly ) {
			$this->repository->filterPubliclyAvailable($this->query);
		}
		
		return $this;
	}
	
	protected function getFieldPath(string $field): string {
		return strpos($field, '.') ? $field : $this->alias . '.' . $field;
	}
	
	protected function getTermParameter(?string $pattern = null): string {
		$term = &$this->terms[$this->processingTerm];
		$value = $pattern ? sprintf($pattern, $term[0]) : $term[0];
		$key = 'TERM_' . crc32((string) $value) . '_' . count($term[1]);
		// Doctrine is not allowing reuse of parameter name
		$term[1][] = $key;
		$this->query->setParameter($key, $value);
		
		return $key;
	}
	
	public function setFieldLike(string $field): static {
		$this->searchConditions->add(sprintf('%s LIKE :%s', $this->getFieldPath($field), $this->getTermParameter('%%%s%%')));
		
		return $this;
	}
	
	public function setFieldSearch(string $field): static {
		$this->searchConditions->add(sprintf('MATCH(%s) AGAINST(:%s BOOLEAN) > 1', $this->getFieldPath($field), $this->getTermParameter()));
		
		return $this;
	}
	
	public function applyTerms(array $terms): static {
		if( !$this->query ) {
			$this->prepare();
		}
		$this->searchConditions = $this->query->expr()->orX();
		foreach( $terms as $term ) {
			$this->processingTerm = count($this->terms);// Compat with multiple applyTerms()
			$this->terms[] = [$term, []];
			foreach( $this->fields as $field ) {
				$this->repository->applySearchCondition($this, $field);
			}
		}
		// Is there something to apply ?
		if( $this->searchConditions->count() ) {
			$this->query->andWhere($this->searchConditions);
		}
		// Clear
		$this->processingTerm = null;
		$this->searchConditions = null;
		
		return $this;
	}
	
	/**
	 * @return AbstractEntityRepository
	 */
	public function getRepository(): AbstractEntityRepository {
		return $this->repository;
	}
	
	/**
	 * @return string
	 */
	public function getAlias(): string {
		return $this->alias;
	}
	
	/**
	 * @param string $alias
	 * @return EntitySearch
	 */
	public function setAlias(string $alias): EntitySearch {
		$this->alias = $alias;
		
		return $this;
	}
	
	/**
	 * @return QueryBuilder
	 */
	public function getQuery(): QueryBuilder {
		return $this->query;
	}
	
	/**
	 * @param QueryBuilder $query
	 * @return EntitySearch
	 */
	public function setQuery(QueryBuilder $query): EntitySearch {
		$this->query = $query;
		
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function isPublicOnly(): bool {
		return $this->publicOnly;
	}
	
	/**
	 * @param bool $publicOnly
	 * @return EntitySearch
	 */
	public function setPublicOnly(bool $publicOnly): EntitySearch {
		$this->publicOnly = $publicOnly;
		
		return $this;
	}
	
	/**
	 * @return array|null
	 */
	public function getFields(): ?array {
		return $this->fields;
	}
	
	/**
	 * @return array
	 */
	public function getTerms(): array {
		return $this->terms;
	}
	
	/**
	 * @return int|null
	 */
	public function getProcessingTermIndex(): ?int {
		return $this->processingTerm;
	}
	
	/**
	 * @return string|null
	 */
	public function getProcessingTerm(): ?string {
		return $this->processingTerm !== null ? $this->terms[$this->processingTerm][0] : null;
	}
	
}
