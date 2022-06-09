<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\DataFixtures;

class FixtureDataSet {
	
	/** @var string */
	protected $name;
	
	/** @var string */
	protected $class;
	
	/** @var FixtureDataItem[] */
	protected $items;
	
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
	
	/**
	 * @param YamlFixture $fixture
	 * @return int
	 */
	public function buildEntityList(YamlFixture $fixture) {
		$count = 0;
		foreach( $this->items as $item ) {
			$item->buildEntity($fixture, $this->class);
			$count++;
		}
		$fixture->getManager()->flush();
		
		return $count;
	}
	
}
