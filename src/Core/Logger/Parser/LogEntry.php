<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Logger\Parser;

use DateTimeInterface;
use LogicException;
use Monolog\Level;
use Psr\Log\LogLevel;
use Symfony\Component\Serializer\Attribute\Ignore;

class LogEntry {
	
	const STATUS_RARE = 'rare';
	const STATUS_OCCASIONALLY = 'occasionally';
	const STATUS_FREQUENTLY = 'frequently';
	const STATUS_SOLVED = 'solved';
	
	protected string $domain;
	
	protected int $level;
	
	protected string $message;
	
	protected ?array $data = null;
	
	protected array $occurrences = [];
	
	/**
	 * Average delay of days between two occurrences
	 */
	protected ?float $averageDays = null;
	
	/**
	 * Difference in days between the last occurrence and now
	 */
	protected ?int $lastToNowDays = null;
	
	protected ?string $status = null;
	protected ?string $statusText = null;
	
	/**
	 * LogRow constructor
	 *
	 * @param string $domain
	 * @param int $level
	 * @param string $message
	 * @param array|null $data
	 */
	public function __construct(string $domain, int $level, string $message, ?array $data = null) {
		$this->domain = $domain;
		$this->level = $level;
		$this->message = $message;
		$this->data = $data;
	}
	
	public function setCalculated(string $status, float $averageDays, int $lastToNowDays, string $statusText): LogEntry {
		if( $this->status ) {
			throw new LogicException('Status already set');
		}
		$this->status = $status;
		$this->averageDays = $averageDays;
		$this->lastToNowDays = $lastToNowDays;
		$this->statusText = $statusText;
		
		return $this;
	}
	
	#[Ignore]
	public function getLines(): array {
		return array_filter(array_map(function ($occurrence) {
			return $occurrence[1];
		}, $this->occurrences));
	}
	
	public function addOccurrence(DateTimeInterface $date, $line): void {
		$this->occurrences[] = [$date, $line];
	}
	
	public function getGroupKey(): string {
		return (string)crc32($this->domain . '-' . $this->level . '-' . $this->message . '-' . json_encode($this->data));
	}
	
	public function getAverageDays(): ?float {
		return $this->averageDays;
	}
	
	public function getLastToNowDays(): ?int {
		return $this->lastToNowDays;
	}
	
	public function getStatus(): ?string {
		return $this->status;
	}
	
	public function getStatusText(): ?string {
		return $this->statusText;
	}
	
	public function getOccurrences(): array {
		return $this->occurrences;
	}
	
	public function isError(): bool {
		return $this->level >= Level::Error->value;
	}
	
	public function getDomain(): string {
		return $this->domain;
	}
	
	public function getLevel(): int {
		return $this->level;
	}
	
	public function getLevelName(): string {
		return Level::from($this->level)->getName();
	}
	
	public function getMessage(): string {
		return $this->message;
	}
	
	public function getData(): ?array {
		return $this->data;
	}
	
}
