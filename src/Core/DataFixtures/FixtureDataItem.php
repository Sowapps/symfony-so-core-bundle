<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\DataFixtures;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Sowapps\SoCore\Entity\AbstractEntity;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class FixtureDataItem {
	
	/** @var string */
	protected $ref;
	
	/** @var array */
	protected $data;
	
	/**
	 * FixtureDataItem constructor
	 *
	 * @param string|null $ref
	 * @param array $data
	 */
	public function __construct(?string $ref, array $data) {
		$this->ref = $ref;
		$this->data = $data;
	}
	
	public function buildEntity(YamlFixture $fixture, $class, $data = null, $ref = null) {
		if( !$data ) {
			$data = $this->data;
			$ref = $this->ref;
		}
		$entity = new $class();
		foreach( $data as $field => $value ) {
			$setter = $this->getFieldSetter($field);
			$value = $this->parseValue($value, $entity, $fixture);
			if( method_exists($entity, $setter) ) {
				// Try to set
				call_user_func([$entity, $setter], $value);
			} elseif( is_array($value) ) {
				// If unable to set, try to add
				$setter = $this->getFieldAdder($field);
				foreach( $value as $childValue ) {
					call_user_func([$entity, $setter], $childValue);
				}
			} else {
				throw new RuntimeException(sprintf('No setter/adder found for class "%s" and field "%s"', $class, $field));
			}
		}
		if( $entity instanceof AbstractEntity ) {
			$entity->setCreateDate(new DateTimeImmutable());
			$entity->setCreateIp('127.0.0.1');
		}
		if( method_exists($entity, 'initFixture') ) {
			call_user_func([$entity, 'initFixture'], $fixture);
		}
		if( $ref ) {
			$fixture->addReference($ref, $entity);
		}
		// Automatically cascade persist children first
		$fixture->getManager()->persist($entity);
		
		return $entity;
	}
	
	/**
	 * @param string $field
	 * @return string
	 */
	protected function getFieldSetter(string $field): string {
		return 'set' . str_replace('_', '', $field);
	}
	
	protected function parseValue($value, $entity, YamlFixture $fixture) {
		if( is_array($value) ) {
			// Array
			if( !isset($value['_class']) ) {
				// 0-indexed & associative arrays
				return array_map(fn($value) => $this->parseValue($value, $entity, $fixture), $value);
			} else {
				// Sub entity
				$class = $value['_class'];
				unset($value['_class']);
				if( isset($value['items']) ) {
					// Array of entities
					return array_map(fn($value) => $this->parseValue($value, $entity, $fixture), $value['items']);
				} else {
					// Single entity
					$ref = $value['_ref'] ?? null;
					unset($value['_ref']);
					
					return $this->buildEntity($fixture, $class, $value, $ref);
				}
			}
		}
		if( !is_string($value) ) {
			// int & others
			return $value;
		}
		// String
		// Deprecated usage of $, use ref() instead
		if( $value[0] === '$' ) {
			// Ref
			[$refName, $refClass] = explode(':', substr($value, 1), 2);

			return $fixture->getReference($refName, $refClass);
		}
		if( preg_match('#^(\w+)\((.*)\)$#', $value, $matches) ) {
			// function
			$function = $matches[1];
			$subValue = $this->parseValue(array_map(trim(...), explode(',', $matches[2])), $entity, $fixture);
			$method = 'parseValue' . $function;
			
			return $this->$method($subValue, $entity, $fixture);
		}
		if( $value === 'true' ) {
			return true;
		}
		if( $value === 'false' ) {
			return false;
		}
		if( ctype_digit($value) ) {
			return intval($value);
		}
		
		return $value;
	}
	
	/**
	 * @param string $field
	 * @return string
	 */
	protected function getFieldAdder(string $field): string {
		return 'add' . str_replace('_', '', $field);
	}
	
	/**
	 * @param array $args
	 * @param $entity
	 * @param YamlFixture $fixture
	 * @return array
	 *
	 *  TODO Move to SoIngeniousBundle
	 */
	protected function parseValueContent(array $args, $entity, YamlFixture $fixture): array {
		[$path, $format] = array_pad($args, 2, null);
		$file = new SplFileInfo(YamlFixture::CONFIG_PATH . '/' . $path);
		if( !$file->isFile() ) {
			throw new RuntimeException(sprintf('File "%s" not found', $path));
		}
		if( !$format ) {
			// Guess the content format from extension
			// TODO Use constants instead of hardcoded strings
			$format = match ($file->getExtension()) {
				'html' => 'html',
				'md' => 'markdown',
				'txt' => 'text',
				default => throw new RuntimeException(sprintf("Unknown content format for file '%s'", $path)),
			};
		}
		$content = file_get_contents($file);
		
		return [
			'format'  => $format,
			'text' => $content,
		];
	}
	
	protected function parseValueFile(array $args, $entity, YamlFixture $fixture) {
		[$path] = $args;
		$file = new SplFileInfo($path);
		if( !$file->isFile() ) {
			throw new RuntimeException(sprintf('Unable to open file "%s"', $path));
		}
		return match ($file->getExtension()) {
			'yaml', 'yml' => Yaml::parseFile($file->getRealPath()),
			'txt' => file_get_contents($file->getRealPath()),
			default => throw new RuntimeException(sprintf("Unable to parse file '%s'", $path)),
		};
	}
	
	protected function parseValueRef(array $args, $entity, YamlFixture $fixture) {
		[$refName, $refClass] = $args;
		return $fixture->getReference($refName, $refClass);
	}
	
	protected function parseValueEnum(array $args, $entity, YamlFixture $fixture) {
		[$value, $enumClass] = $args;
		return $enumClass::from($value);// Enum must implements method from(string)
	}
	
	protected function parseValueSlug(array $args, $entity, YamlFixture $fixture): string {
		return $fixture->getStringHelper()->convertToSlug($args[0]);
	}
	
	protected function parseValuePassword(array $args, $entity, YamlFixture $fixture): string {
		return $fixture->getUserService()->encodePassword($args[0], $entity);
	}
	
	protected function parseValueDate(array $args, $entity, YamlFixture $fixture): DateTime {
		return new DateTime($args[0], self::getUtcTimeZone());
	}
	
	protected static function getUtcTimeZone() {
		static $tz;
		if( !$tz ) {
			$tz = new DateTimeZone('UTC');
		}
		
		return $tz;
	}
	
}
