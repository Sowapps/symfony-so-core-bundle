<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Entity;

use DateTimeInterface;

class TimeRange implements TimeBoundable {
	
	/**
	 * TimeRange constructor
	 *
	 * @param DateTimeInterface $start
	 * @param DateTimeInterface $end
	 */
	public function __construct(private readonly DateTimeInterface $start, private readonly DateTimeInterface $end)
    {
    }
	
	/**
	 * @return DateTimeInterface
	 */
	public function getStart(): DateTimeInterface {
		return $this->start;
	}
	
	/**
	 * @return DateTimeInterface
	 */
	public function getEnd(): DateTimeInterface {
		return $this->end;
	}
	
}
