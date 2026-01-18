<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

class UserUpdateDto {
	
	public function __construct(
		public ?string $name = null,
		public ?string $email = null,
	) {
	}
	
}
