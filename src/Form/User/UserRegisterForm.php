<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Form\User;

use Sowapps\SoCore\Core\Form\AbstractForm;
use Sowapps\SoCore\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegisterForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_NAME)
			->addModel(UserType::MODEL_EMAIL)
			->addModel(UserType::MODEL_PASSWORD)
			->addModel(UserType::MODEL_CALCULATED)
			->end();
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'row_attr' => [
				'class' => 'form-floating',
			],
		]);
	}
	
}
