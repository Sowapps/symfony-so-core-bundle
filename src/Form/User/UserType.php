<?php

namespace Sowapps\SoCore\Form\User;

use Sowapps\SoCore\Core\Form\AbstractUserForm;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Form\ImageType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractUserForm {
	
	const MODEL_ADMIN = 'admin';
	const MODEL_CALCULATED = 'calculated';
	const MODEL_NAME = 'name';
	const MODEL_EMAIL = 'email';
	const MODEL_PICTURE = 'picture';
	const MODEL_PASSWORD = 'password';
	const MODEL_PASSWORD_ADMIN = 'password_admin';
	const MODEL_REQUIRE_PASSWORD = 'require_password';
	
	public static function getPasswordOptions(): array {
		return [
			'mapped'      => false,
			'constraints' => [
				new NotBlank(message: 'user.password.empty'),
				new Length(min: 6, max: 4096, minMessage: 'user.password.length'),
			],
		];
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/** @var AbstractUser $user */
		$user = $builder->getData();
		$this->setOptions($options);
		if( $this->hasModel(self::MODEL_REQUIRE_PASSWORD) && !$user->isNew() ) {
			$builder->add('currentPassword', PasswordType::class, [
				'mapped'      => false,
				'constraints' => [
					new UserPassword(),
				],
			]);
		}
		if( $this->hasModel(self::MODEL_NAME) ) {
			$builder->add('name', null, [
				'disabled' => $this->isModelDisabled(self::MODEL_EMAIL),
			]);
		}
		if( $this->hasModel(self::MODEL_EMAIL) ) {
			$builder->add('email', null, [
				'attr'     => ['autocomplete' => 'username'],
				'disabled' => $this->isModelDisabled(self::MODEL_EMAIL),
			]);
		}
		if( $this->hasModel(self::MODEL_PICTURE) ) {
			$builder->add('avatar', ImageType::class, [
				'preview_width' => '10rem',
			]);
		}
		if( $this->hasModel(self::MODEL_PASSWORD_ADMIN) ) {
			$fieldOptions = static::getPasswordOptions();
			$fieldOptions['label'] = 'user.field.password';
			$fieldOptions['attr'] = ['autocomplete' => 'new-password'];
			$builder->add('plainPassword', TextType::class, $fieldOptions);
		} elseif( $this->hasModel(self::MODEL_PASSWORD) ) {
			$builder->add('plainPassword', RepeatedType::class, static::getPasswordOptions() + [
					'type'        => PasswordType::class,
					'first_name'  => 'password',
					'second_name' => 'passwordConfirm',
					'options'     => ['attr' => ['autocomplete' => 'new-password']],
				]);
		}
		if( $this->hasModel(self::MODEL_ADMIN) ) {
			$builder
				->add('roles', ChoiceType::class, [
					'required'    => true,
					'expanded'    => true,
					'multiple'    => true,
					'choices'     => [
						'user.roleState.user'      => AbstractUser::ROLE_USER,
						'user.roleState.admin'     => AbstractUser::ROLE_ADMIN,
						'user.roleState.developer' => AbstractUser::ROLE_DEVELOPER,
					],
					//					'label_translation_parameters' => ['gender' => $user->getGenderKey()],
					'choice_attr' => function ($element) {
						$requiredRole = $this->userService->getRoleRestriction($element);
						if( !$requiredRole ) {
							$disable = true;
						} else {
							$disable = !$this->userService->isCurrentHavingRole($requiredRole);
						}
						
						return $disable ? ['disabled' => 'disabled'] : [];
					},
				]);
		}
		if( $this->hasModel(self::MODEL_CALCULATED) ) {
			$builder->add('timezone', HiddenType::class, [
				'attr' => [
					'data-controller' => 'sowapps--so-core--input-timezone',
				],
			]);
		}
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults([
			'models' => [self::MODEL_EMAIL => true, self::MODEL_PASSWORD => true],
		]);
	}
	
}
