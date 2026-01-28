import {AbstractController} from "../abstract.controller.js";

/**
 * TODO Update
 */
export default class extends AbstractController {
	
	// static targets = ['cancel', 'confirm'];
	originElement = null;
	data = null;
	
	initialize() {
		// Cohabit with Dialog controller
		// console.log('Confirm SoCore Dialog', this.element);
	}
	
	confirm(event) {
		// Close dialog
		this.dispatchEvent(this.element, 'so.dialog.close');
		
		if( this.originElement ) {
			// Confirm to the original element
			this.dispatchEvent(this.originElement, 'so.dialog.confirm', this.data);
		}
	}
	
	request(event) {
		const data = event.detail;
		this.data = data;
		this.originElement = data.element;
		// Fill dialog
		this.element.querySelectorAll('.modal-title').forEach(element => element.innerHTML = data.title);
		this.element.querySelectorAll('.dialog-legend').forEach(element => element.innerHTML = data.message);
		// this.confirmTargets.forEach(element => {
		// 	element.setAttribute('name', data.submitName);
		// 	element.setAttribute('value', data.submitValue);
		// });
		// Open dialog
		this.dispatchEvent(this.element, 'so.dialog.open');
	}
	
}
