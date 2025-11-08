<?php

namespace Sowapps\SoCore\Core\Form\Creator;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Handle only subform and a form containing subforms can not contain raw fields by this way
 */
class FormCreator {
	
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function __construct(private readonly FormBuilderInterface $builder, private readonly array $options)
    {
    }
	
	public function addForm(string $name, string $type): Form {
		return new Form($this, $name, $type);
	}
	
	/**
	 * @return FormBuilderInterface
	 */
	public function getBuilder(): FormBuilderInterface {
		return $this->builder;
	}
	
	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}
	
}
