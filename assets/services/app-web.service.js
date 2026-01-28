import {Is} from "../helpers/is.helper.js";
import {Exception} from "../core/exceptions.js";
import {Pagination} from "../core/pagination.js";

class AppWebService {
	
	apiUrl = "/api";// No ending slash
	
	constructor() {
		this.FORMAT = Object.freeze({
			ADMIN: "admin",
			RELATION: "relation",
		});
	}
	
	/**
	 * @returns {Promise<Object>}
	 */
	async getAuthenticatedUser() {
		try {
			return await this.requestGet(`/me`);
		} catch (error) {
			if( error instanceof ApiServerError && error.response.status === 401 ) {
				// Unauthorized is acceptable if user is not authenticated
				return null;
			}
			throw new ApiException("Unable to get authenticated user", error);
		}
	}
	
	/**
	 * @param {Object} authentication
	 * @returns {Promise<Object>}
	 */
	async authenticateUser(authentication) {
		return await this.requestPost(`/security/authenticate`, authentication);
		// try {
		// } catch( error ) {
		// 	throw new ApiException("Unable to authenticate user", error);
		// }
	}
	
	/**
	 * @returns {Promise<Object>}
	 */
	async disconnectUser() {
		try {
			return await this.requestPost(`/security/disconnect`, null);
		} catch (error) {
			debugger;
			throw new ApiException("Unable to disconnect user", error);
		}
	}
	
	/**
	 * @param {Object} question
	 * @returns {Promise<Object>}
	 */
	async addQuestion(question) {
		try {
			return await this.requestPost(`/question`, question);
		} catch (error) {
			throw new ApiException("Unable to create question", error);
		}
	}
	
	/**
	 *
	 * @param {Number} questionId
	 * @param {Object} answer
	 * @returns {Promise<Object>}
	 */
	async answerQuestion(questionId, answer) {
		try {
			return await this.requestPost(`/question/${questionId}/answer`, answer);
		} catch (error) {
			throw new ApiException("Unable to answer question", error);
		}
	}
	
	/**
	 * @param {Array<Number>} excludeQuestions
	 * @returns {Promise<Array>}
	 */
	async getRandomQuestions(excludeQuestions) {
		const excludedList = JSON.stringify(excludeQuestions);
		try {
			return await this.getList(`/question/random?limit=20&exclude=${excludedList}`);
		} catch (error) {
			throw new ApiException("Unable to load question list", error);
		}
	}
	
	/**
	 * Post ressource to API
	 *
	 * @param {String} path
	 * @param {Object} input
	 * @returns {Promise<Object>}
	 */
	requestPost(path, input) {
		return this.request(path, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: input ? JSON.stringify(input) : null,
		});
	}
	
	/**
	 * Patch ressource to API
	 *
	 * @param {String} path
	 * @param {Object} input
	 * @returns {Promise<Object>}
	 */
	requestPatch(path, input) {
		return this.request(path, {
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
		return this.request(path, {
			method: "DELETE",
			headers: {
				"Content-Type": "application/json",
			},
			body: input ? JSON.stringify(input) : null,
		});
	}
	
	/**
	 * Get ressources from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Array>}
	 */
	getList(path, query = null) {
		return this.requestGet(path, query);
	}
	
	/**
	 * Get ressources from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Array>}
	 */
	async getPaginatedList(path, query = null) {
		const response = await this.request(path, {
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
	 * Get ressources from API
	 *
	 * @param {String} path
	 * @param {Object|null} query
	 * @returns {Promise<Object|Array>}
	 */
	requestGet(path, query = null) {
		return this.request(path, {query});
	}
	
	/**
	 * Request API
	 *
	 * @param {String} path
	 * @param {?Object|null} options
	 * @returns {Promise<Object|Array>}
	 */
	async request(path, options = null) {
		let response = null;
		// Format options
		options = options || {};
		options.headers = options.headers || {};
		options.withResponseHeaders = options.withResponseHeaders || false;
		if( !options.headers["Accept"] ) {
			options.headers["Accept"] = "application/json";
		}
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
		try {
			if( response.status === 204 ) {
				return null;
			}
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

class ApiError extends Exception {
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
class ApiServerError extends ApiError {
	
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
		if( [400, 401].includes(response.status) ) {
			return await ApiValidationError.create(response);
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

export class ApiValidationError extends ApiUserServerError {
	
	constructor(response, errors) {
		super(response);
		this.errors = errors;
	}
	
	/**
	 * @param {Response} response
	 * @returns {Promise<ApiValidationError>}
	 */
	static async create(response) {
		let errors = [];
		try {
			const body = await response.json();
			if( body.hasOwnProperty("errors") ) {
				errors = body.errors;
			} else if( body.hasOwnProperty("error") ) {
				errors = [{message: body.error}];
			}
		} catch (error) {
			console.error("Unable to parse error contents", error);
		}
		return new ApiValidationError(response, errors);
	}
	
	getJoinedErrors(separator = "\n") {
		return this.errors.map(error => error.message).join(separator);
	}
	
	getMessage() {
		if( !this.errors.length ) {
			return super.getMessage();
		}
		if( this.errors.length === 1 ) {
			return `Validation error : ${this.errors[0].message}`;
		}
		const message = this.getJoinedErrors();
		
		return `Validation errors :\n${message}`;
	}
	
}

class ApiNetworkError extends ApiError {
	
	constructor(previous) {
		super(`API Network issue (${previous.message})`);
		
		this.previous = previous;
	}
	
}

class ApiClientError extends ApiError {
	
	constructor(response, previous) {
		super(`API Client issue (${previous.message})`, response);
		
		this.previous = previous;
	}
	
}

export const appWebService = new AppWebService();
