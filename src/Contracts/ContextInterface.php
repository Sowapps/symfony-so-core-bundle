<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Contracts;

use Sowapps\SoCore\Core\Locale\LocaleFormatter;
use Sowapps\SoCore\Entity\Language;

interface ContextInterface {
	
	function getEnvironmentName(): string;
	
	function getEnvironmentLevel(): string;
	
	function getEnvironmentId(): string;
	
	function getApplicationLetter(): string;
	
	public function setCurrentLanguage(Language $language, CurrencyInterface $currency);
	
	function getCurrentLanguage(): ?Language;
	
	function getLocaleFormatter(): LocaleFormatter;
	
	function isDebug(): bool;
	
}
