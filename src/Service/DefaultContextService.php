<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\Contracts\ContextInterface;
use Sowapps\SoCore\Contracts\CurrencyInterface;
use Sowapps\SoCore\Core\Environment\Environment;
use Sowapps\SoCore\Core\Locale\LocaleFormatter;
use Sowapps\SoCore\Entity\Language;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultContextService implements ContextInterface {
	
	const DEFAULT_LANGUAGE = 'fr';
	
	protected Kernel $kernel;
	
	protected RequestStack $requestStack;
	
	protected TranslatorInterface $translator;
	
	protected UrlGeneratorInterface $router;
	
	protected ?LocaleFormatter $localeFormatter = null;
	
	protected ?Environment $environment = null;
	
	protected ?Language $currentLanguage = null;
	
	public function __construct(Kernel $kernel, RequestStack $requestStack, TranslatorInterface $translator, UrlGeneratorInterface $router, Environment $environment) {
		$this->kernel = $kernel;
		$this->requestStack = $requestStack;
		$this->translator = $translator;
		$this->router = $router;
		$this->environment = $environment;
	}
	
	public function isDebug(): bool {
		return $this->kernel->isDebug();
	}
	
	public function getEnvironmentName(): string {
		return $this->environment->getEnvironmentName();
	}
	
	public function getEnvironmentLevel(): string {
		return $this->environment->getEnvironmentLevel();
	}
	
	public function getEnvironmentId(): string {
		return $this->environment->getEnvironmentId();
	}
	
	public function getApplicationLetter(): string {
		return $this->environment->isProd() ? 'L' : strtoupper($this->getEnvironmentName()[0]);
	}
	
	protected function setCurrentLocale(string $locale) {
		$this->requestStack->getMainRequest()->setLocale($locale);
		$this->router->getContext()->setParameter('_locale', $locale);
	}
	
	/**
	 * @return Language|null
	 */
	public function getCurrentLanguage(): ?Language {
		return $this->currentLanguage;
	}
	
	public function setCurrentLanguage(Language $language, CurrencyInterface $currency) {
		$this->currentLanguage = $language;
		$this->setCurrentLocale($language->getLocale());
		$this->localeFormatter = new LocaleFormatter($this->translator, $language, $currency);
	}
	
	/**
	 * @return LocaleFormatter
	 */
	public function getLocaleFormatter(): LocaleFormatter {
		return $this->localeFormatter;
	}
	
}
