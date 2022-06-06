<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Sowapps\SoCoreBundle\Core\Entity\Persistable;

/**
 * Superclass must be in Sowapps\SoCoreBundle\Entity namespace
 */
#[ORM\MappedSuperclass]
class AbstractEntity implements JsonSerializable, Persistable {
	
	const MODEL_MINIMALIST = 'min';
	const MODEL_PUBLIC = 'public';
	
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected ?int $id = null;
	
	#[ORM\Column(type: 'datetimetz_immutable')]
	protected ?DateTimeImmutable $createDate = null;
	
	#[ORM\Column(type: 'string', length: 60)]
	protected ?string $createIp = null;
	
	#[ORM\ManyToOne(targetEntity: 'Sowapps\SoCoreBundle\Entity\AbstractUser')]
	protected ?AbstractUser $createUser = null;
	
	public function __construct() {
		// Do nothing
	}
	
	public function jsonSerialize(): array {
		return $this->toArray(self::MODEL_PUBLIC);
	}
	
	public function toMinimalistArray(): array {
		return $this->toArray(self::MODEL_MINIMALIST);
	}
	
	public function toArray(string $model): array {
		return [
			// MODEL_MINIMALIST
			'entity_type' => $this->getEntityType(),
			'id'          => $this->getId(),
			'label'       => $this->getLabel(),
		];
	}
	
	public function __clone() {
		$this->id = null;
	}
	
	public function __toString() {
		return $this->getLabel();
	}
	
	public function getEntityLabel(): string {
		return sprintf('%s (#%d)', $this->getLabel(), $this->getId());
	}
	
	public function getLabel(): string {
		return $this->getEntityKey();
	}
	
	public function getEntityType(): string {
		$names = explode('\\', get_called_class());
		
		return strtolower(array_pop($names));
	}
	
	public function getEntityKey(): string {
		return get_called_class() . '#' . $this->getId();
	}
	
	public function getId(): ?int {
		return $this->id;
	}
	
	/**
	 * @param mixed $other
	 * @return bool
	 */
	public function equals($other): bool {
		return $other && is_object($other) && get_class($this) === get_class($other) && !$this->isNew() && !$other->isNew() && $this->getId() === $other->getId();
	}
	
	/**
	 * Is this instance new ? Or is it saved to database ?
	 * It tests the id to know.
	 *
	 * @return bool
	 */
	public function isNew(): bool {
		return !isset($this->id) || !$this->id;
	}
	
	/**
	 * @return DateTimeImmutable|null
	 */
	public function getCreateDate(): ?DateTimeImmutable {
		return $this->createDate;
	}
	
	/**
	 * @param DateTimeImmutable $createDate
	 * @return AbstractEntity
	 */
	public function setCreateDate(DateTimeImmutable $createDate): self {
		$this->createDate = $createDate;
		
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getCreateIp(): ?string {
		return $this->createIp ?? null;
	}
	
	/**
	 * @param string $createIp
	 * @return AbstractEntity
	 */
	public function setCreateIp(string $createIp): self {
		$this->createIp = $createIp;
		
		return $this;
	}
	
	public function getCreateUser(): ?AbstractUser {
		return $this->createUser;
	}
	
	public function setCreateUser(AbstractUser $createUser): self {
		$this->createUser = $createUser;
		
		return $this;
	}
	
}
