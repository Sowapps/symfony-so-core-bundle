import { Controller } from '@hotwired/stimulus';
import { Modal } from "bootstrap";
import {NotImplementedException} from "../exceptions.js";
import {domService} from "../../services/dom.service.js";
import {ApiValidationError} from "../../services/app-web.service.js";

export class AbstractController extends Controller {
	
	/**
	 * Report the validation exception to the main controller, so it could choose the way to notify the user
	 * @param exception
	 * @param title
	 * @param formController
	 */
	reportValidationException(exception, title, formController) {
		this.reportException(exception, title);
		if( exception instanceof ApiValidationError ) {
			formController.processValidationError(exception);
		}
	}
	
	dispatchEvent(event, detail = null, options = {}) {
		domService.dispatchEvent(this.element, event, detail, options);
	}
	
	/**
	 * Report the exception to the main controller, so it could choose the way to notify the user
	 */
	reportException(exception, title = null, options = {}) {
		console.error(exception, title);
		this.dispatchEvent("so.report.error", {title, error: exception, options});
	}
	
	/**
	 * Report the success to the main controller, so it could choose the way to notify the user
	 */
	reportSuccess(message, title = null, options = {}) {
		this.dispatchEvent("so.report.success", {title, message, options});
	}
	
	/**
	 * Start any server operation, so any button or form is disabled
	 */
	startOperating() {
		this.operating = true;
		this.renderOperating();
	}
	
	/**
	 * End any server operation, so any button or form can be enabled again
	 */
	endOperating() {
		this.operating = false;
		this.renderOperating();
	}
	
	/**
	 * @return {HTMLElement[]}
	 */
	getButtons	() {
		throw new NotImplementedException("Not implemented, you must implement this feature in child class");
	}
	
	renderOperating() {
		this.getButtons().forEach(button => {
			this.renderElementAccordingToOperatingMode(button);
		});
	}
	
	renderElementAccordingToOperatingMode(button) {
		button.disabled = this.operating;
	}
	
	fixSelect2(element) {
		// Fix placeholder
		// Fix focus on search field
		$(element).on('select2:open', () => {
			$('.select2-container.select2-container--open .select2-search__field').prop('placeholder', $(element).data('searchPlaceholder'));
			document.querySelector('.select2-search__field').focus();
		})
	}
	
	/**
	 * @deprecated Use localeService.getLocale()
	 */
	getLocale() {
		return $('html').attr('lang');
	}
	
	checkImage(file, constraints) {
		constraints = Object.assign({}, {
			allowedTypes: null,
			minWidth: 0,
			maxWidth: Infinity,
			minHeight: 0,
			maxHeight: Infinity,
		}, constraints);
		const deferred = jQuery.Deferred();
		
		if( constraints.allowedTypes && !constraints.allowedTypes.includes(file.type) ) {
			deferred.reject(t('avatarEditor.invalidFileType'));
			
		} else {
			const image = new Image();
			
			image.onload = function () {
				// Check if image is bad/invalid
				if( this.width + this.height === 0 ) {
					this.onerror();
					return;
				}
				
				// Check the image resolution
				if(
					constraints.minWidth <= this.width && this.width <= constraints.maxWidth &&
					constraints.minHeight <= this.height && this.height <= constraints.maxHeight
				) {
					deferred.resolve(true);
				} else {
					deferred.reject(t('avatarEditor.invalidFileResolution'));
				}
			};
			
			image.onerror = function () {
				deferred.reject(t('avatarEditor.invalidFileType'));
			}
			
			image.src = URL.createObjectURL(file);
		}
		
		return deferred.promise();
	}
	
	createElementModal(name) {
		return this.createModal($(this.element).data(name));
	}
	
	createModal(selector) {
		return new Modal(document.querySelector(selector));
	}
	
	/**
	 * @param $element
	 * @param name
	 * @return {Controller|null}
	 */
	getController($element, name) {
		return this.application.getControllerForElementAndIdentifier($element, name);
	}
	
	/**
	 * @param $element
	 * @return {FormController}
	 */
	getFormController($element) {
		return this.getController($element, "sowapps--so-core--form");
	}
	
}
