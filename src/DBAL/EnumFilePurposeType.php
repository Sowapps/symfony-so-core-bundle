<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\DBAL;

use Sowapps\SoCore\Core\DBAL\AbstractEnumType;

class EnumFilePurposeType extends AbstractEnumType {
	
	const USER_AVATAR = 'user_avatar';
	
	const VALUES = [self::USER_AVATAR];
	
	protected string $name = 'enum_file_purpose';
	
	protected array $values = self::VALUES;
	
}
