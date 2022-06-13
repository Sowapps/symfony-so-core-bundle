<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace App\Core\Entity;

use DateTimeInterface;

class TimeRange implements TimeBoundable {
	
	private DateTimeInterface $start;
	
	private DateTimeInterface $end;
	
	/**
	 * TimeRange constructor
	 *
	 * @param DateTimeInterface $start
	 * @param DateTimeInterface $end
	 */
	public function __construct(DateTimeInterface $start, DateTimeInterface $end) {
		$this->start = $start;
		$this->end = $end;
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
