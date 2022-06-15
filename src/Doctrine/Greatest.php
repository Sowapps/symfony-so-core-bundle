<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace App\Doctrine;

/**
 * https://mariadb.com/docs/reference/mdb/functions/GREATEST/
 */
class Greatest extends AbstractSpatialDQLFunction {
	
	protected string $functionName = 'GREATEST';
	
	protected int $minArgs = 2;
	
}
