<?php

namespace Sowapps\SoCore\Form\User;

use Sowapps\SoCore\Core\Form\AbstractForm;
use Sowapps\SoCore\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPictureForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		//		$builder->add('user', UserType::class, [
		//			'models' => [UserType::MODEL_PICTURE => true],
		//			'label' => false,
		//		]);
		
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_PICTURE)
			->end();
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults([
			//			'label_attr' => ['class' => 'd-none'],
		]);
	}
	
}
