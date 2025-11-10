<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Entity;

use JsonSerializable;

class EntityReference implements JsonSerializable {
	
	/**
	 * SerializableEntity constructor
	 *
	 * @param string $class
	 * @param int $id
	 */
	public function __construct(protected string $class, protected int $id)
    {
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
		return new static($entity::class, $entity->getId());
	}
	
	public function jsonSerialize(): array {
		return [
			'id'    => $this->getId(),
			'class' => $this->getClass(),
		];
	}
	
}
