/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */
import {Trait} from "../trait/Trait.js";
import {Deferred, DeferredPromise} from "./Deferred.js";
import {EventBag} from "./EventBag.js";
import {Is} from "../../helpers/is.helper.js";


export class EventListenerTrait extends Trait {
	
	initializeEventListenerTrait() {
		if( !this.eventBag ) {
			this.eventBag = new EventBag();
		}
	}
	
	/**
	 * @param {String} event
	 * @param {Object|any|null} data
	 */
	async trigger(event, data = null) {
		this.initializeEventListenerTrait();
		const events = this.eventBag.get(event);
		const resolveEvent = {event, data, target: this, lastResult: null};
		this.lastEvent = resolveEvent;
		
		for (const deferred of events) {
			await this.applyEventToDeferred(resolveEvent, deferred);
		}
	}
	
	/**
	 * @param {Deferred} deferred
	 * @return {Promise<void>}
	 */
	async triggerLastEventToDeferred(deferred) {
		if( this.lastEvent.event === deferred.type ) {
			await this.applyEventToDeferred(this.lastEvent, deferred);
		}
	}
	
	async applyEventToDeferred(resolveEvent, deferred) {
		resolveEvent.lastResult ||= await deferred.resolve(resolveEvent);
	}
	
	/**
	 * @param event
	 * @return {DeferredPromise}
	 */
	on(event) {
		this.initializeEventListenerTrait();
		const deferred = new Deferred(event, this);
		this.eventBag.add(deferred);
		return deferred.promise();
	}
	
	/**
	 * @param {string|DeferredPromise} promiseOrEvent
	 */
	off(promiseOrEvent) {
		this.initializeEventListenerTrait();
		if( promiseOrEvent instanceof DeferredPromise ) {
			this.eventBag.removeDeferred(promiseOrEvent.getRootDeferred());
		} else {
			this.eventBag.remove(promiseOrEvent);
		}
	}
	
}
