/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

/**
 * AsyncLoader handles an asynchronously loaded value.
 *
 * It supports three situations:
 * 1. The value is already available: return it immediately.
 * 2. A loading promise is in progress: wait for it, even if a value already exists.
 * 3. No value and no promise yet: wait until a promise is provided and resolved.
 */
export class AsyncLoader {
	#value = null;
	#error = null;
	/** @var {Promise} */
	#loadingPromise = null;
	
	#waitingResolvers = [];
	#waitingRejectors = [];
	
	/**
	 * Returns true when a resolved value is currently available.
	 *
	 * @returns {boolean}
	 */
	hasValue() {
		return this.#value !== null;
	}
	
	/**
	 * Returns true when the last promise returned an error.
	 *
	 * @returns {boolean}
	 */
	hasError() {
		return this.#error !== null;
	}
	
	/**
	 * Returns the last error, else null if no error
	 *
	 * @returns {*}
	 */
	getError() {
		return this.#error;
	}
	
	/**
	 * Returns true when a loading promise is currently in progress.
	 *
	 * @returns {boolean}
	 */
	isLoading() {
		return this.#loadingPromise !== null;
	}
	
	/**
	 * Provides a loading promise.
	 * All pending load() calls waiting for a future promise will start waiting on it.
	 *
	 * The resolved value becomes the current value.
	 *
	 * @template T
	 * @param {Promise<T>} promise
	 * @returns {Promise<T>}
	 */
	providePromise(promise) {
		if( !(promise instanceof Promise) ) {
			throw new TypeError('The provided value must be a Promise.');
		}
		
		this.#loadingPromise = promise;
		this.#error = null;
		
		return promise.then(
			value => {
				if( this.#loadingPromise === promise ) {
					this.#value = value;
					this.#loadingPromise = null;
				}
				this.#flushWaitingWithPromise(Promise.resolve(value));
				return value;
			},
			error => {
				if( this.#loadingPromise === promise ) {
					this.#error = error;
					this.#loadingPromise = null;
				}
				this.#flushWaitingWithPromise(Promise.reject(error));
				throw error;
			},
		);
	}
	
	/**
	 * Returns the value, waiting if necessary.
	 *
	 * Behavior:
	 * - If a promise is in progress, waits for it.
	 * - Else if a value is available, returns it immediately.
	 * - Else waits until a promise is later provided and resolved.
	 *
	 * @returns {Promise<*>}
	 */
	async get() {
		if( this.#loadingPromise !== null ) {
			// Value is loading, we wait for it
			return await this.#loadingPromise;
		}
		
		if( this.hasError() ) {
			// An error is available
			throw this.#error;
		} else if( this.hasValue() ) {
			// Value is already available
			return this.#value;
		}
		
		// No value, not loading yet, waiting to get a promise and resolve it
		return await new Promise((resolve, reject) => {
			this.#waitingResolvers.push(resolve);
			this.#waitingRejectors.push(reject);
		});
	}
	
	/**
	 * Resolves all pending waiters with a given promise.
	 *
	 * @param {Promise<*>} promise
	 */
	#flushWaitingWithPromise(promise) {
		if( this.#waitingResolvers.length === 0 ) {
			return;
		}
		const resolvers = this.#waitingResolvers;
		const rejectors = this.#waitingRejectors;
		
		this.#waitingResolvers = [];
		this.#waitingRejectors = [];
		
		promise.then(
			value => {
				for (const resolve of resolvers) {
					resolve(value);
				}
			},
			error => {
				for (const reject of rejectors) {
					reject(error);
				}
			},
		);
	}
}
