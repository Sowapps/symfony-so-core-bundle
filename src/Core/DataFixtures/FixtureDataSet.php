<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\DataFixtures;

class FixtureDataSet {
	
	/** @var string */
	protected string $name;
	
	/** @var string */
	protected string $class;
	
	/** @var FixtureDataItem[] */
	protected array $items;
	
	/**
	 * FixtureDataSet constructor
	 *
	 * @param string $name
	 * @param string $class
	 * @param FixtureDataItem[] $items
	 */
	public function __construct(string $name, string $class, array $items) {
		$this->name = $name;
		$this->class = $class;
		$this->items = $items;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getClass(): string {
		return $this->class;
	}
	
	public function getItems(): array {
		return $this->items;
	}
	
}
