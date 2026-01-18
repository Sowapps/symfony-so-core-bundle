<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Service\SecurityService;
use Sowapps\SoCore\Service\StringService;
use Sowapps\SoCore\Service\UserService;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

abstract class YamlFixture extends Fixture {
	
	public const CONFIG_PATH = 'config/fixtures';
	
	protected ?string $file = null;
	
	protected ?ObjectManager $manager = null;
	
	/** @var array<array<AbstractEntity|callable>>  */
	protected array $itemPostProcess = [];
	
	public function __construct(
		protected SecurityService $securityService,
		protected UserService     $userService,
		protected StringService   $stringService,
		protected FixtureParser   $fixtureParser
	)
    {
    }
	
	public function addItemPostProcess(AbstractEntity $entity, callable $callback): static {
		$this->itemPostProcess[] = [$entity, $callback];
		
		return $this;
	}
	
	public function load(ObjectManager $manager): void {
		if( !$this->file ) {
			// This own class is abstract & load nothing
			return;
		}
		$this->manager = $manager;
		$dataSets = $this->loadDataSets();
		if( !$dataSets ) {
			return;
		}
		foreach( $dataSets as $dataSet ) {
			$this->buildDataSet($dataSet);
		}
	}
	
	protected function buildDataSet(FixtureDataSet $dataSet): int {
		$count = 0;
		foreach( $dataSet->getItems() as $item ) {
			$this->buildSetEntity($dataSet->getClass(), $item);
			
			foreach( $item->getEntities() as $entity ) {
				$this->manager->persist($entity);
			}
			
			$count++;
		}
		unset($item, $entity);
		$this->manager->flush();
		
		foreach($this->itemPostProcess as [$entity, $callback]) {
			call_user_func($callback, $entity);
		}
		
		return $count;
	}
	
	public function buildSetEntity($class, FixtureDataItem $item): AbstractEntity {
		return $this->fixtureParser->buildItemEntity($item, $this, $class);
	}
	
	/**
	 * @return FixtureDataSet[]|null
	 */
	public function loadDataSets(): ?array {
		$fileLocator = new FileLocator(YamlFixture::CONFIG_PATH);
		try {
			$file = $fileLocator->locate($this->file);
		} catch( FileLocatorFileNotFoundException ) {
			// Ignore missing yaml file
			return null;
		}
		$dataSets = [];
		$yamlDataSets = Yaml::parse(file_get_contents($file), Yaml::PARSE_DATETIME);
		foreach( $yamlDataSets as $yamlSetName => $yamlSet ) {
			if( empty($yamlSet['class']) ) {
				throw new RuntimeException(sprintf('No entity class provided for fixture data set %s', $yamlSetName));
			}
			$items = [];
			foreach( $yamlSet['items'] as $yamlItem ) {
				$ref = null;
				if( isset($yamlItem['_ref']) ) {
					$ref = $yamlItem['_ref'];
					unset($yamlItem['_ref']);
				}
				$items[] = new FixtureDataItem($ref, $yamlItem);
			}
			$dataSets[] = new FixtureDataSet($yamlSetName, $yamlSet['class'], $items);
		}
		
		return $dataSets;
	}
	
	public function getFile(): string {
		return $this->file;
	}
	
	public function getManager(): ?ObjectManager {
		return $this->manager;
	}
	
	public function getSecurityService(): SecurityService {
		return $this->securityService;
	}
	
	public function getUserService(): UserService {
		return $this->userService;
	}
	
	public function getStringService(): StringService {
		return $this->stringService;
	}
	
	public function getParser(): FixtureParser {
		return $this->fixtureParser;
	}
	
}
