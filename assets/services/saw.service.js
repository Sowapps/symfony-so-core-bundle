import {domService} from "./dom.service.js";
import {Is} from "../helpers/is.helper.js";
import {stringService} from "./string.service.js";
import {Http} from "../helpers/http.helper.js";

class SawService {
	
	/**
	 * @type {Element}
	 */
	#listElement;
	
	/**
	 * @type {Object}
	 */
	#defaultVars = {};
	
	constructor() {
		const id = "SawTemplateList";
		this.#listElement = document.getElementById("#" + id);
		if( !this.#listElement ) {
			// Create list element and add to the DOM
			this.#listElement = document.createElement("div");
			this.#listElement.id = id;
			this.#listElement.hidden = true;
			document.body.append(this.#listElement);
		}
	}
	
	setDefaultVar(key, value) {
		if( value === null || value === undefined ) {
			delete this.#defaultVars[key];
		}
		this.#defaultVars[key] = value;
	}
	
	setLoading(element) {
		if( element.tagName === "TBODY" ) {
			const rowElement = document.createElement("tr");
			const cellElement = document.createElement("td");
			cellElement.colSpan = 99;
			rowElement.append(cellElement);
			element.replaceChildren(rowElement);
			element = cellElement;
		}
		const loadingTemplate = document.getElementById("TemplateLoadingContents");
		const loadingElements = domService.renderTemplate(loadingTemplate);
		element.replaceChildren(...loadingElements);
	}
	
	#parseDataParams(dataset) {
		const data = {};
		const regex = /^param\-(.+)$/;
		for (const key in dataset) {
			const parts = regex.exec(key);
			if( parts ) {
				const property = stringService.lowerFirst(parts[1]);
				data[property] = dataset[key];
			}
		}
		
		return data;
	}
	
	renderLastTitle(parameters = {}) {
		if( !this.lastTitleRendering ) {
			// Do nothing
			return;
		}
		const {template, params} = this.lastTitleRendering;
		parameters = {...params, ...parameters};
		document.title = stringService.replace(template, parameters);
	}
	
	/**
	 * @param {Element} target
	 * @param {string} path
	 * @param {Object|null} parameters
	 * @returns {Promise<void>}
	 */
	async assignTemplate(target, path, parameters) {
		sawService.setLoading(target);
		
		/**
		 * @type {Element[]}
		 */
		let elements = null;
		let elementPath = path;
		let params = {};// Content parameters
		let leafElements = null;
		let injectionCause = "Call to method";
		const templateHierarchy = {}; // From leaf to root
		this.lastTitleRendering = null; // Allow controller to dynamically re-render the title
		// From leaf requested template to top parent template
		do {
			if( templateHierarchy[elementPath] ) {
				// Infinite parent injection loop protection
				throw new Error(`Template ${elementPath} was already injected by ${templateHierarchy[elementPath]}`);
			}
			templateHierarchy[elementPath] = injectionCause;
			
			const template = await sawService.getTemplate(elementPath);
			
			// Template is a parent who is already in DOM
			const domWrapper = domService.queryMeOrChild(target, `[data-wrapping-template-id="${template.id}"]`);
			if( domWrapper ) {
				if( elements ) {
					// This template is present in DOM and should wrap children elements
					domWrapper.replaceChildren(...elements);
				} // Else this element is a leaf and is already present in DOM, we do nothing, there is no changes
				break;
			}
			
			params = {...params, ...this.#parseDataParams(template.dataset)};
			if( template.dataset.documentTitle && !this.lastTitleRendering ) {
				// There is a document title, with priority to lower template
				this.lastTitleRendering = {template: template.dataset.documentTitle, params};
				// Render title after all template nesting to get all params
			}
			if( template.dataset.bodyClass ) {
				document.body.className = template.dataset.bodyClass;
			}
			if( template.dataset.theme ) {
				document.documentElement.dataset.bsTheme = template.dataset.theme;
			}
			const templateElements = domService.renderTemplate(template, parameters);
			// Set templateId of elements to recognize them later
			templateElements.forEach(element => element.dataset.templateId = template.id);
			if( elements ) {
				// If there are elements, they are children and current is their parent
				const wrappingChild = templateElements
					.map(loopElement => domService.queryMeOrChild(loopElement, "[data-template-wrap]"))
					// .map(loopElement => loopElement.matches("[data-template-wrap]") ? loopElement : loopElement.querySelector("[data-template-wrap]"))
					.find(loopElement => !!loopElement);
				if( !wrappingChild ) {
					throw new Error(`Unable to build wrapper ${elementPath} for template ${path} : No wrapping element found in wrapper`);
				}
				wrappingChild.replaceChildren(...elements);
				wrappingChild.dataset.wrappingTemplateId = template.id;
			}
			elements = templateElements;
			if( !leafElements ) {
				leafElements = elements;
			}
			if( Is.set(template.dataset.templateWrapper) ) {
				// There is a parent containing this template
				injectionCause = "Injecting template " + elementPath;
				elementPath = template.dataset.templateWrapper;
			} else {
				// There is a root template
				target.replaceChildren(...elements);
				elementPath = null;
			}
		} while (elementPath);
		
		if( this.lastTitleRendering ) {
			this.renderLastTitle(params);
		}
		
		if( !Is.empty(leafElements) ) {
			const controllerElement = leafElements[0];
			if( controllerElement.dataset.controller ) {
				Object.entries(parameters).forEach(([key, value]) => {
					if( key.substring(0, 2) === "__" ) {
						return;
					}
					controllerElement.setAttribute(`data-${controllerElement.dataset.controller}-${key}-value`, value);
				});
			}
		}
		
	}
	
	async getTemplate(path) {
		const templateKey = this.#hashPath(path);
		const templateId = "Template_" + templateKey;
		
		if( !this.#listElement.querySelector("#" + templateId) ) {
			// Download template
			const queryParams = {};
			if( Object.keys(this.#defaultVars).length ) {
				queryParams.vars = this.#defaultVars;
			}
			const query = Http.formatQueryString(queryParams);
			const response = await fetch(`/api/saw/template/${path}` + query);
			if( !response.ok ) {
				throw new Error("Template not found (or generic error)");
			}
			const contents = await response.text();
			const templateNode = domService.castElement(contents);
			templateNode.id = templateId;
			this.#listElement.append(templateNode);
		}
		return this.#listElement.querySelector("#" + templateId);
	}
	
	#hashPath(str) {
		let hash = 0;
		for (let i = 0; i < str.length; i++) {
			hash = ((hash << 5) - hash + str.charCodeAt(i)) | 0;
		}
		return (hash >>> 0).toString(36);
	}
	
}

export const sawService = new SawService();
