<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace App\Doctrine;

/**
 * https://mariadb.com/kb/en/st_distance_sphere/
 * https://github.com/creof/doctrine2-spatial/blob/4e3d8154a23cbb7216f32b38244dce615db6c28b/lib/CrEOF/Spatial/ORM/Query/AST/Functions/MySql/STDistanceSphere.php
 */
class StDistanceSphere extends AbstractSpatialDQLFunction {
	
	//	protected $platforms = array('mysql');
	protected string $functionName = 'ST_Distance_Sphere';
	
	protected int $minArgs = 2;
	
	protected ?int $maxArgs = 2;
	
}
