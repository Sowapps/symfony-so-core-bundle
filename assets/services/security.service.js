import {Portrait} from "../core/trait/Portrait.js";
import {EventListenerTrait} from "../core/event/EventListenerTrait.js";

class SecurityService {
	
	#user = null;
	
	revokeUser() {
		const user = this.user;
		if( !user ) {
			// Already revoked
			return;
		}
		this.user = null;
		
		this.trigger(SecurityEvent.USER_DISCONNECTED, {user});
	}
	
	isAuthenticated() {
		return !!this.user;
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
	USER_AUTHENTICATED: "user.authenticated",
	USER_CONNECTED: "user.connected",
	USER_DISCONNECTED: "user.disconnected",
};

export const securityService = new SecurityService();
