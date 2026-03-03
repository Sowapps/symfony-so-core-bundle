import {Controller} from "@hotwired/stimulus";
import {domService} from "../../services/dom.service.js";
import {ApiValidationError, appWebService} from "../../services/app-web.service.js";
import {SecurityEvent, securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";

export default class extends Controller {
	static targets = ["form", "error"];
	static values = {
		messageAuthenticating: String,
		messageDelay: {type: Number, default: 20000},
	};
	
	connect() {
		// if( securityService.isAuthenticated() ) {
		// 	navigationService.navigateToIndex();
		// }
	}
	
	async submitAuthentication(event) {
		event.preventDefault();
		if( this.submittingAuthentication ) {
			return;
		}
		this.submittingAuthentication = true;
		const input = domService.getFormObject(this.formTarget);
		domService.disableForm(this.formTarget);
		domService.endFadeOut(this.errorTarget);
		
		try {
			/** @type {Object} */
			const authentication = await appWebService.requestPost(`/security/authenticate`, input, {withApi: true}, {authenticated: false});
			console.log("Auth token", authentication);
			this.formTarget.reset();
			securityService.authenticate(authentication.user, authentication.apiToken.token);
			// Redirect to another page, getting out of this main controller
			navigationService.redirectTo("/admin/dashboard");
		} catch (exception) {
			console.error("exception", exception);
			let message = null;
			if( exception instanceof ApiValidationError ) {
				// TODO Update
				message = "Unable to authenticate: " + exception.getJoinedErrors(", ");
			} else {
				message = exception.getMessage();
			}
			// Show error
			this.errorTarget.innerText = message;
			this.errorTarget.hidden = false;
			domService.fadeOut(this.errorTarget, this.messageDelayValue);
		}
		
		this.submittingAuthentication = false;
		domService.enableForm(this.formTarget);// Disable form until question is displayed
	}
	
}
