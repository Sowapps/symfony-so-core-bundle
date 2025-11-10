<?php

namespace Sowapps\SoCore\Form\User;

use Sowapps\SoCore\Core\Form\AbstractForm;
use Symfony\Component\Form\FormBuilderInterface;

class UserRecoveryRequestForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		$builder
			->add('email', null, [
				'error_bubbling' => true,
				'required'       => true,
			]);
	}
	
}
