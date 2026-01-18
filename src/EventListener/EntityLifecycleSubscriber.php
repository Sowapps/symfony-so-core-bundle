<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Service\SecurityService;

/**
 * EntityLifecycleSubscriber set missing metadata in So entities
 */
#[AsDoctrineListener(event: Events::prePersist)]
readonly class EntityLifecycleSubscriber {
	
	public function __construct(private SecurityService $securityService) {
	}
	
	public function prePersist(PrePersistEventArgs $args): void {
		$entity = $args->getObject();
		if( !($entity instanceof AbstractEntity) ) {
			return;
		}
		if( !$entity->getCreateUser() ) {
			$currentUser = $this->securityService->getCurrentUser();
			if( $currentUser ) {
				$entity->setCreateUser($currentUser);
			}
		}
		if( !$entity->getCreateIp() ) {
			$entity->setCreateIp($this->securityService->getRemoteIp());
		}
	}
	
}
