<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

use Symfony\Component\Validator\Constraints as Assert;

class LanguageCreateDto {
	
	public function __construct(
		#[Assert\NotBlank]
		#[Assert\Length(min: 4, max: 255)]
		public ?string $name = null,
		#[Assert\NotBlank]
		#[Assert\Length(min: 2, max: 7)]
		public ?string $locale = null,
		#[Assert\NotBlank]
		#[Assert\Length(min: 2, max: 7)]
		public ?string $primaryCode = null,
		#[Assert\NotBlank]
		#[Assert\Length(min: 2, max: 7)]
		public ?string $regionCode = null,
	) {
	}
	
}
