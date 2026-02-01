<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Logger\Parser;

use DateTime;
use DateTimeImmutable;
use Exception;
use Monolog\Level;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

readonly class LogParser {
	
	public function __construct(protected TranslatorInterface $translator) {
	}
	
	/**
	 * @throws Exception
	 */
	public function parseFileLogEntries(string $url, callable|int $levelMinOrFilter = 0): array {
		if( !file_exists($url) ) {
			return [[], 0];
		}
		$stream = fopen($url, 'r');
		$logEntries = [];
		$otherCount = 0;
		$line = 0;
		while( ($row = fgets($stream)) !== false ) {
			$line++;
			if( !$row ) {
				continue;
			}
			// If parsing has any error, we throw it to the user/developer asap
			$logEntry = $this->parseRowLog($row, $date);
			if( is_callable($levelMinOrFilter) ) {
				$keep = call_user_func($levelMinOrFilter, $logEntry);
			} else {
				$keep = $logEntry->getLevel() >= $levelMinOrFilter;
			}
			if( $keep ) {
				if( array_key_exists($logEntry->getGroupKey(), $logEntries) ) {
					// Merge similar entries
					$logEntry = $logEntries[$logEntry->getGroupKey()];
				} else {
					$logEntries[$logEntry->getGroupKey()] = $logEntry;
				}
				$logEntry->addOccurrence($date, $line);
				//				$this->calculateEntry($logEntry);
			} else {
				$otherCount++;
			}
			//			if($line % 10 === 0) {
			//				dump($line);
			//			}
			//			if($line > 100) {
			//				die('INTERRUPT SCRIPT');
			//			}
		}
		
		// Update calculated data of all entries
		foreach( $logEntries as $logEntry ) {
			$this->calculateEntry($logEntry);
		}
		
		return [$logEntries, $otherCount];
	}
	
	public function parseFileLastRows(string $url, int $max): array {
		if( !file_exists($url) ) {
			return [];
		}
		$stream = fopen($url, 'r');
		$lines = [];
		$i = -1;
		while( ($row = fgets($stream)) !== false ) {
			$i++;
			if( !$row ) {
				continue;
			}
			$lines[$i] = $row;
			$oldIndex = $i - $max;
			if( array_key_exists($oldIndex, $lines) ) {
				unset($lines['' . $oldIndex]);
			}
		}
		return $lines;
	}
	
	public function removeFile(string $url): void {
		$fileStream = fopen($url, 'r+');// Open to write, pointer at the beginning of the file
		ftruncate($fileStream, 0);// Erase log file
		fclose($fileStream);
	}
	
	public function removeRows(string $url, array $lines): int {
		return $this->filterRows($url, fn($row, $line) => !in_array($line, $lines, true));
	}
	
	public function removeNonErrorRows(string $url): int {
		return $this->filterRows($url, function ($row) {
			$logEntry = $this->parseRowLog($row);
			return $logEntry->getLevel() >= Level::Error->value;
		});
	}
	
	/**
	 * @param string $url Local file path to process
	 * @param callable $filter Filter returns true to keep line
	 * @return int
	 */
	public function filterRows(string $url, callable $filter): int {
		if( !file_exists($url) ) {
			// For an operation, this is an error
			throw new ValueError('File not found: ' . $url);
		}
		$tempStream = tmpfile();
		$fileStream = fopen($url, 'r');// Open to read-only, pointer at the beginning of the file
		$line = 0;
		//		$keep = 0;
		$removed = 0;
		try {
			// Loop on all rows
			while( ($row = fgets($fileStream)) !== false ) {
				$line++;
				if( call_user_func($filter, $row, $line) === true ) {
					fwrite($tempStream, $row);
					//					$keep++;
				} else {
					$removed++;
				}
			}
			rewind($tempStream);// Reset temp file pointer
			fclose($fileStream);// Close read-only stream
			$fileStream = fopen($url, 'r+');// Open to write, pointer at the beginning of the file
			ftruncate($fileStream, 0);// Erase log file
			stream_copy_to_stream($tempStream, $fileStream);// Copy temp to the log file
		} finally {
			if( is_resource($fileStream) ) {
				// This is a valid resource in case of re-opening the file in writing mode failed
				fclose($fileStream);
			}
			fclose($tempStream);// Then PHP automatically deletes the file
		}
		
		return $removed;
	}
	
	public function calculateEntry(LogEntry $logEntry): void {
		$minDate = $maxDate = null;
		$occurrences = $logEntry->getOccurrences();
		foreach( $occurrences as $occurrence ) {
			if( !$minDate || $occurrence[0] < $minDate ) {
				$minDate = $occurrence[0];
			}
			if( !$maxDate || $occurrence[0] > $maxDate ) {
				$maxDate = $occurrence[0];
			}
		}
		$occurrenceCount = count($occurrences);
		unset($occurrences);
		$deltaDay = $minDate->diff($maxDate)->format('%a') + 1;
		// Average delay of days between two occurrences
		$averageDays = $deltaDay / $occurrenceCount;
		// Difference in days between the last occurrence and now
		$lastToNowDays = $maxDate->diff(new DateTime())->format('%a');
		
		if( $lastToNowDays > max(2, min(3 * $averageDays, 14)) ) {
			// 3 x average day between error is solved
			// Urgents are waiting for 2 days at least
			// Rares don't wait for more than 2 weeks
			$status = LogEntry::STATUS_SOLVED;
			
		} else if( $averageDays > 5 ) {
			$status = LogEntry::STATUS_RARE;
			
		} else if( $averageDays <= 1 && $occurrenceCount > 2 ) {
			$status = LogEntry::STATUS_FREQUENTLY;
			
		} else {
			$status = LogEntry::STATUS_OCCASIONALLY;
		}
		
		$logEntry->setCalculated(
			$status,
			$averageDays,
			$lastToNowDays,
			$this->translator->trans('page.admin-log-view.status.' . $status) . ' (' . $averageDays . ', ' . $lastToNowDays . ')'
		);
	}
	
	public function parseRowLog(string $row, ?DateTimeImmutable &$date = null): LogEntry {
		// TODO Fix regex in some cases
		//		if( !preg_match('#^\[([^\]]+)\] ([^\.\s]+)\.([^\.\s]+): (.+) ([\[\{].*[\}\]]) ([\[\{].*[\}\]])$#', $row, $matches) ) {
		if( !preg_match('#^\[([^\]]+)\] ([^\.\s]+)\.([^\.\s]+): (.+) ([\[\{].*[\}\]]) ([\[\{].*[\}\]])$#', $row, $matches) ) {
			//			dd($row);
			throw new Exception('Unable to parse row');
		}
		//		dump($row, $matches);
		$date = new DateTimeImmutable($matches[1]);
		$level = Level::fromName($matches[3]);
		
		// We ignore context & data for now
		return new LogEntry(
			$matches[2],
			$level->value,
			str_replace('Uncaught PHP Exception ', '', $matches[4]),
			null
		);
	}
	
}
