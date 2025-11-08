<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Doctrine;

/**
 * https://mariadb.com/docs/reference/mdb/functions/LEAST/
 */
class LN extends AbstractSpatialDQLFunction {
	
	protected string $functionName = 'LN';
	
	protected int $minArgs = 1;
	
	protected ?int $maxArgs = 1;
	
}
