<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Doctrine;

/**
 * https://mariadb.com/kb/en/ifnull/
 */
class IfNull extends AbstractSpatialDQLFunction {
	
	protected string $functionName = 'IFNULL';
	
	protected int $minArgs = 2;
	
	protected ?int $maxArgs = 2;
	
}
