import {AbstractDecorator, decorator} from "./decorator.service.js";
import {Is} from "../helpers/is.helper.js";

class MapperService {
	/**
	 * @param {Object} source
	 * @param {Class|Object} target
	 */
	map(source, target) {
		if( Is.class(target) ) {
			target = new target();
		}
		
		const sourcePropertyNames = this.#getReadablePropertyNames(source);
		const targetPropertyNames = this.#getWritablePropertyNames(target);
		// const targetPropertySet = new Set(targetPropertyNames);
		console.log("sourcePropertyNames", sourcePropertyNames, "targetPropertyNames", targetPropertyNames);
		
		for (const propertyName of sourcePropertyNames) {
			if( !targetPropertyNames.includes(propertyName) ) {
				continue;
			}
			
			if( this.#isExcluded(source, propertyName) || this.#isExcluded(target, propertyName) ) {
				continue;
			}
			
			// IA added this, but it seems redundant
			// if (!this.#canReadProperty(source, propertyName) || !this.#canWriteProperty(target, propertyName)) {
			// 	continue;
			// }
			
			const value = this.#readPropertyValue(source, propertyName);
			this.#writePropertyValue(target, propertyName, value);
		}
		
		return target;
	}
	
	/**
	 * Unmaps a class instance to a plain object.
	 *
	 * @param {Object} source
	 * @returns {Object}
	 */
	unmap(source) {
		if( !this.#isObjectLike(source) ) {
			throw new TypeError("MapperService.unmap() expects source to be an object.");
		}
		
		const target = {};
		const sourcePropertyNames = this.#getReadablePropertyNames(source);
		
		for (const propertyName of sourcePropertyNames) {
			if( this.#isExcluded(source, propertyName) ) {
				continue;
			}
			
			if( !this.#canReadProperty(source, propertyName) ) {
				continue;
			}
			
			target[propertyName] = this.#readPropertyValue(source, propertyName);
		}
		
		return target;
	}
	
	#isObjectLike(value) {
		return value !== null && (typeof value === "object" || typeof value === "function");
	}
	
	#getReadablePropertyNames(source) {
		const names = new Set();
		
		for (const key of Object.keys(source)) {
			names.add(key);
		}
		
		for (const key of this.#getPrototypePropertyNames(source)) {
			if( this.#canReadProperty(source, key) ) {
				names.add(key);
			}
		}
		
		return Array.from(names);
	}
	
	#getWritablePropertyNames(target) {
		const names = new Set();
		
		for (const key of Object.keys(target)) {
			if( this.#canWriteProperty(target, key) ) {
				names.add(key);
			}
		}
		
		for (const key of this.#getPrototypePropertyNames(target)) {
			if( this.#canWriteProperty(target, key) ) {
				names.add(key);
			}
		}
		
		return Array.from(names);
	}
	
	#getPrototypePropertyNames(object) {
		const names = new Set();
		let current = Object.getPrototypeOf(object);
		
		while (current && current !== Object.prototype) {
			for (const name of Object.getOwnPropertyNames(current)) {
				if( name === "constructor" ) {
					continue;
				}
				
				names.add(name);
			}
			
			current = Object.getPrototypeOf(current);
		}
		
		return Array.from(names);
	}
	
	#canReadProperty(object, propertyName) {
		const descriptor = this.#findPropertyDescriptor(object, propertyName);
		
		if( !descriptor ) {
			return false;
		}
		
		if( typeof descriptor.get === "function" ) {
			return true;
		}
		
		if( "value" in descriptor ) {
			return typeof descriptor.value !== "function";
		}
		
		return false;
	}
	
	#canWriteProperty(object, propertyName) {
		const descriptor = this.#findPropertyDescriptor(object, propertyName);
		
		if( !descriptor ) {
			return false;
		}
		
		if( typeof descriptor.set === "function" ) {
			return true;
		}
		
		if( "value" in descriptor ) {
			return descriptor.writable === true;
		}
		
		return false;
	}
	
	#readPropertyValue(object, propertyName) {
		const getterName = this.#buildGetterName(propertyName);
		
		if( typeof object[getterName] === "function" ) {
			return object[getterName]();
		}
		
		return object[propertyName];
	}
	
	#writePropertyValue(object, propertyName, value) {
		const setterName = this.#buildSetterName(propertyName);
		
		if( typeof object[setterName] === "function" ) {
			object[setterName](value);
			return;
		}
		
		object[propertyName] = value;
	}
	
	#buildGetterName(propertyName) {
		return `get${this.#capitalize(propertyName)}`;
	}
	
	#buildSetterName(propertyName) {
		return `set${this.#capitalize(propertyName)}`;
	}
	
	#capitalize(value) {
		return value.charAt(0).toUpperCase() + value.slice(1);
	}
	
	#findPropertyDescriptor(object, propertyName) {
		let current = object;
		
		while (current) {
			const descriptor = Object.getOwnPropertyDescriptor(current, propertyName);
			
			if( descriptor ) {
				return descriptor;
			}
			
			current = Object.getPrototypeOf(current);
		}
		
		return null;
	}
	
	#isExcluded(object, propertyName) {
		const constructor = this.#resolveConstructor(object);
		const definition = decorator.getDecoratorDefinition(constructor);
		
		return definition.getPropertyDecorator(propertyName, ExcludeDecorator);
	}
	
	#resolveConstructor(object) {
		if( typeof object === "function" ) {
			return object;
		}
		
		return object?.constructor || null;
	}
	
	
	/**
	 * @param {Object} from
	 * @param {Class|Object} to
	 */
	// map(from, to) {
	// 	console.log("Map DTO", from, to, typeof to);
	// 	// console.log("Map DTO", from, to, typeof to);
	// 	// console.dir(to);
	// 	const options = { excludeExtraneousValues: true };
	//
	// 	if( Is.class(to) ) {
	// 		// "to" is the class itself
	// 		return plainToClass(to, from, options);
	// 	}
	// 	if( Is.object(to) ) {
	// 		// "to" is an object (instance or standard)
	// 		return plainToClass(to, from);
	// 	}
	// 	throw new Error("Unable to find an appropriate conversion to map to this target");
	// }
}

// Decorator list

export class Decorator {
	static Exclude() {
		return new ExcludeDecorator();
	}
	static Type(type) {
		return new TypeDecorator(type);
	}
}

class ExcludeDecorator extends AbstractDecorator {

}

class TypeDecorator extends AbstractDecorator {
	type;
	
	constructor(type) {
		super();
		this.type = type;
	}
}

// Service
export const mapper = new MapperService();
