<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Locale\Currency;

use Sowapps\SoCore\Contracts\CurrencyInterface;

class EuroCurrency implements CurrencyInterface {
	
	function getCode(): string {
		return 'EUR';
	}
	
}
