<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace App\Doctrine;

/**
 * https://mariadb.com/docs/reference/mdb/functions/LEAST/
 */
class Least extends AbstractSpatialDQLFunction {
	
	protected string $functionName = 'LEAST';
	
	protected int $minArgs = 2;
	
}
