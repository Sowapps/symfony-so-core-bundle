<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Form\Workflow;

use Sowapps\SoCoreBundle\Exception\UserException;
use Throwable;

/**
 * Class WorkflowStepException
 * Exception about missing step data
 *
 * @package Sowapps\SoCoreBundle\Core\Form\Workflow
 */
class WorkflowStepException extends UserException {
	
	private WorkflowStep $step;
	
	private string $requireStep;
	
	public function __construct(string $message, WorkflowStep $step, string $requireStep, ?Throwable $previous = null) {
		parent::__construct($message, [], null, $previous);
		
		$this->step = $step;
		$this->requireStep = $requireStep;
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
