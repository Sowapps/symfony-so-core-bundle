<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Exception;

use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;
use Throwable;

class UserException extends RuntimeException {
	
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
	public function __construct(string $message, array $parameters = [], ?string $domain = null, ?Throwable $previous = null) {
		parent::__construct($message, 0, $previous);
		
		$this->parameters = $parameters;
		$this->domain = $domain;
	}
	
	/**
	 * @param string|null $domain
	 * @return array
	 */
	#[ArrayShape(['message' => "string", 'parameters' => "array", 'domain' => "null|string"])]
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
