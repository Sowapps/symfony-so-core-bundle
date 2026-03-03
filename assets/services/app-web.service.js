import {Is} from "../helpers/is.helper.js";
import {Exception} from "../core/exceptions.js";
import {Pagination} from "../core/pagination.js";

class AppWebService {
	apiUrl = "/api";// No ending slash
	#bearerToken = null;
	
	constructor() {
		this.FORMAT = Object.freeze({
			ADMIN: "admin",
			RELATION: "relation",
		});
	}
	
	/**
	 * Set the bearer token and enable authentication for all future requests
	 */
	setBearerTokenAuthentication(token) {
		this.#bearerToken = token;
	}
	
	/**
	 * @returns {Promise<Object>}
	 */
	async disconnectUser() {
		try {
			return await this.requestPost(`/security/disconnect`, null);
		} catch (error) {
			debugger;// TODO conclude & remove test
			throw new ApiException("Unable to disconnect user", error);
		}
	}
	
	/**
	 * Post ressource to API
	 *
	 * @param {String} path
	 * @param {Object} input
	 * @param {Object|null} query
	 * @param {Object} options
	 * @returns {Promise<Object>}
	 */
	requestPost(path, input, query = null, options = {}) {
		console.debug("api.requestPost", path, input, query);
		return this.requestJson(path, Object.assign(options, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: input ? JSON.stringify(input) : null,
			query,
		}));
	}
	
	/**
	 * Patch ressource to API
	 *
	 * @param {String} path
	 * @param {Object} input
	 * @returns {Promise<Object>}
	 */
	requestPatch(path, input) {
		return this.requestJson(path, {
			method: "PATCH",
			headers: {
				"Content-Type": "application/json",
			},
			body: input ? JSON.stringify(input) : null,
		});
	}
	
	/**
	 * Post ressource to API
	 *
	 * @param {String} path
	 * @param {Object} input
	 * @returns {Promise<Object>}
	 */
	requestDelete(path, input = null) {
		return this.requestJson(path, {
			method: "DELETE",
			headers: {
				"Content-Type": "application/json",
			},
			body: input ? JSON.stringify(input) : null,
		});
	}
	
	/**
	 * Get resources from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Array>}
	 */
	getList(path, query = null) {
		return this.requestGet(path, query);
	}
	
	/**
	 * Get resources from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Array>}
	 */
	async getPaginatedList(path, query = null) {
		const response = await this.requestJson(path, {
			query,
			withResponseHeaders: true,
			headers: {"Accept-Pagination": 1},
		});
		const headers = response.headers;
		if( !headers || headers["pagination-page-current"] === undefined ) {
			throw new ApiClientError("Missing required pagination in headers");
		}
		const pagination = new Pagination(
			headers["pagination-page-current"] * 1,
			response.body.length,
			Math.max(headers["pagination-ressources-per-page"] * 1, 1),// If 0 entries, we show an empty first page
			headers["pagination-ressources-total"] * 1,
		);
		return {
			pagination,
			list: response.body,
		};
	}
	
	/**
	 * Get resources from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Object|Array>}
	 */
	requestGet(path, query = null) {
		return this.requestJson(path, {query});
	}
	
	/**
	 * Download file from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Object|Array>}
	 */
	async downloadFile(path, query = null) {
		// Format options
		let options = {query};
		options.withResponseHeaders = true;// Required to get disposition header
		
		// Request
		const response = await this.requestFile(path, options);
		if( !response ) {
			return null;
		}
		
		// Success
		const filename = this.extractFilename(response.headers["content-disposition"]);
		const objectUrl = URL.createObjectURL(response.body);
		
		// Create anchor to create an auto-clicked link
		try {
			const a = document.createElement("a");
			a.href = objectUrl;
			a.download = filename;
			document.body.appendChild(a);
			a.click();
			a.remove();
		} finally {
			URL.revokeObjectURL(objectUrl);
		}
	}
	
	/**
	 * Upload a file to API, send as multipart/form-data but expect a JSON response
	 *
	 * @param {String} path
	 * @param {FormData} form
	 * @param {Object|null} query
	 * @returns {Promise<Object|Array>}
	 */
	async uploadFile(path, form, query = null) {
		return this.requestJson(path, {
			method: "POST",
			body: form,
			query,
		});
	}
	
	extractFilename(contentDisposition) {
		if( !contentDisposition ) {
			return null;
		}
		// Ex: attachment; filename="export.csv"
		const m = /filename\*?=(?:UTF-8''|")?([^\";]+)/i.exec(contentDisposition);
		return m?.[1] ? decodeURIComponent(m[1].replace(/"/g, "")) : null;
	}
	
	/**
	 * Download file from API
	 *
	 * @param {String} path
	 * @param {Object|null} options
	 * @returns {Promise<Blob|Object|null>}
	 */
	async requestFile(path, options = null) {
		// Format options
		options = this.#formatRequestOptions(options);
		
		// Request
		let response = await this.request(path, options);
		if( !response ) {
			return null;
		}
		try {
			const body = await response.blob();
			if( options.withResponseHeaders ) {
				return {headers: Object.fromEntries(response.headers), body};
			}
			return body;
		} catch (error) {
			throw new ApiClientError(response, error);
		}
	}
	
	/**
	 * Request API
	 *
	 * @param {String} path
	 * @param {?Object|null} options
	 * @returns {Promise<Object|Array>}
	 */
	async requestJson(path, options = null) {
		// Format options
		options = this.#formatRequestOptions(options);
		if( !options.headers["Accept"] ) {
			options.headers["Accept"] = "application/json";
		}
		let response = await this.request(path, options);
		if( !response ) {
			return null;
		}
		try {
			const body = await response.json();
			if( options.withResponseHeaders ) {
				return {headers: Object.fromEntries(response.headers), body};
			}
			return body;
		} catch (error) {
			throw new ApiClientError(response, error);
		}
	}
	
	/**
	 * @param {Object|null} options
	 * @return {{headers: Object, withResponseHeaders: boolean}}
	 */
	#formatRequestOptions(options) {
		// Initialize only objects (no scalar) to prevent call of property on an undefined object
		options = options || {};
		options.headers = options.headers || {};
		options.withResponseHeaders = options.withResponseHeaders || false;
		
		return options;
	}
	
	/**
	 * @param {Object|null} options
	 * @return {Object}
	 */
	#formatFetchOptions(options) {
		options = this.#formatRequestOptions(options);
		
		// Automatically complete options with configured data
		const { authenticated = true } = options;
		console.log("authenticated", authenticated);
		if( authenticated && this.#bearerToken ) {
			options.headers['Authorization'] = 'Bearer ' + this.#bearerToken;
		}
		delete options.authenticated;
		
		return options;
	}
	
	/**
	 * Request API
	 *
	 * @param {String} path
	 * @param {?Object|null} options
	 * @returns {Promise<Response>}
	 */
	async request(path, options = null) {
		let response = null;
		// Format options
		options = this.#formatFetchOptions(options);
		// Request server
		try {
			const query = this.formatQueryString(options.query);
			response = await fetch(this.apiUrl + path + (query ? "?" + query : ""), options);
			// console.log("Response to " + path, response.ok, response.status, typeof response.status, response.statusText);
		} catch (error) {
			throw new ApiNetworkError(error);
		}
		// Process errors
		if( !response.ok ) {
			if( response.status < 500 ) {
				throw await ApiUserServerError.create(response);
			}
			throw new ApiServerError(response);
		}
		// Format response
		if( response.status === 204 ) {
			return null;
		}
		return response;
	}
	
	/**
	 * @param {object} query
	 * @return {null|string}
	 * @see https://jsfiddle.net/Loenix34/52hgsjt3/19/
	 */
	formatQueryString(query) {
		if( !Is.object(query) ) {
			return null;
		}
		return this.#appendDeepQueryParams(query, new URLSearchParams(), "").toString();
	}
	
	/**
	 * @param {object|scalar} value
	 * @param {URLSearchParams} params
	 * @param {string} prefix
	 * @return {URLSearchParams}
	 */
	#appendDeepQueryParams(value, params, prefix) {
		// Output examples : key=value array[]=value object[key]=value object[array][]=value
		if( Is.object(value) ) {
			// Array or object => loop
			const valueIsArray = Is.array(value);
			Object.entries(value)
				.forEach(([loopKey, loopValue]) => {
					// If it has no prefix => First prefix => no brackets
					// Else if current is an array => use brackets with no key
					// Else if current is an object => use brackets with key
					this.#appendDeepQueryParams(loopValue, params, prefix ? (prefix + (valueIsArray ? "[]" : "[" + loopKey + "]")) : loopKey);
				});
		} else {
			// Scalar => append
			params.append(prefix, value + "");
		}
		return params;
	}
	
}

export class ApiException extends Exception {
	
	getMessage() {
		const previousMessage = this.previous instanceof ApiValidationError ? this.previous.getMessage() : this.previous.message;
		return `${this.message} due to ${previousMessage}`;
	}
	
}

export class ApiError extends Exception {
	/**
	 * @var {Response}
	 */
	response;
	
	constructor(message, response) {
		super(message);
		
		this.response = response;
	}
	
	get statusCode() {
		return this.response.status;
	}
	
}

/**
 * Error from server, by default user could not get what is going wrong
 */
export class ApiServerError extends ApiError {
	
	constructor(response, message = null) {
		super(message || "API Request failed with code " + response.status, response);
	}
	
}

/**
 * Error from server that user could be noticed (4XX as 400, 401, 404, ...) because this is a client issue
 */
export class ApiUserServerError extends ApiServerError {
	
	/**
	 * @param {Response} response
	 * @returns {ApiUserServerError|ApiValidationError}
	 */
	static async create(response) {
		if( [400, 422].includes(response.status) ) {
			// Symfony currently returns 422 for validation errors
			return ApiValidationError.create(response);
		}
		let message = null;
		try {
			const body = await response.json();
			// Standard of application/problem+json by SymfonyCasts
			// @see https://symfonycasts.com/screencast/rest/application-problem
			if( body.type && Is.array(body.errors) ) {
				if( response.status === 409 && body.type === "error.fatal.versionMismatch" ) {
					return await new ApiVersionError(response, body.title, body.errors);
				}
				message = body.errors[0];
			} else if( body.error ) {
				// Symfony standard in case of server error
				message = body.error;
			} else if( body.message ) {
				// Is this a former standard? Pls confirm?
				message = body.message;
			} else {
				// Symfony standard in case of constraint violation error (422)
				message = body.detail;
			}
		} catch (exception) {
			console.warn("Unable to parse error from response", exception);
		}
		return new ApiUserServerError(response, message);
	}
	
}

/**
 * Client app is using an incompatible version
 */
export class ApiVersionError extends ApiUserServerError {
	
	/**
	 * @param {Response} response
	 * @param {string} title
	 * @param {array} errors
	 */
	constructor(response, title, errors) {
		super(response);
		this.title = title;
		this.errors = errors;
	}
	
	getMessage() {
		if( !this.errors.length ) {
			return super.getMessage();
		}
		if( this.errors.length === 1 ) {
			return `${this.title} : ${this.errors[0]}`;
		}
		const message = this.errors.join("\n");
		
		return `${this.title} :\n${message}`;
	}
}

class PropertyViolation {
	
	constructor(property, message) {
		this.property = property;
		this.message = message;
	}
	
}

/**
 * Validation error
 */
export class ApiValidationError extends ApiUserServerError {
	/**
	 * @var {PropertyViolation[]}
	 */
	#violations;
	
	constructor(response, message, violations) {
		super(response, message);
		this.#violations = violations;
	}
	
	get violations() {
		return this.#violations;
	}
	
	/**
	 * @param {Response} response
	 * @returns {ApiValidationError}
	 */
	static async create(response) {
		const body = await response.json();
		/**
		 * Using a custom validation exception format, not compatible with Symfony validation error format
		 * @see \App\Event\ExceptionSubscriber::onValidationException
		 */
		if( !Is.array(body.violations) || !body.message ) {
			throw new Error("Invalid validation error format from server, requires violations array and message string");
		}
		
		const violations = body.violations.map(violation => new PropertyViolation(violation.path, violation.message));
		return new ApiValidationError(response, body.message, violations);
	}
	
	getJoinedErrors(separator = "\n") {
		return this.violations.map(error => error.message).join(separator);
	}
	
}

export class ApiNetworkError extends ApiError {
	
	constructor(previous) {
		super(`API Network issue (${previous.message})`);
		
		this.previous = previous;
	}
	
}

export class ApiClientError extends ApiError {
	
	constructor(response, previous) {
		super(`API Client issue (${previous.message})`, response);
		
		this.previous = previous;
	}
	
}

export const appWebService = new AppWebService();
