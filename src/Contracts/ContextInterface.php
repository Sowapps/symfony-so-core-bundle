<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Contracts;

use Sowapps\SoCoreBundle\Core\Locale\LocaleFormatter;
use Sowapps\SoCoreBundle\Entity\Language;

interface ContextInterface {
	
	function getEnvironmentName(): string;
	
	function getEnvironmentLevel(): string;
	
	function getEnvironmentId(): string;
	
	function getApplicationLetter(): string;
	
	public function setDefaultLanguage();
	
	public function setCurrentLanguage(Language $language, CurrencyInterface $currency);
	
	function getCurrentLanguage(): ?Language;
	
	function getLocaleFormatter(): LocaleFormatter;
	
	function isDebug(): bool;
	
}
