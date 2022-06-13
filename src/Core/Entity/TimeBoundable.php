<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace App\Core\Entity;


use DateTimeInterface;

interface TimeBoundable {
	
	function getStart(): ?DateTimeInterface;
	
	function getEnd(): ?DateTimeInterface;
	
}
