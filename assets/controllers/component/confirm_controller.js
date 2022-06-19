import { AbstractController } from "../abstract.controller.js";
import { stringService } from "../../vendor/orpheus/js/service/string.service.js";

export default class extends AbstractController {
	
	static values = {title: String, message: String, submitName: String, submitValue: String};
	
	initialize() {
		// console.log('SoCore Confirm', this.element, this.titleValue);
		this.element.addEventListener('click', () => this.request());
	}
	
	formatData() {
		return {
			title: this.titleValue,
			message: this.hasMessageValue ? this.formatMessage(this.messageValue) : null,
			submitName: this.hasSubmitNameValue ? this.submitNameValue : 'submitConfirm',
			submitValue: this.hasSubmitValueValue ? this.submitValueValue : '1',
		};
	}
	
	formatMessage(message) {
		return stringService.nl2br(message);
	}
	
	request() {
		const data = this.formatData();
		// console.log('SoCore Confirm - request()', this.element, data);
		this.dispatchEvent(window, 'so.confirm.request', data);
	}
	
}
