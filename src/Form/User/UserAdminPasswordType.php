<?php

namespace Sowapps\SoCoreBundle\Form\User;

use Sowapps\SoCoreBundle\Core\Form\AbstractForm;
use Sowapps\SoCoreBundle\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;

class UserAdminPasswordType extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_PASSWORD_ADMIN)
			->end();
	}
	
}
