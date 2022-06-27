<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Environment;

use Exception;

/**
 * Class Environment
 *
 * @package Sowapps\SoCore\Core\Environment
 */
class Environment {
	
	const LEVEL_DEV = 'dev';
	const LEVEL_STAGING = 'staging';
	const LEVEL_PROD = 'prod';
	
	protected string $environmentId;
	
	protected string $environmentName;
	
	protected string $environmentLevel;
	
	protected string $projectName;
	
	/** @var EnvironmentProject[] */
	protected array $projects;
	
	public function __construct($object) {
		$this->fromObject($object);
	}
	
	protected function fromObject(object $object): void {
		$this->environmentId = $object->environment_id;
		$this->environmentName = $object->environment_name;
		$this->environmentLevel = $object->environment_level;
		$this->projectName = $object->project_name;
		$this->projects = [];
		foreach( $object->projects as $projectName => $projectObject ) {
			$this->projects[$projectName] = new EnvironmentProject($projectName, (object) $projectObject);
		}
	}
	
	public function isProd(): bool {
		return $this->getEnvironmentLevel() === static::LEVEL_PROD;
	}
	
	/**
	 * @return string
	 */
	public function getEnvironmentLevel(): string {
		return $this->environmentLevel;
	}
	
	/**
	 * @return string
	 */
	public function getEnvironmentId(): string {
		return $this->environmentId;
	}
	
	/**
	 * @return string
	 */
	public function getEnvironmentName(): string {
		return $this->environmentName;
	}
	
	/**
	 * @return string
	 */
	public function getProjectName(): string {
		return $this->projectName;
	}
	
	/**
	 * @return EnvironmentProject
	 */
	public function getProject(): EnvironmentProject {
		return $this->projects[$this->projectName];
	}
	
	/**
	 * @return EnvironmentProject[]
	 */
	public function getProjects(): array {
		return $this->projects;
	}
	
	/**
	 * @param $name
	 * @return EnvironmentProject
	 * @throws Exception
	 */
	public function getProjectByName($name): EnvironmentProject {
		if( !isset($this->projects[$name]) ) {
			throw new Exception(sprintf('Unknown project %s', $name));
		}
		
		return $this->projects[$name];
	}
	
	public function __serialize() {
		return $this->toArray();
	}
	
	protected function toArray(): array {
		$projects = [];
		foreach( $this->projects as $project ) {
			$projects[$project->getName()] = $project->toArray();
		}
		
		return [
			'environment_id'    => $this->environmentId,
			'environment_name'  => $this->environmentName,
			'environment_level' => $this->environmentLevel,
			'project_name'      => $this->projectName,
			'projects'          => $projects,
		];
	}
	
	public function __unserialize($data) {
		$this->fromObject((object) $data);
	}
	
}
