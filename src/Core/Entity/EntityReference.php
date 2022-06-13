<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Entity;

use JsonSerializable;

class EntityReference implements JsonSerializable {
	
	protected string $class;
	
	protected int $id;
	
	/**
	 * SerializableEntity constructor
	 *
	 * @param string $class
	 * @param int $id
	 */
	public function __construct(string $class, int $id) {
		$this->class = $class;
		$this->id = $id;
	}
	
	/**
	 * @return string
	 */
	public function getClass(): string {
		return $this->class;
	}
	
	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}
	
	public static function fromEntity(Persistable $entity): self {
		return new static(get_class($entity), $entity->getId());
	}
	
	public function jsonSerialize(): array {
		return [
			'id'    => $this->getId(),
			'class' => $this->getClass(),
		];
	}
	
}
