/**
 * Decorate class properties with some decorators (like attributes or annotations)
 */
class DecoratorService {
	decoratorProperty;
	
	constructor() {
		this.decoratorProperty = Symbol("dto_decorators");
	}
	
	decorate(TargetClass) {
		if( typeof TargetClass !== "function" ) {
			throw new TypeError("decorate() expects a class or constructor.");
		}
		
		return new ClassDecoratorBuilder(TargetClass);
	}
	
	/**
	 * @param targetClass
	 * @return {ClassDecoratorDefinition}
	 */
	getDecoratorDefinition(targetClass) {
		if( typeof targetClass !== "function" ) {
			return {};
		}
		const decoratorProperty = this.decoratorProperty;
		
		if( !Object.prototype.hasOwnProperty.call(targetClass, decoratorProperty) ) {
			Object.defineProperty(targetClass, decoratorProperty, {
				value: new ClassDecoratorDefinition(),
				writable: false,
				enumerable: false,
				configurable: false,
			});
		}
		
		return targetClass[decoratorProperty];
	}
}

/**
 * All decorators should inherits this class
 */
export class AbstractDecorator {
}

// Service
export const decorator = new DecoratorService();

// Decorating classes

/**
 * Builder for decorators of a class
 */
class ClassDecoratorBuilder {
	/**
	 * @param {Function} TargetClass
	 */
	constructor(TargetClass) {
		this.targetClass = TargetClass;
	}
	
	/**
	 * @param {string} propertyName
	 * @param  {...AbstractDecorator} decorators
	 * @returns {ClassDecoratorBuilder}
	 */
	set(propertyName, ...decorators) {
		if( typeof propertyName !== "string" || propertyName.length === 0 ) {
			throw new TypeError("Decorator property name must be a non-empty string.");
		}
		
		const validDecorators = decorators.filter(Boolean);
		if( !validDecorators.length ) {
			return this;
		}
		
		const definition = decorator.getDecoratorDefinition(this.targetClass);
		definition.setToProperty(propertyName, ...decorators);
		
		return this;
	}
	
	/**
	 * @param {string} propertyName
	 * @returns {Object[]}
	 */
	get(propertyName) {
		const metadata = decorator.getDecoratorDefinition(this.targetClass);
		
		return metadata[propertyName] ? [...metadata[propertyName]] : [];
	}
	
	/**
	 * @returns {Object}
	 */
	all() {
		const metadata = decorator.getDecoratorDefinition(this.targetClass);
		
		return {...metadata};
	}
}


class ClassDecoratorDefinition {
	propertyDecorators = {};
	
	/**
	 * @param {string} name
	 * @param  {...AbstractDecorator} decorators
	 * @returns {ClassDecoratorDefinition}
	 */
	setToProperty(name, ...decorators) {
		if( typeof name !== "string" || name.length === 0 ) {
			throw new TypeError("Decorator property name must be a non-empty string.");
		}
		
		const validDecorators = decorators.filter(Boolean);
		
		if( validDecorators.length === 0 ) {
			return this;
		}
		
		this.propertyDecorators[name] = decorators;
		
		return this;
	}
	
	/**
	 * @param name
	 * @return {AbstractDecorator[]}
	 */
	getForProperty(name) {
		return this.propertyDecorators[name] || [];
	}
	
	getPropertyDecorator(name, decoratorClass) {
		const decorators = this.getForProperty(name);
		return decorators.find(decorator => decorator instanceof decoratorClass);
	}
	
}
