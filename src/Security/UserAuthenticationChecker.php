<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAuthenticationChecker implements UserCheckerInterface {
	
	public function checkPreAuth(UserInterface $user): void {
		if( !$user instanceof User ) {
			return;
		}
		
		if( !$user->isActivated() ) {
			// the message passed to this exception is meant to be displayed to the user
			throw new CustomUserMessageAccountStatusException('Your user account is not activated.');
		}
		
		if( !$user->isEnabled() ) {
			// the message passed to this exception is meant to be displayed to the user
			throw new CustomUserMessageAccountStatusException('Your user account is disabled.');
		}
	}
	
	public function checkPostAuth(UserInterface $user): void {
	}
	
}
