import { AbstractController } from "@sowapps/so-core/controllers/abstract.controller.js";

export default class extends AbstractController {
	
	initialize() {
		this.$form = this.element.querySelector('form');
		this.formController = this.getController(this.$form, 'sowapps--so-core--form');
	}
	
	cancel() {
		// Close dialog
		this.dispatchEvent(this.element, 'app.dialog.close');
		console.log('TEST');
	}
	
	request(event) {
		const data = event.detail;
		// console.log('Language Dialog - Request', this.element, event, data);
		// Fill dialog
		this.formController.reset().fill(data);
		// Open dialog
		this.dispatchEvent(this.element, 'app.dialog.open');
	}
	
}
