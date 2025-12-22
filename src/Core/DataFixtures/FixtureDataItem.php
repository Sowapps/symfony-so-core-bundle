<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\DataFixtures;

use RuntimeException;
use Sowapps\SoCore\Entity\AbstractEntity;

class FixtureDataItem {
	
	/** @var string|null */
	protected ?string $ref;
	
	/** @var array */
	protected array $data;
	
	/** @var AbstractEntity[] */
	protected ?array $entities = null;
	
	/**
	 * FixtureDataItem constructor
	 *
	 * @param string|null $ref
	 * @param array $data
	 */
	public function __construct(?string $ref, array $data) {
		$this->ref = $ref;
		$this->data = $data;
	}
	
	public function setEntities(array $entities): static {
		if( $this->entities !== null ) {
			throw new RuntimeException('Unable to set entities, already set.');
		}
		$this->entities = $entities;
		
		return $this;
	}
	
	public function getRef(): ?string {
		return $this->ref;
	}
	
	public function getData(): array {
		return $this->data;
	}
	
	public function getEntities(): array {
		// Should fail if $this->entities is null here
		return $this->entities;
	}
	
}
