/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

import {securityService} from "../services/security.service.js";

/**
 * Base application class for the whole JS application
 */
export class Application {
	#stimulusApp;
	
	get stimulusApp() {
		return this.#stimulusApp;
	}
	
	setStimulusApp(value) {
		if( this.#stimulusApp ) {
			throw new Error("Cannot set stimulusApp to a new value, only one value allowed");
		}
		this.#stimulusApp = value;
		
		return this;
	}
	
	start() {
		console.log("Start App");
		securityService.start();
	}
	
}
