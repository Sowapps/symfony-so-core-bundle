<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Form\Type;

use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PhoneType extends PhoneNumberType {
	
	public function buildView(FormView $view, FormInterface $form, array $options) {
		parent::buildView($view, $form, $options);
		
		$view->vars['invalid_message'] = $options['invalid_message'];
	}
	
}
