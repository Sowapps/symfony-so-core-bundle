<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\DBAL;

use Sowapps\SoCoreBundle\Core\DBAL\AbstractEnumType;

class EnumEmailPurposeType extends AbstractEnumType {
	
	const CONTACT = 'contact';
	const USER_REGISTRATION = 'user_registration';
	const USER_RECOVER = 'user_recover';
	
	const VALUES = [
		self::USER_REGISTRATION, self::USER_RECOVER, self::CONTACT,
	];
	
	protected string $name = 'enum_email_purpose';
	
	protected array $values = self::VALUES;
	
}
