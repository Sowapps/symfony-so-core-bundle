/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

import {Is} from "../helpers/is.helper.js";

class ParseValueException extends Error {
}

/**
 * Only to replace variables in a string
 */
export class StringTemplate {
	
	constructor(filters, filterer) {
		this.filters = filters;
		this.filterer = filterer;
	}
	
	/**
	 * @param {string} template
	 * @param {Object} data
	 * @return {string}
	 */
	render(template, data) {
		data = data || {};
		// Resolve values in attributes
		// Deprecated for TWIG compatibility, double brackets
		template = template.replace(/\{\{ ?([^\}]+) ?\}\}/ig, (all, variable) => {
			try {
				return this.parseValue(variable, data);
			} catch (exception) {
				return all;
			}
		});
		// Yes we want this one, simple brackets
		template = template.replace(/\{ ?([^\}]+) ?\}/ig, (all, variable) => {
			try {
				return this.parseValue(variable, data);
			} catch (exception) {
				return all;
			}
		});
		// For url compatibility, url encoded brackets
		template = template.replace(/\%7B\%20([^\%]+)\%20\%7D/ig, (all, variable) => {
			try {
				return this.parseValue(variable, data);
			} catch (exception) {
				return all;
			}
		});
		
		return template;
	}
	
	parseValue(value, data) {
		const result = value.match(/\s?([^\|]+[^\s])(?=\s*\||\s*$)/g)
			// Chain filter using the result of the previous one (first property is a filter and the data is the first value)
			.reduce((chainedValue, filter) => this.resolveFilter(chainedValue, filter, data), data);
		if( result === undefined ) {
			throw new ParseValueException(`Unable to resolve value ${value}`);
		}
		return result;
	}
	
	/**
	 * @param {any} value
	 * @param {string} filter
	 * @param {Object} data
	 */
	resolveFilter(value, filter, data) {
		const [, name, argumentList] = filter.match(/^([^\(]+)(?:\(([^\)]*)\))?$/);
		const processAsFunction = argumentList !== undefined || this.filters[name];
		if( processAsFunction ) {
			// As function
			const filterCallback = this.filters[name];
			if( !filterCallback ) {
				throw new Error(`Unknown filter ${name}`);
			}
			let args = (Is.set(argumentList) ? argumentList : "")
				.match(/(".*?"|'.*?'|[^"',\s]+)(?=\s*,|\s*$)/g);// Group by quotes or separated by comma
			if( args ) {
				args = args.map(argValue => this.parseValue(argValue, data));
			} else {
				args = [];
			}
			return filterCallback.call(this.filters, value, ...args);
		}
		if( this.isScalarString(name) ) {
			// Scalar string value. Example: 'foo' or "bar"
			return name.slice(1, name.length - 1);
			
		}
		if( this.isScalarInteger(name) ) {
			// Scalar integer value. Example: 999
			return name * 1;
			
		}
		
		// As property or raw value
		return this.resolveProperty(name, value);
	}
	
	resolveProperty(propertyChain, data) {
		// Undefined must be handled by "default()" filter
		return propertyChain.trim().split(".")
			// Chain property using the result of parent, but parent is null or undefined
			.reduce((chainedValue, key) => chainedValue !== undefined && chainedValue !== null ? chainedValue[key] : chainedValue, data);
	}
	
	/**
	 * @param {string} value
	 * @return {boolean}
	 */
	isScalarString(value) {
		const firstChar = value.charAt(0);
		const lastChar = value.slice(-1);
		return firstChar === lastChar && firstChar === "\"" || firstChar === "'";
	}
	
	/**
	 * @param {string} value
	 * @return {boolean}
	 */
	isScalarInteger(value) {
		return Is.stringInteger(value);
	}
	
}
