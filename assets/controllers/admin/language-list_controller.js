import { AbstractController } from "@sowapps/so-core/controllers/abstract.controller.js";

export default class extends AbstractController {
	static targets = ['dialogCreate', 'dialogUpdate'];
	
	getEventRowData(event) {
		return event.target.closest('tr').dataset.item;
	}
	
	createLanguage() {
		this.dispatchEvent(this.dialogCreateTarget, 'so.language.request');
	}
	
	updateLanguage(event) {
		this.dispatchEvent(this.dialogUpdateTarget, 'so.language.request', this.getEventRowData(event));
	}
	
}
