<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Exception;

use Throwable;

class InvalidStatusException extends UserException {
	
	public function __construct(string $message = 'invalidStatus', array $parameters = [], ?string $domain = null, ?Throwable $previous = null) {
		parent::__construct($message, $parameters, $domain, $previous);
	}
	
}
