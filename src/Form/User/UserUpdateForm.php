<?php

namespace Sowapps\SoCore\Form\User;

use Sowapps\SoCore\Core\Form\AbstractForm;
use Sowapps\SoCore\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;

class UserUpdateForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_EMAIL)
			->end();
	}
	
}
