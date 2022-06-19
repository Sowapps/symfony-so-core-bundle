import { AbstractController } from "../abstract.controller.js";

export default class extends AbstractController {
	
	static targets = ['cancel', 'confirm'];
	
	// initialize() {
	// Cohabit with Dialog controller
	// console.log('Confirm SoCore Dialog', this.element, this.confirmTarget);
	// }
	
	cancel() {
		// Close dialog
		this.dispatchEvent(this.element, 'app.dialog.close');
	}
	
	request(event) {
		const data = event.detail;
		// Fill dialog
		this.element.querySelectorAll('.modal-title').forEach(element => element.innerHTML = data.title);
		this.element.querySelectorAll('.dialog-legend').forEach(element => element.innerHTML = data.message);
		this.confirmTargets.forEach(element => {
			element.setAttribute('name', data.submitName);
			element.setAttribute('value', data.submitValue);
		});
		// Open dialog
		this.dispatchEvent(this.element, 'app.dialog.open');
	}
	
}
