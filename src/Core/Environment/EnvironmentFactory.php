<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Environment;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class EnvironmentFactory {
	
	protected KernelInterface $kernel;
	
	protected string $projectPath;
	
	protected string $environmentFile = '/config/environment.json';
	
	private CacheInterface $cache;
	
	/**
	 * EnvironmentFactory constructor
	 *
	 * @param CacheInterface $cache
	 * @param string $projectPath
	 */
	public function __construct(KernelInterface $kernel, CacheInterface $cache) {
		$this->kernel = $kernel;
		$this->cache = $cache;
	}
	
	public function __invoke() {
		$factory = $this;
		
		return $this->cache->get('so_core.environment', function (ItemInterface $item) use ($factory) {
			$item->expiresAfter(86400);// Once a day
			$filePath = $factory->getEnvironmentFile();
			if( file_exists($filePath) ) {
				$data = json_decode(file_get_contents($filePath));
			} else {
				$data = $this->parseKernel();
			}
			
			return new Environment($data);
		});
	}
	
	public function getEnvironmentFile(): string {
		return $this->kernel->getProjectDir() . $this->environmentFile;
	}
	
	public function parseKernel(): array {
		return [
			"environment_id"    => strtoupper($this->kernel->getEnvironment()),
			"environment_name"  => strtoupper($this->kernel->getEnvironment()),
			"environment_level" => strtolower($this->kernel->getEnvironment()),
			"project_name"      => basename($this->kernel->getProjectDir()),
		];
	}
	
}
