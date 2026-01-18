<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

class UserPasswordDto {
	
	public function __construct(
		public string $password,
	) {
	}
	
}
