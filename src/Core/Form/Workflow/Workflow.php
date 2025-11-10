<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Form\Workflow;

use Sowapps\SoCore\Core\Controller\AbstractController;
use Sowapps\SoCore\Core\Form\AppForm;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class Workflow {
	
	protected AbstractController $controller;
	
	protected WorkflowStep $activeStep;
	
	protected array $steps = [];
	
	protected array $stepKeys = [];
	
	private $data = null;
	
	private Request $request;
	
	public function isAvailableStep(?string $stepKey): bool {
		/** @var WorkflowStep $step */
		// True if same key
		// Stop if key not found and passed a not done step
		foreach( $this->steps as $step ) {
			if( $step->getKey() === $stepKey ) {
				return true;
			}
			if( !$step->isDone() ) {
				return false;
			}
		}
		
		return false;
	}
	
	public function getLastAvailableStep(): WorkflowStep {
		/** @var WorkflowStep $step */
		foreach( $this->steps as $step ) {
			if( !$step->isDone() ) {
				return $step;
			}
		}
		
		return $step;
	}
	
	public function getTemplateData(): array {
		return $this->activeStep->getTemplateData();
	}
	
	public function prepare(Request $request, $data): bool {
		$this->setRequest($request);
		$this->setData($data);
		
		return $this->activeStep->prepare($this->request, $this->data);
	}
	
	public function processForm(): bool {
		return $this->activeStep->processForm($this->request, $this->data);
	}
	
	/**
	 * @return AbstractController
	 */
	public function getController(): AbstractController {
		return $this->controller;
	}
	
	/**
	 * Workflow constructor
	 *
	 * @param AbstractController $controller
	 */
	protected function setController(AbstractController $controller) {
		$this->controller = $controller;
	}
	
	/**
	 * @return FormView
	 */
	public function getFormView(): ?FormView {
		$form = $this->getForm();
		
		return $form ? $form->createView() : null;
	}
	
	/**
	 * @return AppForm|null
	 */
	public function getForm(): ?AppForm {
		return $this->activeStep->getForm();
	}
	
	/**
	 * @return Request
	 */
	public function getRequest(): Request {
		return $this->request;
	}
	
	/**
	 * @param Request $request
	 */
	protected function setRequest(Request $request): void {
		$this->request = $request;
	}
	
	/**
	 * @return mixed|null
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * @param null $data
	 */
	protected function setData($data): void {
		$this->data = $data;
	}
	
	public function getState(): array {
		$state = ['activeStep' => $this->activeStep->getKey()];
		$state['steps'] = array_map(fn(WorkflowStep $step) => $step->getState(), $this->steps);
		
		return $state;
	}
	
	public function loadState(?array $state) {
		$this->setActiveStep($this->getStepByKey($state['activeStep']));
		foreach( $state['steps'] as $stepState ) {
			$step = $this->getStepByKey($stepState['key']);
			$step->loadState($stepState);
		}
	}
	
	public function getStepByKey(string $step): WorkflowStep {
		return $this->stepKeys[$step];
	}
	
	public function getPreviousStep(?WorkflowStep $step = null): ?WorkflowStep {
		if( !$step ) {
			$step = $this->getActiveStep();
		}
		
		return $this->isFirstStep($step) ? null : $this->steps[$step->getIndex() - 1];
	}
	
	/**
	 * @return WorkflowStep|null
	 */
	public function getActiveStep(): ?WorkflowStep {
		return $this->activeStep;
	}
	
	/**
	 * @param WorkflowStep $step
	 */
	public function setActiveStep(WorkflowStep $step): void {
		if( isset($this->activeStep) ) {
			$this->activeStep->setActive(false);
		}
		$this->activeStep = $step;
		$step->setActive(true);
	}
	
	public function isFirstStep(?WorkflowStep $step = null): bool {
		if( !$step ) {
			$step = $this->getActiveStep();
		}
		
		return !$step->getIndex();
	}
	
	public function getNextStep(?WorkflowStep $step = null): ?WorkflowStep {
		if( !$step ) {
			$step = $this->getActiveStep();
		}
		
		return $this->isLastStep($step) ? null : $this->steps[$step->getIndex() + 1];
	}
	
	public function isLastStep(?WorkflowStep $step = null): bool {
		if( !$step ) {
			$step = $this->getActiveStep();
		}
		
		return $step->getIndex() === (count($this->steps) - 1);
	}
	
	public function addStep(WorkflowStep $step) {
		$step->setWorkflow($this);
		$step->setIndex(count($this->steps));
		$this->steps[$step->getIndex()] = $step;
		$this->stepKeys[$step->getKey()] = $step;
		// If no step, first one is now the current
		if( !isset($this->activeStep) ) {
			$this->activeStep = $step;
		}
	}
	
	/**
	 * @return array
	 */
	public function getSteps(): array {
		return $this->steps;
	}
	
}
