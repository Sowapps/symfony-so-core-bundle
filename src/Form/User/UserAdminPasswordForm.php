<?php

namespace Sowapps\SoCore\Form\User;

use Sowapps\SoCore\Core\Form\AbstractForm;
use Sowapps\SoCore\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAdminPasswordForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_PASSWORD_ADMIN)
			->end();
	}
	
	public function configureOptions(OptionsResolver $resolver): void {
		parent::configureOptions($resolver);
		$resolver->setDefaults([
			'label_attr' => ['class' => 'd-none'],
		]);
	}
	
}
