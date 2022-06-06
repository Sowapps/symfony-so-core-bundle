<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\EventListener;

use DateTimeImmutable;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sowapps\SoCoreBundle\Entity\AbstractEntity;
use Sowapps\SoCoreBundle\Service\AbstractUserService;

class EntityLifecycleSubscriber implements EventSubscriber {
	
	protected AbstractUserService $userService;
	
	/**
	 * EntityLifecycleSubscriber constructor
	 *
	 * @param AbstractUserService $userService
	 */
	public function __construct(AbstractUserService $userService) {
		$this->userService = $userService;
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
			$entity->setCreateIp(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
		}
	}
	
}
