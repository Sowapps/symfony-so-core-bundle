<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\DBAL;

use Sowapps\SoCore\Core\DBAL\AbstractEnumType;

class EnumFileSourceType extends AbstractEnumType {
	
	const HTTP_UPLOAD = 'http_upload';
	const LOCAL = 'local';
	const VALUES = [self::HTTP_UPLOAD, self::LOCAL];
	
	protected string $name = 'enum_file_source';
	
	protected array $values = self::VALUES;
	
}
