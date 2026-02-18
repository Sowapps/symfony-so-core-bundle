import {AbstractController} from "../../core/controller/abstract.controller.js";
import {stringService} from "../../services/string.service.js";
import {domService} from "../../services/dom.service.js";

/**
 * Only send a confirm request to the confirm dialog
 */
export default class extends AbstractController {
	
	static values = {title: String, message: String, submitName: String, submitValue: String};
	
	initialize() {
		// console.log('SoCore Confirm', this.element, this.titleValue);
		this.element.addEventListener('click', () => this.request());
	}
	
	formatData() {
		return {
			element: this.element,
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
		domService.dispatchEvent(window, 'so.confirm.request', data);
	}
	
}
