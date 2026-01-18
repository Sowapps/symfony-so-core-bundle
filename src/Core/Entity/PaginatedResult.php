<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Entity;

use OutOfBoundsException;
use Traversable;

class PaginatedResult {
	
	protected Traversable $results;
	
	protected int $page;
	
	protected int $resultPerPage;
	
	protected int $count;
	
	protected int $pageDelta = 2;
	
	/**
	 * PaginatedResult constructor
	 *
	 * @param Traversable $results
	 * @param int $page
	 * @param int $resultPerPage
	 * @param int $count
	 */
	public function __construct(Traversable $results, int $page, int $resultPerPage, int $count) {
		$this->results = $results;
		$this->page = $page;
		$this->resultPerPage = $resultPerPage ?: $count;
		$this->count = $count;
		
		if( $page > $this->getPageCount() ) {
			throw new OutOfBoundsException('Wrong page number');
		}
	}
	
	/**
	 * @return int
	 */
	public function getPageCount(): int {
		return $this->count ? max(ceil($this->count / $this->resultPerPage), 1) : 1;
	}
	
	/**
	 * @return Traversable
	 */
	public function getResults(): Traversable {
		return $this->results;
	}
	
	/**
	 * @return int
	 */
	public function getPage(): int {
		return $this->page;
	}
	
	/**
	 * @return int
	 */
	public function getResultPerPage(): int {
		return $this->resultPerPage;
	}
	
	/**
	 * @return int
	 */
	public function getCount(): int {
		return $this->count;
	}
	
	/**
	 * @return bool
	 */
	public function hasPreviousPage(): bool {
		return !$this->isFirstPage();
	}
	
	/**
	 * @return int
	 */
	public function isFirstPage(): int {
		return $this->page === 1;
	}
	
	/**
	 * @return bool
	 */
	public function hasNextPage(): bool {
		return !$this->isLastPage();
	}
	
	/**
	 * @return int
	 */
	public function isLastPage(): int {
		return $this->page === $this->getPageCount();
	}
	
	/**
	 * @return int
	 */
	public function getDeltaStart(): int {
		return (int)max($this->page - $this->pageDelta, 1);
	}
	
	/**
	 * @return int
	 */
	public function getDeltaEnd(): int {
		return (int)min($this->page + $this->pageDelta, $this->getPageCount());
	}
	
}
