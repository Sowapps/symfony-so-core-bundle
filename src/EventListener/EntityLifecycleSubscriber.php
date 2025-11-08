<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\EventListener;

use DateTimeImmutable;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Service\AbstractUserService;

class EntityLifecycleSubscriber implements EventSubscriber {
	
	/**
	 * EntityLifecycleSubscriber constructor
	 *
	 * @param AbstractUserService $userService
	 */
	public function __construct(protected AbstractUserService $userService)
    {
    }
	
	/**
	 * @inheritDoc
	 */
	public function getSubscribedEvents(): array {
		return [
			Events::prePersist,
		];
	}
	
	public function prePersist(LifecycleEventArgs $args) {
		$entity = $args->getObject();
		if( !($entity instanceof AbstractEntity) ) {
			return;
		}
		
		if( !$entity->getCreateDate() ) {
			$entity->setCreateDate(new DateTimeImmutable());
		}
		if( !$entity->getCreateUser() ) {
			$currentUser = $this->userService->getCurrent();
			if( $currentUser ) {
				$entity->setCreateUser($currentUser);
			}
		}
		if( !$entity->getCreateIp() ) {
			$entity->setCreateIp($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
		}
	}
	
}
