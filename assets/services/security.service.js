/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

import {Portrait} from "../core/trait/Portrait.js";
import {EventListenerTrait} from "../core/event/EventListenerTrait.js";
import {Is} from "../helpers/is.helper.js";
import {ApiException, ApiServerError, appWebService} from "./app-web.service.js";

/**
 * Manage security of the JS app
 */
class SecurityService {
	#token = null;
	#user = null;
	/**
	 * @type {Storage|null}
	 */
	storage = null;
	keyApiToken = "api_token";
	
	start() {
		this.storage = localStorage;
		this.loadToken();
		this.loadUser();
		this.listenEvents();
	}
	
	listenEvents() {
		window.addEventListener('storage', (event) => {
			console.log('Storage event:', event);
			// e.key, e.oldValue, e.newValue, e.url, e.storageArea
			if( event.storageArea === this.storage && event.key === this.keyApiToken ) {
				console.log('Token changed in another tab:', event.newValue);
				this.loadToken();
			}
		});
		console.log('Listen storage events');
	}
	
	/**
	 * Load token from the storage, forcing update
	 */
	loadToken() {
		const token = this.storage.getItem(this.keyApiToken);
		console.log("Load token, found", token);
		if( token ) {
			this.#setActiveToken(token);// Reload from the storage
		}
		// TODO Set token to the appWebService
	}
	
	/**
	 * Load the user from API using the active token
	 */
	async loadUser() {
		let user = null;
		if( this.hasToken() ) {
			// If SecurityService is having a token, so the appWebService should too
			try {
				// TODO Map to a real JS object
				user = await appWebService.requestGet(`/me`);
			} catch (error) {
				if( !(error instanceof ApiServerError) || error.response.status !== 401 ) {
					throw new ApiException("Unable to get authenticated user", error);
				} // Else 401 error is acceptable, token is no more valid
				// TODO Review the token is no more authenticating the user, show error ? suggest login again ? Token was deleted ?
			}
		}
		this.user = user;
		if( user ) {
			this.trigger(SecurityEvent.USER_AUTHENTICATED, {user});
		}
	}
	
	#storeToken(token) {
		console.log("Store token in localStorage:", token);
		if( !token || token === "undefined" || !Is.string(token) ) {
			// Wont save an invalid token
			return false;
		}
		this.storage.setItem(this.keyApiToken, token);
		
		return true;
	}
	
	/**
	 * New authentication from the login page
	 */
	authenticate(user, token) {
		this.#storeToken(token);
		this.#setActiveToken(token);// From the login page
		// this.loadUser();// Already loaded from the authentication
	}
	
	revokeUser() {
		const user = this.user;
		if( !user ) {
			// Already revoked
			return;
		}
		this.user = null;
		this.revokeToken();
		this.trigger(SecurityEvent.USER_DISCONNECTED, {user});
	}
	
	/**
	 * Revoke locally only
	 */
	revokeToken() {
		this.#token = null;
		this.storage.removeItem(this.keyApiToken);
	}
	
	hasToken() {
		return !!this.#token;
	}
	
	/**
	 * A complete authentication is having a valid token and a valid user
	 * @return {boolean}
	 */
	isAuthenticated() {
		return !!this.user;
	}
	
	#setActiveToken(token) {
		appWebService.setBearerTokenAuthentication(token);
		this.#token = token;
	}
	
	get user() {
		return this.#user;
	}
	
	set user(value) {
		this.#user = value;
	}
}

Portrait.for(SecurityService).use(EventListenerTrait);
/**
 * @name SecurityService#on
 * @function
 * @memberof SecurityService
 * @param {String } event The event to listen
 * @return {DeferredPromise}
 */
/**
 * @name SecurityService#off
 * @function
 * @memberof SecurityService
 * @param {string|DeferredPromise} promiseOrEvent The event to unbind
 */
/**
 * @name SecurityService#trigger
 * @function
 * @memberof SecurityService
 * @param {String} event
 * @param {Object|any|null} data
 */

export const SecurityEvent = {
	USER_AUTHENTICATED: "user.authenticated", // Has authentication token and the authenticated user is loaded
	USER_CONNECTED: "user.connected", // The authenticated user is loaded
	USER_DISCONNECTED: "user.disconnected", // The authenticated user is unloaded and his authentication is revoked
};

export const securityService = new SecurityService();
