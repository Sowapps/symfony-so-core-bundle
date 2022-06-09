<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use Sowapps\SoCoreBundle\Entity\Language;
use Sowapps\SoCoreBundle\Repository\LanguageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class LanguageService extends AbstractEntityService {
	
	protected RouterInterface $router;
	
	protected ?string $currentCountry = null;
	
	/**
	 * LanguageService constructor
	 *
	 * @param RouterInterface $router
	 * @param string $projectPath
	 */
	public function __construct(RouterInterface $router) {
		$this->router = $router;
	}
	
	/**
	 * @param int $id
	 * @return Language|null
	 */
	public function getLanguage(int $id): ?Language {
		return $this->getLanguageRepository()->find($id);
	}
	
	/**
	 * @return LanguageRepository
	 */
	public function getLanguageRepository(): LanguageRepository {
		return $this->entityManager->getRepository(Language::class);
	}
	
	/**
	 * @param string $locale
	 * @return Language|null
	 */
	public function getLanguageByPrimary(string $locale): ?Language {
		return $this->getLanguageRepository()->findByPrimary($locale);
	}
	
	/**
	 * @return Language[]
	 */
	public function getLanguages(): array {
		return $this->getLanguageRepository()->findAll();
	}
	
	
	public function getBestUserLanguage(Request $request): ?Language {
		
		foreach( $this->getClientPreferredLocales($request) as $locale ) {
			$language = $this->getLanguageByLocale($locale);
			if( $language ) {
				// Return first matching language
				return $language;
			}
			if( strlen($locale) < 5 ) {
				// Try to find a language with this local as primary code
				// e.g for fr_CA,fr App will look for fr_FR
				$language = $this->getLanguageByLocale($locale);
				if( $language ) {
					// Return first matching language
					return $language;
				}
			}
		}
		
		return $this->getDefaultLocaleLanguage();
	}
	
	public function getClientPreferredLocales(Request $request): array {
		//en-US,en;q=0.9,fr-FR;q=0.8,fr;q=0.7
		$acceptLanguageString = $request->headers->get('accept-language');
		if( !$acceptLanguageString ) {
			return [];
		}
		$httpLocales = explode(',', $acceptLanguageString);
		$locales = [];
		foreach( $httpLocales as $httpLocale ) {
			$locales[] = $this->getLocaleFromHttpFormat($httpLocale);
		}
		
		return $locales;
	}
	
	public function getLocaleFromHttpFormat($httpLocale): string {
		if( strlen($httpLocale) > 7 ) {
			[$httpLocale,] = explode(';', $httpLocale);
		}
		if( strlen($httpLocale) > 3 ) {
			$httpLocale = strtr($httpLocale, '-', '_');
		}
		
		return $httpLocale;
	}
	
	/**
	 * @param string $locale
	 * @return Language|null
	 */
	public function getLanguageByLocale(string $locale): ?Language {
		return $this->getLanguageRepository()->findByLocale($locale);
	}
	
	public function getDefaultLocaleLanguage(): ?Language {
		return $this->getLanguageByLocale($this->getDefaultLocale());
	}
	
	public function getDefaultLocale(): string {
		return DefaultContextService::DEFAULT_LANGUAGE;
	}
	
}
