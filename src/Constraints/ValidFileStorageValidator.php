<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Constraints;

use Sowapps\SoCore\Service\FileService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidFileStorageValidator extends ConstraintValidator {
	
	public function __construct(
		private readonly FileService $fileService,
	) {
	}
	
	public function validate(mixed $value, Constraint $constraint): void {
		if( $value === null || $value === '' ) {
			return;
		}
		
		if( !$constraint instanceof ValidFilePurpose ) {
			return;
		}
		
		$value = (string)$value;
		if( !in_array($value, $this->fileService->getAllStorages(), true) ) {
			$this->context
				->buildViolation($constraint->message)
				->setParameter('{{ value }}', $value)
				->addViolation();
		}
	}
}
