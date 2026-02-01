/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

import {sawService} from "../../services/saw.service.js";
import {domService} from "../../services/dom.service.js";

/**
 * Connect element to a the API
 * TODO Abstract
 */
export class AbstractElementLoader {
	
	static autoStart = true;
	#started = false;
	
	constructor(element) {
		if( this.constructor === AbstractElementLoader ) {
			throw new Error("Class is of abstract type and can't be instantiated");
		}
		
		this.element = element;
	}
	
	dispatchEvent(event, detail = null, options = {}) {
		domService.dispatchEvent(this.element, event, detail, options);
	}
	
	watch(promise) {
		this.onStart();// Could be already started
		return promise.then(
			(response) => {
				return this.onSuccess(response) || response;
			},
			(error) => {
				this.onError(error);
				throw error;// Throw back the error
			}
		).finally(this.onTerminating.bind(this));
	}
	
	start() {
		if( this.#started ) {
			// Silent start reject
			return;
		}
		this.#started = true;
		this.onStart();
	}
	
	onStart() {
	}
	
	onSuccess(response) {
	}
	
	onError(error) {
	}
	
	onTerminating() {
	}
	
}

export class SawElementLoader extends AbstractElementLoader {
	
	#notificationTitle = null;
	
	setNotificationTitle(title) {
		this.#notificationTitle = title;
		
		return this;
	}
	
	onStart() {
		sawService.setLoading(this.element);
	}
	
	// onSuccess(result) {
	// 	console.log("onSuccess", result);
	// }
	
	onError(error) {
		// Add notification
		if( this.#notificationTitle ) {
			this.reportException(error, this.#notificationTitle);
		}
		// Change content to show error
		const template = document.getElementById("TemplateLoadingServerError");
		const elements = domService.renderTemplate(template);
		this.element.replaceChildren(...elements);
	}
	
	reportException(exception, title = null, options = {}) {
		this.dispatchEvent("so.report.error", {title, error: exception, options});
	}
	
	static connect(element) {
		const loader = new SawElementLoader(element);
		if( this.autoStart ) {
			loader.start();
		}
		return loader;
	}
	
}
