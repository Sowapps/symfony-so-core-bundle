<?php

namespace Sowapps\SoCoreBundle\Form\User;

use Sowapps\SoCoreBundle\Core\Form\AbstractForm;
use Sowapps\SoCoreBundle\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;

class UserRecoveryPasswordForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_PASSWORD)
			->end();
		//		$builder
		//			->add('newPassword', RepeatedType::class, [
		//				'type'           => PasswordType::class,
		//				'mapped'         => false,
		//				'constraints'    => [
		//					new NotBlank([
		//						'message' => 'user.password.empty',
		//					]),
		//					new Length([
		//						'min'        => 6,
		//						'max'        => 4096,
		//						'minMessage' => 'user.password.length',
		//						// max length allowed by Symfony for security reasons
		//					]),
		//				],
		//				'first_options'  => ['label' => 'user.recover.newPassword'],
		//				'second_options' => ['label' => 'user.recover.newPasswordConfirm'],
		//			]);
	}
	
}
