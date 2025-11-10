<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Environment;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Class EnvironmentProject
 *
 * @package Sowapps\SoCore\Core\Environment
 */
class EnvironmentProject {
	
	protected string $url;
	
	protected string $path;
	
	protected string $version;
	
	public function __construct(protected string $name, object $object) {
		$this->url = $object->url;
		$this->path = $object->path;
		$this->version = $object->version;
	}
	
	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}
	
	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}
	
	/**
	 * @return string
	 */
	public function getVersion(): string {
		return $this->version;
	}
	
	#[ArrayShape(['url' => "mixed", 'path' => "mixed", 'version' => "mixed"])]
	public function toArray(): array {
		return [
			'url'     => $this->url,
			'path'    => $this->path,
			'version' => $this->version,
		];
	}
	
}
