import {Is} from "./is.helper.js";

export class Http {
	
	/**
	 * @param {Object} query
	 * @param {boolean} ready
	 * @return {null|string}
	 */
	static formatQueryString(query, ready = true) {
		if( !Is.empty(query) ) {
			return (ready ? "?" : "") + (new URLSearchParams(this.formatToUri(query)).toString());
		}
		return (ready ? "" : null);
	}
	
	/**
	 * Flatten nested object to complex keys, e.g. first[second][third]
	 *
	 * @param {Object} object
	 * @return {Object}
	 */
	static formatToUri(object) {
		const entries = this.#buildUriEntries(object, "");
		return Object.fromEntries(entries);
	}
	
	/**
	 * @param value
	 * @param {string} prefix
	 * @return {Array[]}
	 */
	static #buildUriEntries(value, prefix) {
		let entries = [];
		if( Is.array(value) ) {
			/** @var {Array} value */
			for (const subValue of value) {
				entries.push(...this.#buildUriEntries(subValue, prefix + "[]"));
			}
		} else if( Is.pureObject(value) ) {
			for (const [key, subValue] of Object.entries(value)) {
				entries.push(...this.#buildUriEntries(subValue, prefix ? prefix + `[${key}]` : key));
			}
		} else {
			// Scalar
			entries.push([prefix, value]);
		}
		return entries;
	}
	
}
