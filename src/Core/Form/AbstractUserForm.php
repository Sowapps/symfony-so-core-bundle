<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Form;

use Sowapps\SoCore\Service\AbstractUserService;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractUserForm extends AbstractForm {
	
	protected AbstractUserService $userService;
	
	public function __construct(AbstractUserService $userService) {
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
