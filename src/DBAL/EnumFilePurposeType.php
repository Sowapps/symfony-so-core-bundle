<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\DBAL;

use Sowapps\SoCoreBundle\Core\DBAL\AbstractEnumType;

class EnumFilePurposeType extends AbstractEnumType {
	
	const USER_AVATAR = 'user_avatar';
	
	const VALUES = [self::USER_AVATAR];
	
	protected string $name = 'enum_file_purpose';
	
	protected array $values = self::VALUES;
	
}
