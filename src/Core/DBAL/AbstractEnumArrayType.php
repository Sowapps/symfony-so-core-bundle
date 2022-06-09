<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use InvalidArgumentException;

abstract class AbstractEnumArrayType extends JsonType {
	
	protected string $name;
	
	protected string $enumClass;
	
	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
		$this->verifyType();
		
		return parent::getSQLDeclaration($column, $platform);
	}
	
	public function verifyType() {
		if( empty($this->enumClass) || !class_exists($this->enumClass) || !is_subclass_of($this->enumClass, AbstractEnumType::class, true) ) {
			throw new InvalidArgumentException(sprintf('Invalid enum class for declaration of "%s" type.', $this->getName()));
		}
	}
	
	public function convertToDatabaseValue($value, AbstractPlatform $platform) {
		// Not an array or having unknown values
		if( $value !== null && (!is_array($value) || array_diff($value, $this->getValues())) ) {
			throw new InvalidArgumentException(sprintf('Invalid value of "%s" type.', $this->getName()));
		}
		
		return parent::convertToDatabaseValue($value, $platform);
	}
	
	public function getValues(): array {
		$class = $this->enumClass;
		/** @var AbstractEnumType $enumInstance */
		$enumInstance = new $class();
		
		return $enumInstance->getValues();
	}
	
	public function getName(): string {
		return $this->name;
	}
	
}
