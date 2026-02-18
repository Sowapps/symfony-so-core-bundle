<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

class LanguageEnabledDto {
	
	public function __construct(
		public bool $enabled,
	) {
	}
	
}
