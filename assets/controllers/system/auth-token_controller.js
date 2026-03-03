import {Controller} from "@hotwired/stimulus";
import {ApiNetworkError, ApiUserServerError, appWebService} from "../../services/app-web.service.js";
import {securityService} from "../../services/security.service.js";

export default class extends Controller {
	static targets = ["status", "retryButton"];
	static values = {
		checking: String,
		authSuccess: String,
		errorNoToken: String,
		errorAccessDenied: String,
		errorNetwork: String,
		errorGeneric: String,
	};
	
	async connect() {
		this.connectUser();
	}
	
	async connectUser() {
		this.enableRetryButton(false);
		this.setStatus('info', this.checkingValue);
		
		if( !securityService.hasToken() ) {
			this.setStatus('danger', this.errorNoTokenValue);
			this.enableRetryButton();
			return;
		}
		const globalApiUrl = appWebService.apiUrl;// Save usual api URL to use an
		try {
			appWebService.apiUrl = "";// Allow using another endpoint than /api
			// Token is automatically loaded in service
			await appWebService.requestPost('/auth-api/connect', {});// Empty body, token is in header
			this.setStatus('success', this.authSuccessValue);
			window.location.reload();
		} catch (exception) {
			console.error("Exception while refreshing session auth", exception, exception.constructor.name);
			this.enableRetryButton();
			if( exception instanceof ApiNetworkError ) {
				// Error : Network issue
				this.setStatus('danger', this.errorNetworkValue);
			} else if( exception instanceof ApiUserServerError && [401, 403].includes(exception.statusCode) ) {
				// Error : Access denied
				this.setStatus('danger', this.errorAccessDeniedValue);
			} else {
				// Error: Generic error
				this.setStatus('danger', this.errorGenericValue);
			}
		} finally {
			// Restore the usual api url
			appWebService.apiUrl = globalApiUrl;
		}
	}
	
	enableRetryButton(enabled = true) {
		this.retryButtonTarget.hidden = !enabled;
	}
	
	setStatus(level, text) {
		console.log("setStatus", text);
		this.statusTarget.className = `alert alert-${level} d-flex align-items-center mb-3`;
		this.statusTarget.innerText = text;
		console.log("Status: " + this.statusTarget.innerText, this.statusTarget);
	}
}
