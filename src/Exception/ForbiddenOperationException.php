<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Exception;

class ForbiddenOperationException extends UserException {
	
	static int $DEFAULT_CODE = 403;
	
}
