<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Form\Workflow;

use Sowapps\SoCore\Exception\UserException;
use Throwable;

/**
 * Class WorkflowStepException
 * Exception about missing step data
 *
 * @package Sowapps\SoCore\Core\Form\Workflow
 */
class WorkflowStepException extends UserException {
	
	public function __construct(string $message, private readonly WorkflowStep $step, private readonly string $requireStep, ?Throwable $previous = null) {
		parent::__construct($message, [], null, $previous);
	}
	
	/**
	 * @return WorkflowStep
	 */
	public function getStep(): WorkflowStep {
		return $this->step;
	}
	
	/**
	 * @return string
	 */
	public function getRequireStep(): string {
		return $this->requireStep;
	}
	
}
