import { AbstractController } from "../abstract.controller.js";
import { domService } from "../../vendor/orpheus/js/service/dom.service.js";

export default class Form extends AbstractController {
	
	static targets = ['submitButton'];
	static values = {delegate: Boolean, liveCheck: Boolean};
	
	initialize() {
		const $form = this.element;
		// Ensure we can find this element even this is not a <form>
		$form.classList.add('controller-form');
		this.delegateValue = this.hasDelegateValue && this.delegateValue;
		this.liveCheckValue = this.hasLiveCheckValue && this.liveCheckValue;
		// Bootstrap 5 validation code
		// https://getbootstrap.com/docs/5.0/forms/validation/
		$form.addEventListener('submit', () => {
			// Unable to make it work
			// this.dispatchEvent(this.element, 'appformvalidating', {form: this.element});
			const valid = this.checkValidity();
			if( valid && this.delegateValue ) {
				$form.trigger('form.valid');
				return false;
			}
			
			$form.classList.add('was-validated');
			if( valid ) {
				// Now submitting
				setTimeout(() => {
					// Wait form is submitting
					$form.classList.add('state-submitting');
					this.dispatchEvent($form, 'disable');
				}, 100);
			}
			$form.querySelector(':invalid').each((index, element) => {
				if( element.dataset.invalidate ) {
					const $other = document.querySelector(element.dataset.invalidate);
					if( $other ) {
						$other.setCustomValidity('invalid');
					}
				}
			});
			
			return valid;
		});
		$form.addEventListener('app.form.reset', () => this.reset());
		$form.addEventListener('app.form.disable-submit', () => this.disableSubmit());
		$form.addEventListener('app.form.enable-submit', () => this.enableSubmit());
		if( this.liveCheckValue ) {
			domService.getInputs($form).forEach($input => {
				$input.addEventListener('change', () => {
					const valid = this.checkValidity();
					if( valid ) {
						this.enableSubmit();
					} else {
						this.disableSubmit();
					}
				});
			});
		}
	}
	
	enableSubmit() {
		this.submitButtonTargets.forEach(button => {
			button.disabled = false;
		});
	}
	
	disableSubmit() {
		this.submitButtonTargets.forEach(button => {
			button.disabled = true;
		});
	}
	
	reset() {
		if( this.element.nodeName === 'FORM' ) {
			this.element.reset();
		}
	}
	
	checkValidity() {
		this.dispatchEvent(this.element.querySelectorAll('.require-validation'), 'app.form.validate');
		return this.element.checkValidity();
	}
}
