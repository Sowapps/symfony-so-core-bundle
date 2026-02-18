<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Exception;

use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @deprecated Use Symfony Exception => ValidationFailedException
 */
class ValidationException extends InvalidArgumentException implements JsonSerializable {
	
	public function __construct(
		private readonly ConstraintViolationListInterface $errors
	) {
		parent::__construct();
	}
	
	public function getErrors(): ConstraintViolationListInterface {
		return $this->errors;
	}
	
	public function jsonSerialize(): array {
		$errors = [];
		foreach( $this->getErrors() as $error ) {
			$parameters = [];
			foreach( $error->getParameters() as $key => $value ) {
				$key = preg_replace('#\{\{ ?(.+) ?\}\}#', '$1', $key);
				$parameters[$key] = $value;
			}
			$errors[] = [
				'path'       => $error->getPropertyPath(),
				'message'    => $error->getMessage(),
				'parameters' => $parameters,
				'constraint' => $error->getConstraint()::class,
			];
		}
		
		return [
			'errors' => $errors,
		];
	}
	
}
