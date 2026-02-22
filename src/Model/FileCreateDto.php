<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

use Symfony\Component\Validator\Constraints as Assert;

class FileCreateDto {
	
	public function __construct(
		#[Assert\NotBlank]
		#[Assert\Length(max: 50)]
		public string $purpose,
		
		/**
		 * @var string|null Any DateTime format is supported
		 */
		#[Assert\Length(max: 50)]
		public ?string $expireDate = null,
		
		#[Assert\Length(max: 100)]
		public ?string $relationId = null,
	) {
	}
	
}
