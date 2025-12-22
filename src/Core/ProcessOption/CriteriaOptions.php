<?php

namespace Sowapps\SoCore\Core\ProcessOption;

class CriteriaOptions {

    private array $filters;

    private array $sorting;

    private ?int $limit;

    public function __construct(array $criteria) {
        $this->sorting = $this->parseSorting($criteria['sort'] ?? null);
        $this->limit = $criteria['limit'] ?? null;
        if( isset($criteria['sort']) ) {
            unset($criteria['sort']);
        }
        if( isset($criteria['limit']) ) {
            unset($criteria['limit']);
        }
        $this->filters = $criteria['filters'] ?? $criteria;// Sub array filters or criteria itself
    }

    public function restrictFilters(string ...$filters): static {
        $this->filters = array_intersect_key($this->filters, array_flip($filters));

        return $this;
    }

    /**
     * Parse sorting to array of array from :
     * - String list separated by coma
     * - Array of string (can be combined with following)
     * - Array of array (same as output)
     * - Null (empty array)
     */
    protected function parseSorting(array|string|null $sortList): array {
        if( !$sortList ) {
            return [];
        }
        if( is_string($sortList) ) {
            $sortList = explode(',', $sortList);
        }
        $sorts = [];
        foreach( $sortList as $sort ) {
            if( is_array($sort) ) {
                continue;
            }
            $sort = trim($sort);
            $direction = 'ASC';
            if( str_starts_with($sort, '-') ) {
                $direction = 'DESC';
                $field = substr($sort, 1);
            } else {
                $field = $sort;
            }
            $sorts[$field] = $direction;
        }

        return $sorts;
    }

    public function setDefaultLimit(int $limit): void {
        $this->limit ??= $limit;
    }

    public function setLimit(?int $limit): void {
        $this->limit = $limit;
    }

    public function setSorting(array $sorting): void {
        $this->sorting = $sorting;
    }

    public function getSorting(): array {
        return $this->sorting;
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function getFilters(): array {
        return $this->filters;
    }

    public function hasFilter(string $key): bool {
        return isset($this->filters[$key]);
    }

    public function getFilter(string $key) {
        return $this->filters[$key] ?? null;
    }

    public function getFilterAsArray(string $key): array {
        return (array)$this->getFilter($key) ?? [];
    }

}
