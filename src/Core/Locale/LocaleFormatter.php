<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Locale;

use NumberFormatter;
use Sowapps\SoCore\Contracts\CurrencyInterface;
use Sowapps\SoCore\Entity\Language;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleFormatter {
	
	private readonly NumberFormatter $currencyFormatter;
	
	/**
	 * LocaleFormatter constructor
	 *
	 * @param Language $language
	 * @param CurrencyInterface $currency
	 */
	public function __construct(private readonly TranslatorInterface $translator, private readonly Language $language, private readonly CurrencyInterface $currency) {
		$this->currencyFormatter = new NumberFormatter($this->language->getLocale(), NumberFormatter::CURRENCY);
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
	
	public function formatFileSize(array $size): string {
		$unit = $this->translator->trans('format.fileSize.short.' . $size[1]);
		
		return $this->translator->trans('format.unit', ['value' => $size[0], 'unit' => $unit]);
	}
	
}
