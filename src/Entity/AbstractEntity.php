<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Sowapps\SoCore\Core\Entity\EntityReference;
use Sowapps\SoCore\Core\Entity\Persistable;
use Sowapps\SoCore\Core\ProcessOption\FormatOptions;

/**
 * Superclass must be in Sowapps\SoCore\Entity namespace
 */
#[ORM\MappedSuperclass]
class AbstractEntity implements JsonSerializable, Persistable, \Stringable {
	const FORMAT_PUBLIC = 'public';// Show for anybody on internet, not sensible or private data (Default)
	const FORMAT_PRIVATE = 'private';// Show for the creator/owner itself (private data included)
	const FORMAT_ADMIN = 'admin';// Show for any admin (all data included)
	
	const FORMAT_RELATION = 'relation';// Show first level of relations (public info only, and O2O or M2O only)
	
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected ?int $id = null;
	
	#[ORM\Column(type: 'datetimetz_immutable')]
	protected ?DateTimeImmutable $createDate = null;
	
	#[ORM\Column(type: 'string', length: 60)]
	protected ?string $createIp = null;
	
	#[ORM\ManyToOne(targetEntity: AbstractUser::class)]
	protected ?AbstractUser $createUser = null;
	
	public function __construct() {
		$this->createDate = new DateTimeImmutable();
	}
	
	public function isOwnedBy(?AbstractUser $user): false {
		return false;
	}
	
	public function jsonSerialize(): array {
		return $this->asPublicArray();
	}
	
	public function asPublicArray(): array {
		return $this->asArray(new FormatOptions([self::FORMAT_PUBLIC]));
	}
	
	public function asArray(FormatOptions $format): array {
		$array = [
			'id' => $this->getId(),
		];
		if( $format->isRestricted() ) {
			$array += [
				'createDate'   => $this->getCreateDate()->format('c'),
				'createIp'     => $this->getCreateIp(),
				'createUserId' => $this->getCreateUser()?->getId(),
			];
			if( $format->isRelation() ) {
				$array['createUser'] = $this->getCreateUser()?->asPublicArray();
			}
		}
		
		return $array;
	}
	
	public function getLabel(): string {
		return $this->getEntityKey();
	}
	
	public function refresh() {
	
	}
	
	public function isNew(): bool {
		// Warning : If handled by Doctrine, it could be set as not null but not saved in db
		return !$this->id;
	}
	
	public function getEntityLabel(): string {
		return sprintf('%s (#%d)', $this->getLabel(), $this->getId());
	}
	
	public function getEntityType(): string {
		$names = explode('\\', static::class);
		
		return strtolower(array_pop($names));
	}
	
	public function getEntityKey(): string {
		return static::class . '#' . $this->getId();
	}
	
	public function getEntityReference(): EntityReference {
		return new EntityReference(static::class, $this->getId());
	}
	
	/**
	 * @param mixed $other
	 * @return bool
	 */
	public function equals(mixed $other): bool {
		return $other && is_object($other) && static::class === $other::class && !$this->isNew() && !$other->isNew() && $this->getId() === $other->getId();
	}
	
	public function getId(): ?int {
		return $this->id;
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
	
	public function __clone() {
		$this->id = null;
	}
	
	public function __toString(): string {
		return $this->getLabel();
	}
	
}
