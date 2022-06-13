<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Form\User;

use Sowapps\SoCoreBundle\Core\Form\AbstractUserForm;
use Sowapps\SoCoreBundle\Core\Form\Creator\FormCreator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegisterForm extends AbstractUserForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$creator = new FormCreator($builder, $options);
		$creator->addForm('user', UserType::class)
			->addModel(UserType::MODEL_NAME)
			->addModel(UserType::MODEL_EMAIL)
			->addModel(UserType::MODEL_PASSWORD)
			->addModel(UserType::MODEL_CALCULATED)
			->end();
		//		$data = $builder->getData();
		//		$builder
		//			->add('user', UserType::class, [
		//				'label'  => false,
		//				'data'   => is_object($data) ? $data : $data['user'],
		//				'models' => [UserType::MODEL_NAME, UserType::MODEL_EMAIL, UserType::MODEL_PASSWORD, UserType::MODEL_CALCULATED],
		//			]);
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'row_attr' => [
				'class' => 'form-floating',
			],
		]);
	}
	
}
