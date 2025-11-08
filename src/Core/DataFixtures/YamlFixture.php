<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use Sowapps\SoCore\Service\AbstractUserService;
use Sowapps\SoCore\Service\StringHelper;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

abstract class YamlFixture extends Fixture {
	
	protected ?string $file = null;
	
	protected ?ObjectManager $manager = null;
	
	public function __construct(protected AbstractUserService $userService, protected StringHelper $stringHelper)
    {
    }
	
	public function load(ObjectManager $manager) {
		if( !$this->file ) {
			// This own class is abstract & load nothing
			return;
		}
		$this->manager = $manager;
		$dataSets = $this->buildDataSets();
		if( !$dataSets ) {
			return;
		}
		foreach( $dataSets as $dataSet ) {
			$dataSet->buildEntityList($this);
		}
	}
	
	public function buildDataSets(): ?array {
		$fileLocator = new FileLocator('config/fixtures');
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
	
	/**
	 * @return string
	 */
	public function getFile(): string {
		return $this->file;
	}
	
	/**
	 * @return ObjectManager|null
	 */
	public function getManager(): ?ObjectManager {
		return $this->manager;
	}
	
	/**
	 * @return AbstractUserService
	 */
	public function getUserService(): AbstractUserService {
		return $this->userService;
	}
	
	/**
	 * @return StringHelper
	 */
	public function getStringHelper(): StringHelper {
		return $this->stringHelper;
	}
	
}
