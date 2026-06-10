import {stringService} from "../../services/string.service.js";
import {Is} from "../../helpers/is.helper.js";

/**
 * TODO Require JS DOC
 */
export class Deferred2 {
	/**
	 * @type {DeferredPromise}
	 */
	#promise;
	
	constructor(type, container = null) {
		this.type = type;
		this.container = container;
		this.doneCallback = null;
		this.failCallback = null;
		// For now, we want to lose previous values
		// this.pendingValue = null;
		
		// Bidirectional
		this.children = [];
		this.parent = null;
		// Unique ID
		this.id = stringService.generateId();
		this.#promise = new DeferredPromise(this);
	}
	
	/**
	 * @param {Array<DeferredPromise>} promises
	 * @return Deferred
	 */
	static any(promises) {
		const deferred = new Deferred(promises.length ? promises[0].type() : null);
		promises.forEach(promise => promise.then(
			data => deferred.resolve(data),
			data => deferred.reject(data),
		));
		return deferred;
	}
	
	attachCallbacks(doneCallback, failCallback) {
		this.doneCallback = doneCallback;
		this.failCallback = failCallback;
	}
	
	async reject(value) {
		if( this.failCallback ) {
			value = await this.failCallback.apply(null, [value]);
		}
		if( this.children ) {
			for (const child of this.children) {
				await child.reject(value);
			}
		}
		
		return this;
	}
	
	promise() {
		return this.#promise;
	}
	
	getRootDeferred() {
		return this.parent ? this.parent.getRootDeferred() : this;
	}
	
	castChild() {
		const child = new Deferred(this.type);
		child.parent = this;
		this.children.push(child);
		return child;
	}
	
	getListener() {
		if( !this.listener ) {
			this.listener = async event => {
				await this.resolve(event.detail, event);
			};
		}
		return this.listener;
	}
	
	async resolve(...values) {
		// Root deferred never has callback, children are wearing it
		// Each promise then, each callback assignment is creating a new deferred child
		if( this.doneCallback ) {
			const value = await this.doneCallback.apply(null, values);
			if( value !== undefined ) {
				values[0] = value;
			}
		}
		if( this.children ) {
			for (const child of this.children) {
				await child.resolve(...values);
			}
		}
		
		return this;
	}
	
	async triggerLastOne() {
		if( this.container && !Is.domElement(this.container) ) {
			await this.container.triggerLastEventToDeferred(this);
		}
		// Else do not handle container is DOM Element
	}
}

export class DeferredPromise2 {
	/**
	 * @param {Deferred} deferred
	 */
	constructor(deferred) {
		this.deferred = deferred;
	}
	
	type() {
		return this.deferred.type;
	}
	
	getRootDeferred() {
		return this.deferred.getRootDeferred();
	}
	
	then(doneCallback, failCallback = null) {
		const child = this.deferred.castChild();
		child.attachCallbacks(doneCallback, failCallback);
		return child.promise();
	}
	
	triggerLastOne() {
		this.getRootDeferred().triggerLastOne();
	}
}
