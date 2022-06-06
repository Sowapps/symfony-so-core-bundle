<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Form\Workflow;

use JetBrains\PhpStorm\ArrayShape;
use Sowapps\SoCoreBundle\Core\Form\AppForm;
use Sowapps\SoCoreBundle\Entity\AbstractUser;
use Symfony\Component\HttpFoundation\Request;

class WorkflowStep {
	
	protected Workflow $workflow;
	
	protected int $index;
	
	protected string $key;
	
	protected string $route;
	
	protected string $template;
	
	protected bool $done = false;
	
	protected bool $active = false;
	
	protected ?AppForm $form = null;
	
	/**
	 * WorkflowStep constructor
	 *
	 * @param string $key
	 */
	public function __construct(string $key) {
		$this->key = $key;
	}
	
	public function getCurrentUser(): ?AbstractUser {
		return $this->workflow->getController()->getUserService()->getCurrent();
	}
	
	public function getTemplateData(): array {
		return [];
	}
	
	public function getForm(): ?AppForm {
		return $this->form;
	}
	
	#[ArrayShape(['key' => "string", 'done' => "bool", 'active' => "bool"])]
	public function getState(): array {
		return [
			'key'    => $this->getKey(),
			'done'   => $this->isDone(),
			'active' => $this->isActive(),
		];
	}
	
	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}
	
	/**
	 * @return bool
	 */
	public function isDone(): bool {
		return $this->done;
	}
	
	/**
	 * @param bool $done
	 */
	public function setDone(bool $done): void {
		$this->done = $done;
	}
	
	/**
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->active;
	}
	
	/**
	 * @param bool $active
	 */
	public function setActive(bool $active): void {
		$this->active = $active;
	}
	
	public function loadState(array $stepState): void {
		$this->setDone($stepState['done']);
		$this->setActive($stepState['active']);
	}
	
	public function __toString() {
		return $this->getKey();
	}
	
	/** @noinspection PhpUnusedParameterInspection */
	public function prepare(Request $request, $data): bool {
		return true;
	}
	
	/** @noinspection PhpUnusedParameterInspection */
	public function processForm(Request $request, ?array $data): bool {
		return false;
	}
	
	/**
	 * @return Workflow
	 */
	public function getWorkflow(): Workflow {
		return $this->workflow;
	}
	
	/**
	 * @param Workflow $workflow
	 */
	public function setWorkflow(Workflow $workflow): void {
		$this->workflow = $workflow;
	}
	
	/**
	 * @return string
	 */
	public function getTemplate(): string {
		return $this->template;
	}
	
	/**
	 * @param string $template
	 */
	public function setTemplate(string $template): void {
		$this->template = $template;
	}
	
	/**
	 * @return string
	 */
	public function getRoute(): string {
		return $this->route;
	}
	
	/**
	 * @param string $route
	 */
	public function setRoute(string $route): void {
		$this->route = $route;
	}
	
	/**
	 * @return int
	 */
	public function getIndex(): int {
		return $this->index;
	}
	
	/**
	 * @param int $index
	 */
	public function setIndex(int $index): void {
		$this->index = $index;
	}
	
	/**
	 * @return int
	 */
	public function getNumber(): int {
		return $this->index + 1;
	}
	
}
