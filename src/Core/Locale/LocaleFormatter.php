<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Locale;

use NumberFormatter;
use Sowapps\SoCoreBundle\Contracts\CurrencyInterface;
use Sowapps\SoCoreBundle\Entity\Language;

class LocaleFormatter {
	
	private Language $language;
	
	private CurrencyInterface $currency;
	
	private NumberFormatter $currencyFormatter;
	
	/**
	 * LocaleFormatter constructor
	 *
	 * @param Language $language
	 * @param CurrencyInterface $currency
	 */
	public function __construct(Language $language, CurrencyInterface $currency) {
		$this->language = $language;
		$this->currency = $currency;
		$this->currencyFormatter = new NumberFormatter($language->getLocale(), NumberFormatter::CURRENCY);
	}
	
	public function getNumberFormatter(int $style): NumberFormatter {
		return new NumberFormatter($this->getLocale(), $style);
	}
	
	public function getLocale(): string {
		return $this->language->getLocale();
	}
	
	public function formatCurrency(float $amount, CurrencyInterface $currency = null): string {
		$currency ??= $this->currency;
		
		return $this->getCurrencyFormatter()->formatCurrency($amount, $currency->getCode());
	}
	
	/**
	 * @return NumberFormatter
	 */
	public function getCurrencyFormatter(): NumberFormatter {
		return $this->currencyFormatter;
	}
	
}
