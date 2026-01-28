import {AbstractController} from "../abstract.controller.js";
import {Modal} from 'bootstrap';

export default class extends AbstractController {
	
	static values = {initOpen: Boolean};
	
	initialize() {
		this.modal = Modal.getOrCreateInstance(this.element);
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
	}
	
	open(event) {
		let data = null, prefix = null, pattern = null;
		if( event && event.detail ) {
			prefix = event.detail.prefix || 'item';
			pattern = event.detail.pattern;
			data = event.detail.data || event.detail;
		}
		// console.log('Open dialog with', data, 'prefix', prefix, 'and pattern', pattern);
		if( data ) {
			$(this.element).fill(prefix, data);
			if( pattern ) {
				$(this.element).fillByName(data, pattern);
			}
		}
		this.modal.show();
	}
}
