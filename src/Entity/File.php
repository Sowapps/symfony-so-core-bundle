<?php

namespace Sowapps\SoCore\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCore\DBAL\EnumFileStorageType;
use Sowapps\SoCore\Repository\FileRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class File extends AbstractEntity {
	
	#[ORM\Column(type: "string", length: 255)]
	private string $name;
	
	#[ORM\Column(type: "string", length: 5)]
	#[Assert\Length(min: 1, max: 5)]
	private string $extension;
	
	#[ORM\Column(type: "string", length: 100)]
	private string $mimeType;
	
	#[ORM\Column(type: "enum_file_purpose")]
	private ?string $purpose = null;
	
	#[ORM\Column(type: "string", length: 32)]
	private string $privateKey;
	
	#[ORM\Column(type: "enum_file_source")]
	private string $sourceType;
	
	#[ORM\Column(type: "string", length: 255, nullable: true)]
	private ?string $sourceName = null;
	
	#[ORM\Column(type: "string", length: 511, nullable: true)]
	private ?string $sourceUrl = null;
	
	#[ORM\Column(type: "datetime", nullable: true)]
	private ?DateTime $expireDate = null;
	
	#[ORM\Column(type: "integer", nullable: true)]
	private ?int $parentId = null;
	
	#[ORM\Column(type: "smallint")]
	private int $position = 0;

	#[ORM\Column(type: "enum_file_storage")]
	private string $storage = EnumFileStorageType::LOCAL;// On which support ?

	#[ORM\Column(type: "string")]
	private ?string $path = null;// Path on support
	
	#[ORM\Column(type: "string", length: 255, nullable: true)]
	private ?string $outputName = null;
	
	public function getLabel(): string {
		return $this->getName();
	}
	
	public function getName(): ?string {
		return $this->name;
	}
	
	public function setName(string $name): self {
		$this->name = $name;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getLocalName(): string {
		return $this->getId() . '.' . $this->getExtension();
	}
	
	public function getExtension(): ?string {
		return $this->extension;
	}
	
	public function setExtension(string $extension): self {
		$this->extension = $extension;
		
		return $this;
	}
	
	public function getMimeType(): ?string {
		return $this->mimeType;
	}
	
	public function setMimeType(string $mimeType): self {
		$this->mimeType = $mimeType;
		
		return $this;
	}
	
	public function getPurpose(): ?string {
		return $this->purpose;
	}
	
	public function setPurpose(?string $purpose): self {
		$this->purpose = $purpose;
		
		return $this;
	}
	
	public function getPrivateKey(): ?string {
		return $this->privateKey;
	}
	
	public function setPrivateKey(string $privateKey): self {
		$this->privateKey = $privateKey;
		
		return $this;
	}
	
	public function getSourceType(): string {
		return $this->sourceType;
	}
	
	public function setSourceType($sourceType): self {
		$this->sourceType = $sourceType;
		
		return $this;
	}
	
	public function getSourceName(): ?string {
		return $this->sourceName;
	}
	
	public function setSourceName(?string $sourceName): self {
		$this->sourceName = $sourceName;
		
		return $this;
	}
	
	public function getSourceUrl(): ?string {
		return $this->sourceUrl;
	}
	
	public function setSourceUrl(?string $sourceUrl): self {
		$this->sourceUrl = $sourceUrl;
		
		return $this;
	}
	
	public function getExpireDate(): ?DateTime {
		return $this->expireDate;
	}
	
	public function setExpireDate(?DateTime $expireDate): self {
		$this->expireDate = $expireDate;
		
		return $this;
	}
	
	public function getParentId(): ?int {
		return $this->parentId;
	}
	
	public function setParentId(?int $parentId): self {
		$this->parentId = $parentId;
		
		return $this;
	}
	
	public function getPosition(): ?int {
		return $this->position;
	}
	
	public function setPosition(int $position): self {
		$this->position = $position;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getStorage(): string
	{
		return $this->storage;
	}
	
	/**
	 * @param string $storage
	 */
	public function setStorage(string $storage): self
	{
		$this->storage = $storage;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}
	
	/**
	 * @param string $path
	 */
	public function setPath(string $path): self
	{
		$this->path = $path;
		
		return $this;
	}
	
	public function getOutputName(): ?string {
		return $this->outputName;
	}
	
	public function setOutputName(?string $outputName): self {
		$this->outputName = $outputName;
		
		return $this;
	}
	
}
