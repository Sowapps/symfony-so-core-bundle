<?php

namespace Sowapps\SoCoreBundle\Form\User;

use Sowapps\SoCoreBundle\Core\Form\AbstractForm;
use Symfony\Component\Form\FormBuilderInterface;

class UserRecoveryRequestForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('email', null, [
				'error_bubbling' => true,
				'required'       => true,
			]);
	}
	
}
