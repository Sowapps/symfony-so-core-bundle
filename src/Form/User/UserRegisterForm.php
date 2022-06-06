<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Form\User;

use App\Entity\User;
use Sowapps\SoCoreBundle\Core\Form\AbstractForm;
use Sowapps\SoCoreBundle\Service\AbstractUserService;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserRegisterForm extends AbstractForm {
	
	protected AbstractUserService $userService;
	
	public function __construct(TranslatorInterface $translator, AbstractUserService $userService) {
		parent::__construct($translator);
		$this->userService = $userService;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/** @var User $user */
		$user = $builder->getData();
		$builder->add('name');
		$builder->add('email', null, [
			'attr' => ['autocomplete' => 'username'],
		]);
		$builder->add('plainPassword', RepeatedType::class, static::getPasswordOptions() + [
				'type'        => PasswordType::class,
				'first_name'  => 'password',
				'second_name' => 'passwordConfirm',
				'options'     => ['attr' => ['autocomplete' => 'new-password']],
			]);
		//		$builder
		//			->add('roles', ChoiceType::class, [
		//				'required'    => true,
		//				'expanded'    => true,
		//				'multiple'    => true,
		//				'choices'     => [
		//					'user.roleState.user'      => User::ROLE_USER,
		//					'user.roleState.admin'     => User::ROLE_ADMIN,
		//					'user.roleState.developer' => User::ROLE_DEVELOPER,
		//				],
		//				'choice_attr' => function ($element) {
		//					$requiredRole = $this->userService->getRoleRestriction($element);
		//					if( !$requiredRole ) {
		//						$disable = true;
		//					} else {
		//						$disable = !$this->userService->isCurrentHavingRole($requiredRole);
		//					}
		//
		//					return $disable ? ['disabled' => 'disabled'] : [];
		//				},
		//			]);
		$builder->add('timezone', HiddenType::class, [
			'attr' => [
				'data-controller' => 'form--timezone',
			],
		]);
	}
	
	public static function getPasswordOptions(): array {
		return [
			'mapped'      => false,
			'constraints' => [
				new NotBlank([
					'message' => 'user.password.empty',
				]),
				new Length([
					'min'        => 6,
					'max'        => 4096,
					'minMessage' => 'user.password.length',
					// max length allowed by Symfony for security reasons
				]),
			],
		];
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'data_class'   => $this->userService->getUserClass(),
			'label_format' => 'entity.user.field.%name%',
			'row_attr'     => [
				'class' => 'form-floating',
			],
		]);
	}
	
}
