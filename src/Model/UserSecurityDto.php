<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

class UserSecurityDto {
	
	public function __construct(
		public ?bool  $enabled = null,
		public ?array $roles = null,
	) {
	}
	
}
