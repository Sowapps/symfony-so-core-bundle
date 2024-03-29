<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;


class DisabledAccountException extends AccountStatusException {
	
	public function getMessageKey(): string {
		return 'user.login.disabled';
	}
	
}
