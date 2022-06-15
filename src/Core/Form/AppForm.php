<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Form;

use IteratorAggregate;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Traversable;

/**
 * Class AppForm
 * This class AppForm is a tool to handle forms, not a form type
 *
 * @package Sowapps\SoCoreBundle\Form
 */
class AppForm implements FormInterface, IteratorAggregate {
	
	protected ?string $domain;
	
	private FormInterface $form;
	
	private array $successes = [];
	
	/**
	 * AppForm constructor
	 *
	 * @param FormInterface $form
	 * @param string|null $domain
	 */
	public function __construct(FormInterface $form, ?string $domain = null) {
		$this->form = $form;
		$this->domain = $domain;
	}
	
	/**
	 * @param Request $request
	 * @return bool
	 */
	public function isValidRequest(Request $request): bool {
		return $this->isSubmittedRequest($request) && $this->form->isValid();
	}
	
	/**
	 * @param Request $request
	 * @return bool
	 */
	public function isSubmittedRequest(Request $request): bool {
		$this->form->handleRequest($request);
		
		return $this->form->isSubmitted();
	}
	
	/**
	 * @param array $errors
	 * @return AppForm
	 */
	public function setErrors(array $errors): static {
		foreach( $errors as $error ) {
			$this->addError($error);
		}
		
		return $this;
	}
	
	/**
	 * @return array
	 */
	public function getSuccesses(): array {
		return $this->successes;
	}
	
	/**
	 * @param array $successes
	 * @return AppForm
	 */
	public function setSuccesses(array $successes): static {
		$this->successes = $successes['success'] ?? $successes;
		
		return $this;
	}
	
	/**
	 * @param string $message #TranslationKey to translate
	 * @param array $params
	 * @param string|null $domain #TranslationDomain in which to look for
	 */
	public function addSuccess(string $message, array $params = [], ?string $domain = null) {
		$this->successes[] = [$message, $params, $domain];
	}
	
	public function createView(FormView $parent = null): FormView {
		$view = $this->form->createView($parent);
		$view->vars['successes'] = $this->successes;
		$view->vars['success_domain'] = $this->domain;
		
		return $view;
	}
	
	/**
	 * @return string|null
	 */
	public function getDomain(): ?string {
		return $this->domain;
	}
	
	/**
	 * @param string|null $domain
	 */
	public function setDomain(?string $domain): void {
		$this->domain = $domain;
	}
	
	public function offsetExists($offset): bool {
		return $this->form->offsetExists($offset);
	}
	
	public function offsetGet($offset): FormInterface {
		return $this->form->offsetGet($offset);
	}
	
	public function offsetSet($offset, $value): void {
		$this->form->offsetSet($offset, $value);
	}
	
	public function offsetUnset($offset): void {
		$this->form->offsetUnset($offset);
	}
	
	public function count(): int {
		return $this->form->count();
	}
	
	public function setParent(FormInterface $parent = null): static {
		$this->form->setParent($parent);
		
		return $this;
	}
	
	public function getParent(): ?FormInterface {
		return $this->form->getParent();
	}
	
	public function add($child, string $type = null, array $options = []): static {
		$this->form->add($child, $type, $options);
		
		return $this;
	}
	
	public function get(string $name): FormInterface {
		return $this->form->get($name);
	}
	
	public function has(string $name): bool {
		return $this->form->has($name);
	}
	
	public function remove(string $name): static {
		$this->form->remove($name);
		
		return $this;
	}
	
	public function all(): array {
		return $this->form->all();
	}
	
	public function getErrors(bool $deep = false, bool $flatten = true): FormErrorIterator {
		return $this->form->getErrors($deep, $flatten);
	}
	
	public function setData($modelData): static {
		$this->form->setData($modelData);
		
		return $this;
	}
	
	public function getData(): mixed {
		return $this->form->getData();
	}
	
	public function getNormData(): mixed {
		return $this->form->getNormData();
	}
	
	public function getViewData(): mixed {
		return $this->form->getViewData();
	}
	
	public function getExtraData(): array {
		return $this->form->getExtraData();
	}
	
	public function getConfig(): FormConfigInterface {
		return $this->form->getConfig();
	}
	
	public function isSubmitted(): bool {
		return $this->form->isSubmitted();
	}
	
	public function getName(): string {
		return $this->form->getName();
	}
	
	public function getPropertyPath(): ?PropertyPathInterface {
		return $this->form->getPropertyPath();
	}
	
	public function addError(FormError $error): static {
		$this->form->addError($error);
		
		return $this;
	}
	
	public function isValid(): bool {
		return $this->form->isValid();
	}
	
	public function isRequired(): bool {
		return $this->form->isRequired();
	}
	
	public function isDisabled(): bool {
		return $this->form->isDisabled();
	}
	
	public function isEmpty(): bool {
		return $this->form->isEmpty();
	}
	
	public function isSynchronized(): bool {
		return $this->form->isSynchronized();
	}
	
	public function getTransformationFailure(): ?TransformationFailedException {
		return $this->form->getTransformationFailure();
	}
	
	public function initialize(): static {
		$this->form->initialize();
		
		return $this;
	}
	
	public function handleRequest($request = null): static {
		$this->form->handleRequest($request);
		
		return $this;
	}
	
	public function submit($submittedData, bool $clearMissing = true): static {
		$this->form->submit($submittedData, $clearMissing);
		
		return $this;
	}
	
	public function getRoot(): FormInterface {
		return $this->form->getRoot();
	}
	
	public function isRoot(): bool {
		return $this->form->isRoot();
	}
	
	public function getIterator(): Traversable|array {
		return $this->form->getIterator();
	}
	
}
