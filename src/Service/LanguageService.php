<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use InvalidArgumentException;
use RuntimeException;
use Sowapps\SoCore\Entity\Language;
use Sowapps\SoCore\Repository\LanguageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageService extends AbstractEntityService {
	protected ?Language $activeLanguage = null;
	
	public function __construct(
		protected readonly RouterInterface     $router,
		protected readonly TranslatorInterface $translator,
		private readonly RequestStack          $requestStack,
		protected readonly LanguageRepository  $languageRepository
	)
    {
    }
	
	public function formatMonth(int $month): string {
		if( $month < 1 || $month > 12 ) {
			throw new InvalidArgumentException('Month must be between 1 and 12.');
		}
		
		$date = new DateTimeImmutable(sprintf('2020-%02d-01', $month));
		$locale = $this->translator->getLocale();
		
		// LLLL = nom du mois “standalone” (ex: "janvier" en français)
		$fmt = new IntlDateFormatter(
			$locale,
			IntlDateFormatter::NONE,
			IntlDateFormatter::NONE,
			'UTC',
			IntlDateFormatter::GREGORIAN,
			'LLLL'
		);
		
		return $fmt->format($date);
	}
	
	/**
	 * @param DateTimeInterface|DateInterval $date
	 * @param string $format
	 * @return string
	 */
	public function formatDate(DateTimeInterface|DateInterval $date, string $format): string {
		$format = $this->translator->trans('date.format.' . $format);
		$customCharFormats = ['l' => 'date.day.', 'F' => 'date.month.'];
		$customChars = array_keys($customCharFormats);
		// Escape our custom characters to not be handled by DateTime::format
		$format = strtr($format, array_combine($customChars, array_map(fn($char) => '#\\' . $char, $customChars)));
		// Ignore escaped characters, revert change
		$format = strtr($format, array_combine(array_map(fn($char) => '\\#\\' . $char, $customChars), array_map(fn($char) => '\\' . $char, $customChars)));
		$dateText = $date->format($format);
		if( $date instanceof DateTimeInterface ) {
			// Custom still not formatted
			// We replace our format characters by the translated string
			$dateText = strtr($dateText, array_combine(array_map(fn($char) => '#' . $char, $customChars), array_map(fn($char, $charFormat) =>
                // e.g. Get format('l') to translate date.day.friday
                $this->translator->trans($charFormat . strtolower($date->format($char))), $customChars, $customCharFormats)));
		}// Else could format DateInterval
		
		return $dateText;
	}
	
	public function getActiveLanguage(): Language {
		if( !$this->activeLanguage ) {
			$request = $this->requestStack->getCurrentRequest();
			$locale = $request?->getLocale();
			
			$this->activeLanguage = $this->getLanguageByLocale($locale) ?? $this->getDefaultLocaleLanguage();
		}
		return $this->activeLanguage;
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
		return $this->languageRepository;
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
			if( strlen((string) $locale) < 5 ) {
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
		if( strlen((string) $httpLocale) > 7 ) {
			[$httpLocale,] = explode(';', (string) $httpLocale);
		}
		if( strlen((string) $httpLocale) > 3 ) {
			$httpLocale = strtr($httpLocale, '-', '_');
		}
		
		return $httpLocale;
	}
	
	public function getLanguageByLocale(?string $locale): ?Language {
		if( !$locale ) {
			return null;
		}
		return $this->getLanguageRepository()->findByLocale($locale);
	}
	
	public function getDefaultLocaleLanguage(): ?Language {
		$language = $this->getLanguageByLocale($this->getDefaultLocale());
		if( !$language ) {
			throw new RuntimeException('The default locale has no registered language');
		}
		
		return $language;
	}
	
	public function getDefaultLocale(): string {
		return DefaultContextService::DEFAULT_LANGUAGE;
	}
	
}
