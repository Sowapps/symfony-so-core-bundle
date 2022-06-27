<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://symfony.com/doc/current/form/create_form_type_extension.html
 */
class FormExtension extends AbstractTypeExtension {
	
	public static function getExtendedTypes(): iterable {
		return [FormType::class];// Not working with AbstractForm
	}
	
	public function configureOptions(OptionsResolver $resolver): void {
		$resolver->setDefault('require_id', false);
	}
	
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$view->vars['require_id'] = $options['require_id'];
	}
	
}
