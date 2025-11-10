<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\EventListener;

use RuntimeException;
use Sowapps\SoCore\Contracts\ContextInterface;
use Sowapps\SoCore\Core\Locale\Currency\EuroCurrency;
use Sowapps\SoCore\Service\DefaultContextService;
use Sowapps\SoCore\Service\LanguageService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface {
	
	public function __construct(private readonly ContextInterface $contextService, private readonly LanguageService $languageService)
    {
    }
	
	public function onKernelRequest(RequestEvent $event): void {
		$language = $this->languageService->getLanguageByLocale(DefaultContextService::DEFAULT_LANGUAGE);
		if( !$language ) {
			throw new RuntimeException(sprintf('No language found for default locale "%s"', DefaultContextService::DEFAULT_LANGUAGE));
		}
		$this->contextService->setCurrentLanguage($language, new EuroCurrency());
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
