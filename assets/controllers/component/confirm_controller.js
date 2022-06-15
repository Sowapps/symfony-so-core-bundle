import { AbstractController } from "../abstract.controller.js";

export default class extends AbstractController {
	
	static values = {title: String, message: String, submitName: String, submitValue: String};
	
	initialize() {
		// console.log('SoCore Confirm', this.element, this.titleValue);
		this.element.addEventListener('click', () => this.request());
	}
	
	formatData() {
		return {
			title: this.titleValue,
			message: this.hasMessageValue ? this.messageValue : null,
			submitName: this.hasSubmitNameValue ? this.submitNameValue : 'submitConfirm',
			submitValue: this.hasSubmitValueValue ? this.submitValueValue : '1',
		};
	}
	
	request() {
		const data = this.formatData();
		// console.log('SoCore Confirm - request()', this.element, data);
		this.dispatchEvent(window, 'so.confirm.request', data);
	}
	
}
