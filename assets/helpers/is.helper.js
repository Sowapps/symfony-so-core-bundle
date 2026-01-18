export class Is {
	
	static defined(v) {
		return v !== undefined;
	}
	
	/**
	 * @param value
	 * @return {boolean}
	 */
	static empty(value) {
		if( Is.object(value) ) {
			value = Object.keys(value);
		}
		if( Is.array(value) ) {
			return !value.length;
		}
		return !value;
	}
	
	static set(v) {
		return this.defined(v) && v !== null;
	}
	
	static scalar(obj) {
		return (/string|number|boolean/).test(typeof obj);
	}
	
	static stringInteger(value) {
		return !isNaN(value);
	}
	
	static string(v) {
		return typeof (v) === "string";
	}
	
	static object(v) {
		return v != null && typeof (v) === "object";
	}
	
	static pureObject(v) {
		return this.object(v) && v.constructor === Object;
	}
	
	static array(v) {
		return this.object(v) && v.constructor === Array;
	}
	
	static iterable(value) {
		// checks for null and undefined
		if( value == null ) {
			return false;
		}
		return typeof value[Symbol.iterator] === "function";
	}
	
	static function(v) {
		return typeof (v) === "function";
	}
	
	static domElement(obj) {
		return this.object(obj) && obj instanceof HTMLElement;
	}
	
	static jquery(v) {
		return this.object(v) && typeof (v.jquery) !== "undefined";
	}
	
}
