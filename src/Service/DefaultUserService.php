<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\Repository\AbstractUserRepository;

class DefaultUserService extends AbstractUserService {
	
	function getUserRepository(): AbstractUserRepository {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->entityManager->getRepository($this->getUserClass());
	}
	
}
