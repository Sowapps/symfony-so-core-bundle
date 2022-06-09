<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class SampleFixture extends YamlFixture implements DependentFixtureInterface {
	
	/** @var string|null */
	protected ?string $file = 'fixtures-sample.yaml';
	
	public function getDependencies(): array {
		return [
			InitFixture::class,
		];
	}
	
}
