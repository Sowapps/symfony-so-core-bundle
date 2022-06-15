import { Controller } from '@hotwired/stimulus';
import { isJquery } from "../vendor/orpheus/js/orpheus.js";
import { Modal } from "bootstrap";

export class AbstractController extends Controller {
	
	dispatchEvent(element, event, detail = null) {
		if( element ) {
			if( element instanceof NodeList ) {
				// Loop on all children
				element.forEach((itemElement) => this.dispatchEvent(itemElement, event, detail));
				return;
			}
			if( element._element ) {
				// Auto handle BS Modals
				element = element._element;
			} else if( isJquery(element) ) {
				// Auto handle jQuery Elements
				element = element[0];
			}
		}
		element.dispatchEvent(new CustomEvent(event, detail ? {detail: detail} : null));
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
	
}
