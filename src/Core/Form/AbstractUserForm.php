<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Form;

use Sowapps\SoCoreBundle\Service\AbstractUserService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractUserForm extends AbstractForm {
	
	protected AbstractUserService $userService;
	
	public function __construct(TranslatorInterface $translator, AbstractUserService $userService) {
		parent::__construct($translator);
		$this->userService = $userService;
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults([
			'data_class'   => $this->userService->getUserClass(),
			'label_format' => 'user.field.%name%',
		]);
	}
	
}
