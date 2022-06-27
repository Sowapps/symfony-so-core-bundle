<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\DBAL;

use Sowapps\SoCore\Core\DBAL\AbstractEnumType;

class EnumFileStorageType extends AbstractEnumType {
	
	const LOCAL = 'local';
	const AMAZON_S3 = 'amazon_s3';// Not handled for now
	
	const VALUES = [self::LOCAL];
	
	protected string $name = 'enum_file_storage';
	
	protected array $values = self::VALUES;
	
}
