/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */
import {Is} from "../helpers/is.helper.js";
import {Application} from "./application.js";

/**
 * Declare factories for decorated services instantiated while loading JS app
 * So the factories are instantiated but not the services
 */
class FactoryRegistry {
	
	#applicationFactory = null;
	#application = null;
	
	/**
	 * Replace application factory. Please replace it before the first use
	 * @param {() => any} factory
	 */
	setApplicationFactory(factory) {
		if( this.#application ) {
			throw new Error("Application already instantiated. Please, set factory before first use");
		}
		if( !Is.function(factory) ) {
			throw new Error("The factory is not a valid function");
		}
		this.#applicationFactory = factory;
		
		return this;
	}
	
	getApplication() {
		console.log("Get app");
		if( !this.#applicationFactory ) {
			throw new Error("No application factory");
		}
		if( !this.#application ) {
			const instance = this.#applicationFactory();
			if( !instance ) {
				throw new Error("Invalid factory for Application. An Application-based instance should be returned");
			}
			this.#application = instance;
		}
		
		return this.#application;
	}
	
}

export const factoryRegistry = new FactoryRegistry();
factoryRegistry
	.setApplicationFactory(() => new Application());
