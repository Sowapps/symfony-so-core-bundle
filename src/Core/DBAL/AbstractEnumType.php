<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

abstract class AbstractEnumType extends Type {
	
	protected string $name;
	
	protected array $values = [];
	
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string {
		$values = array_map(function ($val) {
			return "'" . $val . "'";
		}, $this->values);
		
		return 'ENUM(' . implode(', ', $values) . ')';
	}
	
	public function getValues(): array {
		return $this->values;
	}
	
	public function convertToPHPValue($value, AbstractPlatform $platform) {
		return $value;
	}
	
	public function convertToDatabaseValue($value, AbstractPlatform $platform) {
		if( $value !== null && !in_array($value, $this->values) ) {
			throw new InvalidArgumentException("Invalid '" . $this->name . "' value.");
		}
		
		return $value;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function requiresSQLCommentHint(AbstractPlatform $platform): bool {
		return true;
	}
	
}
