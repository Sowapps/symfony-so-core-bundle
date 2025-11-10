<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Form;

use Sowapps\SoCore\Core\Form\AbstractForm;
use Sowapps\SoCore\Entity\Language;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageForm extends AbstractForm {
	
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		/** @var FormBuilder $builder */
		$builder
			->add('key', null, [
				'attr' => [
					'placeholder' => 'english, us_english ...',
				],
			])
			->add('primaryCode', null, [
				'attr' => [
					'placeholder' => 'en ...',
				],
			])
			->add('regionCode', null, [
				'attr' => [
					'placeholder' => 'GB, US ...',
				],
			])
			->add('locale', null, [
				'attr' => [
					'placeholder' => 'en, us, en_US ...',
				],
			]);
	}
	
	public function configureOptions(OptionsResolver $resolver): void {
		parent::configureOptions($resolver);
		$resolver->setDefaults([
			'data_class'   => Language::class,
			'label_format' => 'language.field.%name%',
		]);
	}
	
}
