import {Portrait} from "../core/trait/Portrait.js";
import {EventListenerTrait} from "../core/event/EventListenerTrait.js";
import {StringTemplate} from "../core/StringTemplate.js";
import {AbstractMainController} from "../core/controller/controllers.js";

class NavigationService {
	
	filters = {};
	currentRequestRoute = null;
	
	/**
	 * Navigate to controller page (destination must be in main controller's routes)
	 *
	 * @param {string} path
	 * @param {Object} parameters
	 * @return {Promise<void>}
	 */
	async navigate(path, parameters = {}) {
		if( !path ) {
			throw new Error("Unable to navigate to empty path");
		}
		if( path instanceof Route ) {
			path = path.path;
		}
		console.info("Navigate to", path, "with parameters", parameters);
		// Change url
		this.setUrlPath(path, parameters);
		// Url changed but that's all, main controller should handle the changes in page
		await this.trigger(NavigationEvent.NAVIGATED, {path: path, parameters: parameters});
	}
	
	getCurrentUrl() {
		return new URL(window.location.href);
	}
	
	/**
	 * Force real http redirection
	 *
	 * @param {string} destination
	 */
	redirectTo(destination) {
		window.location.href = destination;
	}
	
	/**
	 * @param {Array<Route>} routes
	 * @param {URL|null} url
	 * @return {{route: Route, parameters: Object}|null}
	 */
	getRequestRoute(routes, url = null) {
		url = url || this.getCurrentUrl();
		const route = this.findFirstMatchingRoute(routes, url.pathname);
		if( !route ) {
			throw new Error(`No route found for request path '${url.pathname}'`);
		}
		return route;
	}
	
	/**
	 * @param routes
	 * @param path There request path (with parameters)
	 * @return {{route: Route, parameters: Object}|null}
	 */
	findFirstMatchingRoute(routes, path) {
		for (const route of routes) {
			const parameters = this.getPathRouteParameters(route, path);
			if( parameters ) {
				return {route, parameters};
			}
		}
		
		return null;
	}
	
	/**
	 * @param {Array} routes
	 * @param {string} path The route path
	 * @return {Route|null}
	 */
	findPathRoute(routes, path) {
		for (const route of routes) {
			if( route.path === path ) {
				return route;
			}
		}
		
		return null;
	}
	
	/**
	 * @param {Route} route
	 * @param {string} path
	 * @return {Object|null}
	 */
	getPathRouteParameters(route, path) {
		const {regex, parameters} = route.pathRegex;
		let pathParameterList = regex.exec(path);
		if( !pathParameterList ) {
			return null;
		}
		pathParameterList = pathParameterList.slice(1);// Remove first match, the whole string
		if( parameters.length !== pathParameterList.length ) {
			console.warn(`Issue with route ${route.path} and path ${path}, regex did not find the right count of path parameters (${pathParameterList.length} but got ${parameters.length})`);
			return null;
		}
		const pathParameters = {};
		for (const [index, name] of parameters.entries()) {
			pathParameters[name] = pathParameterList[index];
		}
		
		return pathParameters;
	}
	
	getCurrentStateRoute() {
		return history.state?.route;
	}
	
	/**
	 * @param {{path: string, parameters: Object}|null} stateRoute
	 */
	setCurrentStateRoute(stateRoute) {
		const state = history.state;
		state.route = stateRoute;
		this.#setCurrentState(state);
	}
	
	#setCurrentState(data) {
		history.replaceState(data, "");
	}
	
	/**
	 * @param {string} path
	 * @param {Object} parameters
	 */
	setUrlPath(path, parameters = {}) {
		const stringTemplate = new StringTemplate(this.filters, this);
		const formattedUrl = stringTemplate.render(path, parameters);
		const state = history.state;
		state.route = {path: path, parameters: parameters};
		console.info(`Set url to ${formattedUrl} with state`, state);
		// Set real url in browser with new history entry
		history.pushState(state, "", formattedUrl);
	}
	
}

Portrait.for(NavigationService).use(EventListenerTrait);
/**
 * @name NavigationService#on
 * @function
 * @memberof NavigationService
 * @param {String } event The event to listen
 * @return {DeferredPromise}
 */
/**
 * @name NavigationService#off
 * @function
 * @memberof NavigationService
 * @param {string|DeferredPromise} promiseOrEvent The event to unbind
 */
/**
 * @name NavigationService#trigger
 * @function
 * @memberof NavigationService
 * @param {String} event
 * @param {Object|any|null} data
 */

export const NavigationEvent = {
	// REQUEST: "navigation.request",
	NAVIGATED: "navigation.navigated",
};

export const navigationService = new NavigationService();

class Route {
	path;
	#menuPath;
	
	constructor(path) {
		if( this.constructor === Route ) {
			throw new TypeError("Abstract class \"Route\" cannot be instantiated directly");
		}
		
		this.path = path;
		this.#menuPath = null;
	}
	
	/**
	 * @param {AbstractMainController} controller
	 * @param {Object} parameters
	 * @return {Promise<void>}
	 */
	applyTo(controller, parameters) {
		throw new Error("You must implement this function");
	}
	
	get pathRegex() {
		const parameters = [];
		const regexPath = this.path.replace(/\{([^\}]+)\}/g, (match, variable) => {
			parameters.push(variable);
			return "([^/]+)"; // Capture tout sauf le séparateur de chemin "/"
		});
		return {regex: new RegExp("^" + regexPath + "$"), parameters};
	}
	
	get menuPath() {
		return this.#menuPath || this.path;
	}
	
	setMenuPath(path) {
		this.#menuPath = path;
		
		return this;
	}
}

export class TemplateRoute extends Route {
	template;
	
	constructor(path, template) {
		super(path);
		this.template = template;
	}
	
	/**
	 * @param {AbstractMainController} controller
	 * @param {Object} parameters
	 * @return {Promise<void>}
	 */
	applyTo(controller, parameters) {
		return controller.setContentsToTemplate(this.template, parameters);
	}
}

export class CallbackRoute extends Route {
	callback;
	
	/**
	 * @param {string} path
	 * @param {function} callback
	 */
	constructor(path, callback) {
		super(path);
		this.callback = callback;
	}
	
	/**
	 * @param {AbstractMainController} controller
	 * @param {Object} parameters
	 * @return {Promise<void>}
	 */
	applyTo(controller, parameters) {
		return this.callback.call(controller, parameters);
	}
}
