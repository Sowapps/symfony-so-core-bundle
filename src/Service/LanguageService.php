<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use DateTimeInterface;
use Sowapps\SoCoreBundle\Entity\Language;
use Sowapps\SoCoreBundle\Repository\LanguageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageService extends AbstractEntityService {
	
	protected RouterInterface $router;
	
	protected TranslatorInterface $translator;
	
	protected ?string $currentCountry = null;
	
	/**
	 * LanguageService constructor
	 *
	 * @param RouterInterface $router
	 * @param TranslatorInterface $translator
	 */
	public function __construct(RouterInterface $router, TranslatorInterface $translator) {
		$this->router = $router;
		$this->translator = $translator;
	}
	
	/**
	 * @param DateTimeInterface $date
	 * @param string $format
	 * @return string
	 */
	public function formatDate($date, string $format): string {
		$format = $this->translator->trans('date.format.' . $format);
		$customCharFormats = ['l' => 'date.day.', 'F' => 'date.month.'];
		$customChars = array_keys($customCharFormats);
		// Escape our custom characters to not be handled by DateTime::format
		$format = strtr($format, array_combine($customChars, array_map(function ($char) {
			return '#\\' . $char;
		}, $customChars)));
		// Ignore escaped characters, revert change
		$format = strtr($format, array_combine(array_map(function ($char) {
			return '\\#\\' . $char;
		}, $customChars), array_map(function ($char) {
			return '\\' . $char;
		}, $customChars)));
		$dateText = $date->format($format);
		if( $date instanceof DateTimeInterface ) {
			// Custom still not formatted
			// We replace our format characters by the translated string
			$dateText = strtr($dateText, array_combine(array_map(function ($char) {
				return '#' . $char;
			}, $customChars), array_map(function ($char, $charFormat) use ($date) {
				// e.g. Get format('l') to translate date.day.friday
				return $this->translator->trans($charFormat . strtolower($date->format($char)));
			}, $customChars, $customCharFormats)));
		}// Else could format DateInterval
		
		return $dateText;
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
		/** @noinspection PhpIncompatibleReturnTypeInspection */
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
