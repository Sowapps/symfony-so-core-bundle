import { AbstractController } from "@sowapps/so-core/controllers/abstract.controller.js";

export default class extends AbstractController {
	static targets = ['body'];
	// static values = {labels: Object};
	// static values = {url: String, order: Array};
	
	initialize() {
		console.log('Log view for element', this.element, 'and body', this.bodyTarget);
		this.$body = this.bodyTarget;
	}
	
}
