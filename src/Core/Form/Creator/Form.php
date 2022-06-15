<?php

namespace Sowapps\SoCoreBundle\Core\Form\Creator;

class Form {
	
	private FormCreator $creator;
	
	private string $name;
	
	private string $type;
	
	private array $models = [];
	
	/**
	 * @param FormCreator $creator
	 * @param string $name
	 * @param string $type
	 */
	public function __construct(FormCreator $creator, string $name, string $type) {
		$this->creator = $creator;
		$this->name = $name;
		$this->type = $type;
	}
	
	public function addModel(string $name, bool $enabled = true): static {
		$this->models[$name] = $enabled;
		
		return $this;
	}
	
	public function end(): FormCreator {
		$builder = $this->creator->getBuilder();
		$data = $builder->getData();
//		dump($data, $this->name, $this->type, is_object($data) ? $data : $data[$this->name] ?? null);
		$builder->add($this->name, $this->type, [
			'label'  => false,
			// Instance of entity, array containing name as key or null
			'data'   => is_object($data) ? $data : $data[$this->name] ?? null,
			'models' => $this->models,
		]);
		
		return $this->creator;
	}
	
}
