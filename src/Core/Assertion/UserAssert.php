<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Assertion;

use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * First valid is validating assert
 */
class UserAssert implements Assertable {
	
	private bool $valid = false;
	
	public function __construct(
		private readonly SecurityService $securityService,
		private readonly ?AbstractUser   $user
	) {
	}
	
	public function isAuthenticatedOr(): static {
		if( !$this->isValid() && $this->user ) {
			$this->valid = true;
		}
		
		return $this;
	}
	
	public function isAdminOr(): static {
		if( !$this->isValid() && $this->securityService->isAdmin($this->user) ) {
			$this->valid = true;
		}
		
		return $this;
	}
	
	public function isOwnerOr(AbstractEntity $entity): static {
		if( !$this->isValid() && $entity->isOwnedBy($this->user) ) {
			$this->valid = true;
		}
		
		return $this;
	}
	
	protected function isValid(): bool {
		return $this->valid;
	}
	
	function throw(): void {
		if( !$this->isValid() ) {
			throw new UnauthorizedHttpException('Login');
		}
	}
	
}
