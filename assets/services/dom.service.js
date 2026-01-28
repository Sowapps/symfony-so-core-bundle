import {Is} from "../helpers/is.helper.js";
import {Process} from "../helpers/process.helper.js";
import {StringTemplate} from "../core/StringTemplate.js";
import {Deferred} from "../core/event/Deferred.js";
import {stringService} from "./string.service.js";

class DomService {
	
	/**
	 * Update README-Saw.md relying on this list
	 * @see /README-Saw.md for documentation
	 */
	filters = {
		"default": (value, defaultValue) => {
			return value || defaultValue;
		},
		"truncate": (value, length, ellipsis = "") => {
			const isCut = value.length > length;
			return value.substring(0, length) + (isCut ? ellipsis : "");
		},
		"date": value => {
			if( Is.object(value) ) {
				// The first token can be a filter applied on the whole object; it happens here if the property has the same name as the filter
				return value.date;
			}
			return new Date(value).toLocaleString();
		},
		"contains": (list, item) => {
			return Is.array(list) && list.includes(item);
		},
		"then": (value, then) => {
			return value ? then : "";
		},
		"upper": value => {
			return value.toUpperCase();
		},
		"attr": value => {
			return stringService.escapeAttribute(value);
		},
		"length": values => {
			return values.length;
		},
		"join": (values, separator) => {
			if( !Is.array(values) ) {
				throw new Error("Value must be an array");
			}
			return values.join(separator);
		},
		"text": (value) => {
			if( !value ) {
				return "";
			}
			return stringService.escapeHtml(value);
		},
		"url_host": (value) => {
			if( !value ) {
				return "";
			}
			const location = this.getLocation(value);
			return location.host;
		},
	};
	
	/**
	 * Add possibility to select element itself
	 *
	 * @param {Element} element
	 * @param {string} selector
	 * @return {Element|null}
	 */
	queryMeOrChild(element, selector) {
		return element.matches(selector) ? element : element.querySelector(selector);
	}
	
	/**
	 * Add possibility to select element itself
	 *
	 * @param {Element} element
	 * @param {string} selector
	 * @return {Element|null}
	 */
	queryMeOrParent(element, selector) {
		return element.matches(selector) ? element : element.closest(selector);
	}
	
	on(selector, eventName, container = document) {
		if( Is.domElement(selector) ) {
			container = selector;
			selector = null;
		} else if( !Is.string(eventName) ) {
			throw new Error("Second parameter must be a string eventName");
		}
		const deferred = new Deferred(eventName, container);
		container.addEventListener(eventName, deferred.listener = async event => {
			if( !event.target ) {
				return;
			}
			if( !selector || this.queryMeOrParent(event.target, selector) ) {
				await deferred.resolve(event);
			}
		});
		
		return deferred.promise();
	}
	
	/**
	 * @param {DeferredPromise} promise
	 * @return {DeferredPromise}
	 */
	off(promise) {
		const deferred = promise.getRootDeferred();
		deferred.container.removeEventListener(deferred.type, deferred.listener);
	}
	
	getLocation(uri) {
		const link = document.createElement("a");
		link.href = uri;
		return link;
	}
	
	disableForm(form) {
		[...form.elements].forEach(element => {
			element.dataset.__previouslyDisabled = element.disabled;
			element.disabled = true;
		});
	}
	
	enableForm(form) {
		[...form.elements].forEach(element => {
			element.disabled = element.dataset.__previouslyDisabled === "true";
			delete element.dataset.__previouslyDisabled;
		});
	}
	
	getFormData($form) {
		return new FormData($form);
	};
	
	getFormObject($form) {
		const formData = this.getFormData($form);
		const object = {};
		const buildObject = function (data, keys, value) {
			let key = keys.shift();
			if( !keys.length ) {
				if( !key ) {
					// Last empty returns value
					return value;
				}
				// Lone key with no brackets
				data[key] = value;
				return data;
			}
			value = buildObject((data && data[key]) || null, keys, value);
			if( data === null ) {
				if( !key ) {
					// Empty key in middle of string means []
					return [value];
				}
				return {[key]: value};
			}
			data[key] = value;
			return data;
		};
		formData.forEach((value, name) => {
			const inputs = $form.querySelectorAll(`input[name=${this.#escapeInputName(name)}]`);
			if( inputs.length === 1 ) {
				// Only if one input has this name
				const $input = inputs[0];
				if( $input.matches("input[type=checkbox]") ) {
					// Process checkbox case - recalculate the true value
					// Real value is boolean testing if checked
					value = $input.hasAttribute("value") ? $input.value : true;
				}
			}// Can not process multiple inputs with same name
			this.#assignObjectByKeyChain(object, this.parseFormNameChain(name), value);
		});
		return object;
	};
	
	#escapeInputName(name) {
		return name.replace(/([\[\]])/g, "\\$1");
	}
	
	#assignObjectByKeyChain(object, keys, value) {
		if( !keys.length ) {
			// Key is leaf
			return value;
		}
		let key = keys.shift();
		const subValue = this.#assignObjectByKeyChain((object && object[key]) || null, keys, value);
		if( object === null ) {
			if( !key ) {
				// Empty key in middle of string means []
				return [subValue];
			}
			return {[key]: subValue};
		}
		if( Is.array(object) ) {
			object.push(subValue);
		} else {
			object[key] = subValue;
		}
		return object;
	}
	
	// #assignObjectByKeyChain(object, keys, value) {
	// 	let key = keys.shift();
	// 	if( !keys.length ) {
	// 		// Key is leaf
	// 		if( !key ) {
	// 			// Last empty returns value
	// 			return value;
	// 		}
	// 		// Lone key with no brackets
	// 		object[key] = value;
	// 		return object;
	// 	}
	// 	value = this.#assignObjectByKeyChain((object && object[key]) || null, keys, value);
	// 	if( object === null ) {
	// 		if( !key ) {
	// 			// Empty key in middle of string means []
	// 			return [value];
	// 		}
	// 		return {[key]: value};
	// 	}
	// 	object[key] = value;
	// 	return object;
	// }
	
	/**
	 * Parse a form name chain to an array
	 *
	 * @param {string} name The form name string to parse. Example: user[roles][]
	 * @return {string[]} The array of keys in form name. Example: ['user', 'roles', '']
	 */
	parseFormNameChain(name) {
		return [...name.matchAll(/^([^\[]+)|\[([^\[\]]*)\]/g)].map(group => group[1] || group[2]);
	}
	
	endFadeOut(...elements) {
		elements.forEach(element => {
			element.classList.remove("fade-out");
			element.hidden = true;
		});
	}
	
	async fadeOut(element, delay, remove) {
		await Process.wait(delay);
		element.classList.add("fade-out");
		await Process.wait(500);
		if( remove ) {
			element.remove();
		} else {
			this.endFadeOut(element);
		}
	}
	
	detach($element) {
		if( !$element.parentElement ) {
			return false;
		}
		return $element.parentElement.removeChild($element);
	}
	
	castElementTemplate(fragment) {
		const template = document.createElement("template");
		template.innerHTML = fragment.trim();// Any whitespace will convert it to text
		return template.content;
	}
	
	castElement(fragment) {
		return this.castElementTemplate(fragment).firstChild;
	}
	
	castElementNodes(fragment) {
		return [...this.castElementTemplate(fragment).children];
	}
	
	createElement(tag, className, attributes) {
		const $element = document.createElement(tag);
		if( className ) {
			$element.className = className;
		}
		if( attributes && typeof attributes === "object" ) {
			Object.entries(attributes).forEach(([key, value]) => {
				$element.setAttribute(key, value);
			});
		}
		return $element;
	}
	
	getViewportSize() {
		// https://stackoverflow.com/questions/1248081/how-to-get-the-browser-viewport-dimensions
		return {
			width: Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0),
			height: Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0),
		};
	}
	
	parseArguments(list) {
		// Remove the first level of quoting
		const length = list.length;
		const quotes = ["\"", "'"];
		const quoting = [];
		let token = "";
		let tokens = [];
		// Loop each char to wrap the whole string for a token
		for (let i = 0; i < length; i++) {
			const char = list[i];
			let feedToken = true;
			if( quotes.includes(char) ) {
				if( quoting[0] === char ) {
					// End opened quoting
					quoting.shift();
					feedToken = !!quoting.length;
				} else {
					// Start new quoting
					feedToken = !!quoting.length;
					quoting.unshift(char);
				}
			}
			if( char === " " && !quoting.length ) {
				// Separator encountered and not quoting
				feedToken = false;
				tokens.push(token);
				token = "";
			}
			if( feedToken ) {
				token += char;
			}
		}
		if( token ) {
			// End of string add the started token
			tokens.push(token);
		}
		return tokens;
	}
	
	resolveCondition(condition, data) {
		let conditionParts = this.parseArguments(this.#formatTemplateString(condition, data));
		const invert = conditionParts[0] === "not";
		if( invert ) {
			conditionParts = conditionParts.shift();
		}
		let rawCondition;
		if( conditionParts.length > 2 ) {
			// Comparison operator with 3 tokens
			let [value1, operator, value2] = conditionParts;
			if( !["=", "==", "==="].includes(operator) ) {
				throw new Error(`Unknown operator "${operator}"`);
			}
			rawCondition = value1 === value2;
		} else {
			// Simple boolean property
			let [property] = conditionParts;
			rawCondition = Number(!!data[property]);
		}
		return invert ^ Number(!!rawCondition);
	}
	
	#formatTemplateString(string, data) {
		data = data || {};
		// Clean contents
		let template = string.replace(/>\s+</ig, "><");
		// Extract sub-templates as data
		let subTemplateId = 0;
		template = template.replace(/<template[^\>]*>.+?<\/template>/gs, matched => {
			subTemplateId++;
			const key = `__sub_template_${subTemplateId}__`;
			data[key] = matched;
			
			return `{${key}}`;
		});
		
		return this.renderString(template, data);
	}
	
	renderString(template, data) {
		if( !template ) {
			// Message does not exist
			return template;
		}
		const stringTemplate = new StringTemplate(this.filters, this);
		return stringTemplate.render(template, data);
	}
	
	getElement($element) {
		return Is.string($element) ? document.querySelector($element) : $element;
	}
	
	fillForm($container, data, pattern = null) {
		if( !data || typeof data !== "object" ) {
			throw "Parameter data must be an object";
		}
		$container = this.getElement($container);
		Object.entries(data).forEach(([key, value]) => {
			const name = pattern ? pattern.replace("%s", key) : key;
			$container.querySelectorAll("[name=\"" + name + "\"]")
				.forEach(($element) => {
					this.assignValue($element, value);
				});
		});
	};
	
	assignValue($element, value) {
		if( !($element instanceof Element) ) {
			throw "Parameter $element must be an Element (DOM)";
		}
		const elementTag = $element.tagName.toLowerCase();
		let changed = false;
		if( elementTag === "img" || elementTag === "iframe" ) {
			$element.setAttribute("src", value);
		} else if( elementTag === "a" ) {
			$element.setAttribute("href", value);
		} else if( this.isCheckbox($element) ) {
			if( $element.value.toLowerCase() !== "on" ) {
				// Not default browser value
				$element.checked = Is.array(value) ? value.includes($element.value) : $element.value === value;
			} else {
				// + to convert to int, !! to convert to boolean
				$element.checked = !!+value;
			}
			changed = true;
		} else if( elementTag === "select" ) {
			const values = Is.array(value) ? value : [value];
			// Create all missing options
			for (const selectedValue of values) {
				let $option = $element.querySelector("option[value=\"" + selectedValue + "\"]");
				if( !$option ) {
					// Automatically create new options
					$option = this.createElement("option");
					$option.innerText = selectedValue;
					$option.value = selectedValue;
					$element.append($option);
				}
			}
			// Set selected property for all options (to remove previous selected)
			$element.querySelectorAll("option").forEach($option => {
				$option.selected = values.includes($option.value);
			});
			changed = true;
		} else if( this.isInput($element) ) {
			// Fix issue in some dynamic forms
			// input was filled but the change event not called
			// dispatchEvent adds compatibility with Stimulus (raw event listener)
			$element.value = value;
			changed = true;
		} else {
			// Simple html element
			$element.innerText = value || $element.dataset.emptyText;
		}
		
		if( changed ) {
			$element.dispatchEvent(new Event("change"));
		}
	}
	
	getSiblings($element, filter = null) {
		const filterFunction = typeof filter === "function" ? filter : null;
		const filterSelector = typeof filter === "string" ? filter : null;
		$element = this.getElement($element);
		return [...$element.parentNode.children].filter(($child) =>
			$child !== $element && (!filterFunction || filterFunction($child)) && (!filterSelector || $child.matches(filterSelector)),
		);
	}
	
	renderTemplateElement($template, data, prefix) {
		// Resolve conditional displays
		$template.querySelectorAll("[data-if]").forEach($element => {
			if( !this.resolveCondition($element.dataset.if, data) ) {
				$element.remove();
			}
		});
		// Resolve else displays
		$template.querySelectorAll("[data-else]").forEach($element => {
			if( $element.dataset.else === "siblings" ) {
				const conditionalSiblings = this.getSiblings($element, "[data-if]");
				if( conditionalSiblings.length ) {
					$element.remove();
				}
			} else {
				throw new Error("Unknown template data-else attribute value, supports only: \"siblings\"");
			}
		});
		// Resolve conditioned attribute displays by re-assigning false
		["disabled", "checked", "readonly"]
			.forEach(property => {
				$template.querySelectorAll(`[${property}=false]`).forEach($element => $element[property] = false);
			});
		
		// Fix image loading preventing
		$template.querySelectorAll("[data-src]").forEach($element => {
			$element.src = $element.dataset.src;
			delete $element.dataset.src;
		});
		// Fix link crawling
		$template.querySelectorAll("[data-href]").forEach($element => {
			$element.href = $element.dataset.href;
			delete $element.dataset.href;
		});
		// Resolve values in content
		if( prefix ) {
			this.fillForm($template, data, this.getPrefixPattern(prefix));
		}
	}
	
	getPrefixPattern(prefix) {
		return prefix + "[%s]";
	}
	
	async loadTemplate(key, $target) {
		const response = await fetch("/api/template/" + key);
		if( !response.ok ) {
			throw new Error("Invalid response");
		}
		// const $target = document.querySelector(target);
		$target.innerHTML = await response.text();
		
		return $target;
	}
	
	global() {
		return $(window);
	}
	
	extractTemplate(template) {
		let isHtml = true;
		if( Is.jquery(template) ) {
			template = template[0];
		}
		if( Is.pureObject(template) && template.template !== undefined && template.isHtml !== undefined ) {
			return template;
		}
		if( Is.domElement(template) ) {
			if( template.matches("template") ) {
				let content = template.innerHTML.trim();
				if( !content ) {
					content = template.innerText.trim();
					if( content ) {
						isHtml = false;
					}
				}
				if( !content ) {
					console.error("Template has no contents", template);
				}
				template = content;
			} else {
				template = template.outerHTML;
			}
		}
		return {template: template, isHtml: isHtml};
	}
	
	/**
	 * @param template
	 * @param data
	 * @param options
	 * @returns {Element[]}
	 */
	renderTemplate(template, data = null, options = {}) {
		// TODO [Low] Require unit tests, for now, use Dev Composer page or test api dev page
		if( !template ) {
			throw new Error("Empty template");
		}
		if( !Is.object(options) ) {
			options = {prefix: options};
		}
		options = Object.assign({
			prefix: null,
			wrap: false,
			immediate: true,// Some lib does not handle async
		}, options);
		let {template: templateString, isHtml} = this.extractTemplate(template);
		let renderedTemplate = this.#formatTemplateString(templateString, data);
		// Create jquery object preventing jquery to preload images
		renderedTemplate = renderedTemplate.replace("\\ssrc=", " data-src=");
		// If using text, we need to create a text node to use jQuery
		let elements = isHtml ? this.castElementNodes(renderedTemplate) : [document.createTextNode(renderedTemplate)];
		// if( options.wrap ) {
		// 	const $wrapper = this.createElement("div", "template-contents");
		// 	$wrapper.append($item);
		// 	$item = $wrapper;
		// }
		// Direct in DOM Element
		// $item.renderingTemplate = {template: templateString, options: options};
		elements.forEach(element => this.renderTemplateElement(element, data, options.prefix));
		
		return elements;
	}
	
	renderTemplateAsString(template, data) {
		let {template: templateString} = this.extractTemplate(template);
		return this.#formatTemplateString(templateString, data);
	}
	
	isCheckbox($element) {
		return $element.tagName.toLowerCase() === "input" && $element.getAttribute("type") === "checkbox";
	}
	
	isInput($element) {
		return ["input", "select", "textarea"].includes($element.tagName.toLowerCase());
	}
	
	getInputs($element) {
		return $element.querySelectorAll("input,select,textarea");
	}
	
	/**
	 * @param {Element|string} $element
	 * @param {string|Array<string>} classList
	 * @param {boolean|null} toggle True to add, false to remove, null to invert
	 */
	toggleClass($element, classList, toggle) {
		$element = this.oneElement($element);
		if( typeof classList === "string" ) {
			classList = classList.split(" ");
		}
		for (const cssClass of classList) {
			$element.classList.toggle(cssClass, toggle);
		}
	}
	
	toggle(elements, show) {
		this.allElements(elements)
			.forEach(element => element.hidden = !show);
	}
	
	showElement($element) {
		$element.hidden = false;
	}
	
	oneElement(element, nullable) {
		if( element ) {
			if( Is.string(element) ) {
				// Selector
				element = document.querySelector(element);
			} else if( Is.jquery(element) ) {
				// jQuery object
				element = element[0];
			}
		}
		if( !element ) {
			return nullable ? new NullElement() : null;
		}
		
		return element;
	}
	
	allElements(elements) {
		if( elements ) {
			if( Is.string(elements) ) {
				// Selector
				elements = document.querySelectorAll(elements);
			} else if( Is.jquery(elements) ) {
				// jQuery object
				elements = elements.get();
			}
			if( elements instanceof NodeList ) {
				// NodeList
				elements = [...elements];
			} else {
				// Single element
				elements = [elements];
			}
		}
		
		return elements || [];
	}
	
	buildCustomEvent(event, detail = null, options = {}) {
		if( detail ) {
			options.detail = detail;
		}
		return new CustomEvent(event, options);
	}
	
	dispatchEvent(element, event, detail = null, options = {}) {
		if( element ) {
			if( Is.iterable(element) && !Is.array(element) ) {
				// Convert any iterable to array
				element = [...element];
			}
			if( Is.array(element) ) {
				// Loop on all elements
				element.forEach((itemElement) => this.dispatchEvent(itemElement, event, detail));
				return;
			}
			if( element._element ) {
				// Auto handle BS Modals
				element = element._element;
			}
		}
		if( options.bubbles === undefined ) {
			// Default is to bubble (event goes up to parents)
			options.bubbles = true;
		}
		element.dispatchEvent(this.buildCustomEvent(event, detail, options));
	}
	
	resetForm($form) {
		$form.classList.remove("was-validated");
		$form.reset();
	}
	
}

/**
 * Fake DOM element doing nothing when querying a non-existing element and allowing it to be null, it's still allowing chaining
 */
class NullElement {
	
	addEventListener() {
	}
	
	dispatchEvent() {
	}
	
}

export const domService = new DomService();
