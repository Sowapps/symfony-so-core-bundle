<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCore\Core\ProcessOption\FormatOptions;
use Sowapps\SoCore\Repository\LanguageRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'language.name.exists')]
#[UniqueEntity(fields: ['locale'], message: 'language.locale.exists')]
#[ORM\UniqueConstraint(name: 'uniq_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'uniq_locale', columns: ['locale'])]
class Language extends AbstractEntity {
	
	#[ORM\Column(type: 'string', length: 255)]
	private ?string $name = null;
	
	#[ORM\Column(type: 'string', length: 7)]
	private ?string $locale = null;
	
	#[ORM\Column(type: 'string', length: 7)]
	private ?string $primaryCode = null;
	
	#[ORM\Column(type: 'string', length: 7)]
	private ?string $regionCode = null;
	
	#[ORM\Column(type: 'boolean')]
	private ?bool $enabled = false;
	
	public function asArray(FormatOptions $format): array {
		return parent::asArray($format) + [
				'name'        => $this->getName(),
				'locale'      => $this->getLocale(),
				'primaryCode' => $this->getPrimaryCode(),
				'regionCode'  => $this->getRegionCode(),
				'enabled'     => $this->isEnabled(),
				'httpLocale'  => $this->getHttpLocale(),
			];
	}
	
	/**
	 * @return string
	 */
	public function __toString(): string {
		return (string)$this->name;
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
	
	public function getName(): ?string {
		return $this->name;
	}
	
	public function setName(string $name): self {
		$this->name = $name;
		
		return $this;
	}
	
	public function isEnabled(): ?bool {
		return $this->enabled;
	}
	
	public function setEnabled(bool $enabled): self {
		$this->enabled = $enabled;
		
		return $this;
	}
	
}
