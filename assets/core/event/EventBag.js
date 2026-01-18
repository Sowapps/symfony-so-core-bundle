/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

export class EventBag {
	
	#typeEvents;
	
	/**
	 * @param {Deferred} deferred
	 */
	add(deferred) {
		const event = deferred.type;
		this.#initializeEvent(event);
		this.#typeEvents[event].push(deferred);
	}
	
	/**
	 * @param {String} event
	 */
	get(event) {
		this.#initializeEvent(event);
		return this.#typeEvents[event];
	}
	
	remove(event) {
		this.#initializeEvent(event);
		this.#typeEvents[event] = [];
	}
	
	removeDeferred(deferred) {
		const event = deferred.type;
		this.#initializeEvent(event);
		
		const index = this.#typeEvents[event].indexOf(deferred);
		if( index < 0 ) {
			return false;
		}
		this.#typeEvents[event].splice(index, 1);
		
		return true;
	}
	
	#initializeEvent(event) {
		if( !this.#typeEvents ) {
			this.#typeEvents = {};
		}
		if( !this.#typeEvents[event] ) {
			this.#typeEvents[event] = [];
		}
	}
	
}
