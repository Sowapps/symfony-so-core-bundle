/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */
import {Is} from "../../helpers/is.helper.js";

export class Portrait {
	
	constructor(classPrototype) {
		this.classPrototype = Is.function(classPrototype) ? classPrototype.prototype : classPrototype;
	}
	
	static for(className) {
		return new Portrait(className);
	}
	
	/**
	 * Use trait
	 * Unavailable features : private properties, private methods
	 *
	 * @param traitPrototype
	 */
	use(traitPrototype) {
		traitPrototype = Is.function(traitPrototype) ? traitPrototype.prototype : traitPrototype;
		for (const [property, propertyDescriptor] of Object.entries(Object.getOwnPropertyDescriptors(traitPrototype))) {
			if( property === "constructor" ) {
				// Constructor is always present but should never be used
				continue;
			}
			Object.defineProperty(this.classPrototype, property, propertyDescriptor);
		}
		
		return this;
	}
	
}
