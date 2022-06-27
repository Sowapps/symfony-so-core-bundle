<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\EventListener;

use Sowapps\SoCore\Contracts\ContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface {
	
	private ContextInterface $contextService;
	
	public function __construct(ContextInterface $contextService) {
		$this->contextService = $contextService;
	}
	
	public function onKernelRequest(RequestEvent $event): void {
		$this->contextService->setDefaultLanguage();
	}
	
	public static function getSubscribedEvents(): array {
		return [
			KernelEvents::REQUEST => [
				// must be registered after the Router to have access to the _locale
				['onKernelRequest', 100],
			],
		];
	}
	
}
