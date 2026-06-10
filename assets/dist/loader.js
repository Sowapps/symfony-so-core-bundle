/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * JS Entrypoint for the main application
 */
import {factoryRegistry} from "../core/factories.js";
// Files that are imported in module are not recognized by AssetMapper
import {AsyncLoader} from "../core/event/AsyncLoader.js";
import {mapper} from "../services/mapper.service.js";

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

export * from "../core/controller/abstract.controller.js";
export * from "../core/controller/controllers.js";
export * from "../core/application.js";
export * from "../core/exceptions.js";
export * from "../core/pagination.js";
export * from "../core/StringTemplate.js";
// export * from "./core/event/AsyncLoader.js"; // Impossible, the file is never imported in the bundle, AssetMapper won't find it
export * from "../core/event/Deferred.js";
export * from "../core/event/EventBag.js";
export * from "../core/event/EventListenerTrait.js";
export * from "../services/app-web.service.js";
export * from "../services/dom.service.js";
export * from "../services/navigation.service.js";
export * from "../services/saw.service.js";
export * from "../services/security.service.js";
export * from "../services/string.service.js";

// Forgotten services & variables, not used in the bundle, but available for app
export {
	AsyncLoader,
	mapper
}
