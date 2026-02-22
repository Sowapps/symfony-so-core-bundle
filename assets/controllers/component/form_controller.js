import {AbstractController} from "../../core/controller/abstract.controller.js";
import {domService} from "../../services/dom.service.js";

/**
 * Form to manipulate object (not mad to work with the Symfony framework but with API)
 */
export default class FormController extends AbstractController {
	connect() {
		if( this.element.nodeName !== 'FORM' ) {
			throw new Error("Form controller can only be used on <form> elements");
		}
	}
	
	/**
	 * Resets the validation state of the associated form elements by removing invalid classes
	 * and clearing server-provided feedback messages.
	 */
	resetValidation() {
		this.element.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
		this.element.querySelectorAll(".invalid-feedback.server-feedback").forEach(el => el.remove());
	}
	
	/**
	 * Get input by name
	 * @param {string} name
	 * @returns {HTMLElement}
	 */
	getInputByName(name) {
		return this.element.querySelector(`[name="${name}"]`);
	}
	
	/**
	 * Report violation to the field using property name
	 * @param {ApiValidationError} error
	 * TODO handle more complex property paths
	 */
	processValidationError(error) {
		/**
		 * parameters: {{{ value }}: "null"}
		 * propertyPath: "name"
		 * template: "This value should not be blank."
		 * title: "Cette valeur ne doit pas être vide."
		 * type: "urn:uuid:c1051bb4-d103-4f74-8988-acbcafc7fdc3"
		 */
		error.violations.forEach(violation => {
			const $field = this.getInputByName(violation.property);
			if( $field ) {
				// $field.setCustomValidity(violation.message);
				const $feedback = document.createElement("div");
				$feedback.className = "invalid-feedback server-feedback";
				// $feedback.dataset.serverError = "1";
				$feedback.textContent = violation.message;
				$field.insertAdjacentElement("afterend", $feedback);
				$field.classList.add("is-invalid");
			}
		});
	}
	
	submit(event) {
		if( event ) {
			event.preventDefault();
			event.stopPropagation();
		}
		// Validate form
		if( !this.checkValidity() ) {
			// Invalid form
			return;
		}
		
		// Format form data to object
		const data = domService.getFormObject(this.element);
		
		// Trigger so.form.submit with valid form values
		this.dispatchEvent('so.form.submit', data);
		
		// After all, so the event binder can close the modal before it resets
		// this.reset();
	}
	
	reset() {
		domService.resetForm(this.element);
		
		return this;
	}
	
	checkValidity() {
		domService.dispatchEvent(this.element.querySelectorAll('.require-validation'), 'so.form.validate');
		return this.element.checkValidity();
	}
	
	/** @returns {HTMLFormElement} */
	get element() {
		return super.element;
	}
}
