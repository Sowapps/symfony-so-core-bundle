<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCoreBundle\Repository\LanguageRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[UniqueEntity(fields: ['locale'], message: 'language.locale.exists')]
#[UniqueEntity(fields: ['key'], message: 'language.key.exists')]
class Language extends AbstractEntity {
	
	/**
	 * @ORM\Column(type="string", length=7)
	 */
	private ?string $locale = null;
	
	/**
	 * @ORM\Column(type="string", length=3)
	 */
	private ?string $primaryCode = null;
	
	/**
	 * @ORM\Column(type="string", length=3)
	 */
	private ?string $regionCode = null;
	
	/**
	 * @ORM\Column(name="_key", type="string", length=255)
	 */
	private ?string $key = null;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	private ?bool $enabled = false;
	
	/**
	 * @return string
	 */
	public function __toString() {
		return $this->key;
	}
	
	public function getHttpLocale(): ?string {
		return str_replace('_', '-', $this->locale);
	}
	
	public function getLocale(): ?string {
		return $this->locale;
	}
	
	public function setLocale(string $locale): self {
		$this->locale = $locale;
		
		return $this;
	}
	
	public function getPrimaryCode(): ?string {
		return $this->primaryCode;
	}
	
	public function setPrimaryCode(string $primaryCode): self {
		$this->primaryCode = $primaryCode;
		
		return $this;
	}
	
	public function getRegionCode(): ?string {
		return $this->regionCode;
	}
	
	public function setRegionCode(string $regionCode): self {
		$this->regionCode = $regionCode;
		
		return $this;
	}
	
	public function getKey(): ?string {
		return $this->key;
	}
	
	public function setKey(string $key): self {
		$this->key = $key;
		
		return $this;
	}
	
	public function isEnabled(): ?bool {
		return $this->enabled;
	}
	
	public function setEnabled(bool $enabled): self {
		$this->enabled = $enabled;
		
		return $this;
	}
	
	public function jsonSerialize(): array {
		return [
			'id'          => $this->getId(),
			'key'         => $this->getKey(),
			'primaryCode' => $this->getPrimaryCode(),
			'regionCode'  => $this->getRegionCode(),
			'locale'      => $this->getLocale(),
		];
	}
	
}
