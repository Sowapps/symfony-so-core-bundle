<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\DBAL;

use Sowapps\SoCore\Core\DBAL\AbstractEnumType;

/**
 * @deprecated Use FilePurpose
 * @see FilePurpose
 */
class EnumFileStorageType extends AbstractEnumType {
	
	const LOCAL = 'local';
	
	const VALUES = [self::LOCAL];
	
	protected string $name = 'enum_file_storage';
	
	protected array $values = self::VALUES;
	
}
