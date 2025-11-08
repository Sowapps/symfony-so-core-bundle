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
		if( $value[0] === '#' ) {
			// Ref
			$ref = substr($value, 1);
			
			return $fixture->getReference($ref);
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
	
	//	protected function parseValuePicture(array $args, $entity, YamlFixture $fixture) {
	//		$picture = new PresentationPicture();
	//		$picture->setPresentation($args[0], $args[1]);
	//
	//		return $picture;
	//	}
	
	protected function parseValueSlug(array $args, $entity, YamlFixture $fixture) {
		return $fixture->getStringHelper()->convertToSlug($args[0]);
	}
	
	protected function parseValuePassword(array $args, $entity, YamlFixture $fixture) {
		return $fixture->getUserService()->encodePassword($args[0], $entity);
	}
	
	protected function parseValueDate(array $args, $entity, YamlFixture $fixture) {
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
