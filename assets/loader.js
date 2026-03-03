/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * Entrypoint for the main application
 */
import {factoryRegistry} from "./core/factories.js";

/**
 * Use the factory registry to decorate the important services of your app, as you Application
 * @return {FactoryRegistry}
 */
export function getFactoryRegistry() {
	return factoryRegistry;
}

/**
 * Instantiate the app application from the factory registry
 * @return {Application}
 */
export function getApp() {
	return factoryRegistry.getApplication();
}
