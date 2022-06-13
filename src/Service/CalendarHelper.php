<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @license https://opensource.org/licenses/MIT
 */

namespace App\Core\Calendar;

use App\Core\Entity\TimeBoundable;
use App\Core\Entity\TimeRange;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CalendarHelper
 * This class is a helper calendar manipulation
 * Very useful to get absolute interval between two dates using specific units
 *
 * @package App\Core\Calendar
 * @see https://www.php.net/manual/fr/class.dateinterval.php
 */
class CalendarHelper {
	
	const UNIT_SET_TIME = 'time';
	
	const UNIT_YEAR = 'y';
	const UNIT_MONTH = 'm';
	const UNIT_DAY = 'd';
	const UNIT_HOUR = 'h';
	const UNIT_MINUTE = 'i';
	const UNIT_SECOND = 's';
	const UNIT_MICROSECOND = 'f';
	
	/**
	 * List of all units in order from tallest to smallest
	 */
	const UNITS = [
		// UNIT => X child units
		// X string get property's value in interval
		// X null stops process
		self::UNIT_YEAR        => 12,
		self::UNIT_MONTH       => 'days',
		self::UNIT_DAY         => 24,
		self::UNIT_HOUR        => 60,
		self::UNIT_MINUTE      => 60,
		self::UNIT_SECOND      => 1,// MS are a fraction of seconds, so MS are in seconds
		self::UNIT_MICROSECOND => null,
	];
	
	protected TranslatorInterface $translator;
	
	/**
	 * CalendarHelper constructor
	 *
	 * @param TranslatorInterface $translator
	 */
	public function __construct(TranslatorInterface $translator) {
		$this->translator = $translator;
	}
	
	public function isOverlapping(TimeBoundable $subject, TimeBoundable $other): bool {
		return $subject->getEnd() > $other->getStart() && $subject->getStart() < $other->getEnd();
	}
	
	public function isLonger(DateInterval $interval1, DateInterval $interval2): bool {
		$now = new DateTime();
		$date1 = (clone $now)->add($interval1);
		$date2 = (clone $now)->add($interval2);
		
		return $date1 > $date2;
	}
	
	public function getStartSegment(DateTimeInterface $date): int {
		// Segment rounded by 5 minutes as ceil
		return ceil($date->format('i') / 5);
	}
	
	public function countSegments(DateTimeInterface $start, DateTimeInterface $end, int $segmentMinutes = 15): int {
		// Segment count by 5 minutes
		$interval = $this->getAbsoluteInterval($start, $end, [CalendarHelper::UNIT_MINUTE]);
		
		return ceil($interval->i / $segmentMinutes);
	}
	
	public function compare(DateTimeInterface $date1, DateTimeInterface $date2): bool {
		return $date1 < $date2 ? -1 : ($date1 > $date2 ? 1 : 0);
	}
	
	public function isSameDate(DateTimeInterface $date1, DateTimeInterface $date2): bool {
		return $this->formatDate($date1) === $this->formatDate($date2);
	}
	
	public function isSameYear(DateTimeInterface $date1, DateTimeInterface $date2): bool {
		return $date1->format('Y') === $date2->format('Y');
	}
	
	/**
	 * System format
	 *
	 * @param DateTimeInterface $date
	 * @return string
	 */
	public function formatDate(DateTimeInterface $date): string {
		return $date->format('Y-m-d');
	}
	
	/**
	 * Smart helper
	 *
	 * @param $interval
	 * @param string|array $units
	 * @return DateInterval
	 */
	public function getIntervalAsAbsolute($interval, $units): DateInterval {
		if( $interval instanceof TimeBoundable ) {
			return $this->getAbsoluteInterval($interval->getStart(), $interval->getEnd(), $units);
		}
		
		return $this->convertToAbsolute($interval, $units);
	}
	
	/**
	 * Get absolute interval between two date specifying the units to use.
	 * Example: Get only hours and minutes between $date1 and date2
	 * <code><?php $calendarHelper->getAbsoluteInterval($date1, $date2, [CalendarHelper::UNIT_HOUR, CalendarHelper::UNIT_MINUTE]) ?></code>
	 *
	 * @param $date1
	 * @param $date2
	 * @param array|string $units
	 * @return DateInterval
	 * @see convertToAbsolute
	 */
	public function getAbsoluteInterval($date1, $date2, $units): DateInterval {
		$date1 = $this->formatToDatetime($date1);
		$date2 = $this->formatToDatetime($date2);
		
		return $this->convertToAbsolute(date_diff($date1, $date2), $units);
	}
	
	/**
	 * @param DateTimeInterface|string|int $date
	 * @return DateTime
	 */
	public function formatToDatetime($date): DateTime {
		if( $date instanceof DateTime ) {
			return $date;
		}
		if( $date instanceof DateTimeImmutable ) {
			return DateTime::createFromImmutable($date);
		}
		if( is_string($date) ) {
			return new DateTime($date);
		}
		
		// int
		return new DateTime('@' . $date);
	}
	
	/**
	 * Get absolute interval from any interval
	 *
	 * @param DateInterval $interval
	 * @param array|string $units
	 * @return DateInterval
	 */
	public function convertToAbsolute(DateInterval $interval, $units): DateInterval {
		if( is_string($units) ) {
			switch( $units ) {
				case self::UNIT_SET_TIME:
					$units = [self::UNIT_HOUR, self::UNIT_MINUTE];
					break;
				default:
					throw new RuntimeException(sprintf('Invalid unit set "%s"', $units));
			}
		}
		$absInterval = new DateInterval('PT0S');
		$absInterval->invert = $interval->invert;
		$parentCounter = 0;
		foreach( self::UNITS as $unit => $childCount ) {
			$unitValue = $parentCounter + $interval->$unit;
			if( in_array($unit, $units) ) {
				// Consume inheritance
				$absInterval->$unit = $unitValue;
				$parentCounter = 0;
			} elseif( $childCount !== null ) {
				// Transmit inheritance
				$parentCounter = is_string($childCount) ? ($interval->$childCount ?: 0) : $unitValue * $childCount;
			}
		}
		
		return $absInterval;
	}
	
	public function getWeekMonday($date = null): DateTime {
		$date = $date ? clone $date : new DateTime();
		
		return $date->modify('Sunday' === $date->format('l') ? 'Monday last week' : 'Monday this week');
	}
	
	public function getWeekRange($date = null): TimeRange {
		$start = $this->getWeekMonday($date);
		
		return new TimeRange($start, (clone $start)->modify('+1 week'));
	}
	
	public function getMonthFirstDay($date = null): DateTime {
		$date = $date ? clone $date : new DateTime();
		
		return $date->modify('first day of this month midnight');
	}
	
	public function getMonthRange($date = null): TimeRange {
		$start = $this->getMonthFirstDay($date);
		
		return new TimeRange($start, (clone $start)->modify('+1 month'));
	}
	
}
