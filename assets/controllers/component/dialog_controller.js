import { AbstractController } from "../abstract.controller.js";
import { Modal } from 'bootstrap';

export default class extends AbstractController {
	
	static values = {initOpen: Boolean};
	
	initialize() {
		this.modal = Modal.getOrCreateInstance(this.element);
		if( this.hasInitOpenValue && this.initOpenValue ) {
			this.open();
		}
	}
	
	close() {
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
