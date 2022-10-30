<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Exception;

use RuntimeException;
use Throwable;

class UserException extends RuntimeException {
	
	static int $DEFAULT_CODE = 500;
	
	private array $parameters;
	
	private ?string $domain;
	
	/**
	 * UserException constructor
	 *
	 * @param string $message
	 * @param array $parameters
	 * @param string|null $domain
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message, array $parameters = [], ?string $domain = null, ?Throwable $previous = null, ?int $code = null) {
		parent::__construct($message, $code ?? static::$DEFAULT_CODE, $previous);
		
		$this->parameters = $parameters;
		$this->domain = $domain;
	}
	
	/**
	 * @param string|null $domain
	 * @return array
	 */
	public function asArray(?string $domain = null): array {
		return [
			'message'    => $this->getMessage(),
			'parameters' => $this->getParameters(),
			'domain'     => $domain ?? $this->getDomain(),
		];
	}
	
	/**
	 * @return array
	 */
	public function getParameters(): array {
		return $this->parameters;
	}
	
	/**
	 * @return string|null
	 */
	public function getDomain(): ?string {
		return $this->domain;
	}
	
}
