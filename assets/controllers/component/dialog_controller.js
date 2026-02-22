import {AbstractController} from "../../core/controller/abstract.controller.js";
import {Modal} from 'bootstrap';
import {domService} from "../../services/dom.service.js";

export default class DialogController extends AbstractController {
	static targets = [
		'template', // Optional, if provided the content is removed from the dialog when connecting TODO Remove unused
		'content' // Optional, if provided the content is removed from the dialog when connecting
	];
	
	static values = {
		initOpen: Boolean,
		templated: Boolean,
		autoReset: {type: Boolean, default: true},
	};
	/** @type {String|null} */
	template = null;
	
	/**
	 * @type {Modal}
	 */
	modal;
	
	connect() {
		this.modal = Modal.getOrCreateInstance(this.element);
		console.log("Connect dialog", this.element, this.templatedValue);
		if( this.templatedValue ) {
			// Remove content if this controller is templated
			this.template = this.contentTarget.innerHTML;
			this.contentTarget.innerHTML = "";
			console.log("Detached element", this.template);
		}
		
		if( this.hasInitOpenValue && this.initOpenValue ) {
			this.open();
		}
	}
	
	close() {
		// Remove focus from the button to fix the following error from Chrome
		// Blocked aria-hidden on an element because its descendant retained focus. The focus must not be hidden from assistive technology users. Avoid using aria-hidden on a focused element or its ancestor. Consider using the inert attribute instead, which will also prevent focus. For more details, see the aria-hidden section of the WAI-ARIA specification at https://w3c.github.io/aria/#aria-hidden.
		const buttonElement = document.activeElement;
		buttonElement.blur();
		
		this.modal.hide();
		
		// Auto reset form in dialog
		if( this.autoResetValue ) {
			const $form = this.element.querySelector("form");
			if( $form ) {
				domService.dispatchEvent($form, 'so.form.reset');
			}
		}
	}
	
	open(event) {
		console.log('Open dialog', event.detail);
		let data = null;
		if( event && event.detail ) {
			data = event.detail.data || event.detail;
		}
		if( data ) {
			if( this.template ) {
				this.contentTarget.innerHTML = "";
				const contentElements = domService.renderTemplate(this.template, data);
				console.log("content", contentElements);
				// .template-unloaded allows dev to exclude content from script like button disabling on operating, as it is out of DOM, it won't be enabled again
				// Another solution is to put it in a template in the DOM, so the buttons are enabled again. Both solutions are good.
				this.contentTarget.classList.remove("template-unloaded");
				this.contentTarget.append(...contentElements);
			} else {
				domService.fillForm(this.element, data);
			}
		}
		this.modal.show();
	}
	
	/** @returns {HTMLElement} */
	get element() {
		return super.element;
	}
	
	static get EVENT_DIALOG_OPEN() {
		return "so.dialog.open";
	}
	
	static get EVENT_DIALOG_CLOSE() {
		return "so.dialog.close";
	}
}
