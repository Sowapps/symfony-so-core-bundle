<?php

namespace Sowapps\SoCore\Core\Form\Creator;

class Form {
	
	private array $models = [];
	
	/**
	 * @param FormCreator $creator
	 * @param string $name
	 * @param string $type
	 */
	public function __construct(private readonly FormCreator $creator, private readonly string $name, private readonly string $type)
    {
    }
	
	public function addModel(string $name, bool $enabled = true): static {
		$this->models[$name] = $enabled;
		
		return $this;
	}
	
	public function end(): FormCreator {
		$builder = $this->creator->getBuilder();
		$builder->setAttribute('class', 'no-label');
		//		$data = $builder->getData();
		//		dump(sprintf('$data 1 (%s)', $this->name), $data);
		//		$data = is_object($data) ? $data : $data[$this->name] ?? null;
		//		dump('$data 2 : ' . gettype($data) . ' : '.(is_object($data) ? get_class($data): 'NAO'));
		//		dump($data, $this->name, $this->type, is_object($data) ? $data : $data[$this->name] ?? null);
		//		dump($builder->getData());
		//		$builder->add($this->name, $this->type, $this->creator->getOptions() + [
		$builder->add($this->name, $this->type, [
			'label'  => false,
			// Instance of entity, array containing name as key or null
			'data'   => $builder->getData()[$this->name],
			'models' => $this->models,
		]);
		
		return $this->creator;
	}
	
}
