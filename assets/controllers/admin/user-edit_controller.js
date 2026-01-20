import {sawService} from "../../services/saw.service.js";
import {domService} from "../../services/dom.service.js";
import {appWebService} from "../../services/app-web.service.js";
import {securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";
import {Exception} from "../../core/exceptions.js";
import {AbstractPageController} from "../../core/controller/controllers.js";

export default class extends AbstractPageController {
	static targets = ["content", "template", "form", "profileFormReports", "securityFormReports", "passwordFormReports"];
	static values = {
		id: Number,
	};
	
	connect() {
		// TODO Check permissions
		if( !securityService.isAuthenticated() ) {
			navigationService.navigate("/");
			return;
		}
		this.user = null;
		
		this.load();
	}
	
	async load() {
		sawService.setLoading(this.contentTarget);
		
		const user = await appWebService.getList(`/user/${this.idValue}`);
		this.updateUser(user);
	}
	
	updateUser(user) {
		this.user = user;
		sawService.renderLastTitle({page: this.user.label});
		const elements = domService.renderTemplate(this.templateTarget, this.user);
		this.contentTarget.replaceChildren(...elements);
	}
	
	async submitProfile(event) {
		event.preventDefault();
		if( this.submittingForm ) {
			return;
		}
		const form = event.target;
		const input = this.startSubmittingForm(form);
		try {
			const user = await appWebService.requestPatch(`/user/${this.user.id}`, input);
			this.updateUser(user);
			// Show success
			this.report(this.profileFormReportsTarget, "success", "User profile saved!");
		} catch (exception) {
			console.error("Exception while saving user profile", exception);
			// Show error
			this.report(this.profileFormReportsTarget, "danger", exception.getMessage());
		}
		
		this.endSubmittingForm();
	}
	
	async submitSecurity(event) {
		event.preventDefault();
		if( this.submittingForm ) {
			return;
		}
		const form = event.target;
		const defaultEntity = {enabled: false, roles: []};
		const input = this.startSubmittingForm(form, defaultEntity);
		try {
			const user = await appWebService.requestPatch(`/user/${this.user.id}/security`, input);
			this.updateUser(user);
			// Show success
			this.report(this.securityFormReportsTarget, "success", "User security saved!");
		} catch (exception) {
			console.error("Exception while saving user security", exception);
			if( exception instanceof Exception ) {
				// Show error
				this.report(this.securityFormReportsTarget, "danger", exception.getMessage());
			}
		}
		
		this.endSubmittingForm();
	}
	
	async submitPassword(event) {
		event.preventDefault();
		if( this.submittingForm ) {
			return;
		}
		const form = event.target;
		const input = this.startSubmittingForm(form);
		try {
			const user = await appWebService.requestPatch(`/user/${this.user.id}/password`, input);
			this.updateUser(user);
			// Show success
			this.report(this.passwordFormReportsTarget, "success", "User password saved!");
		} catch (exception) {
			console.error("Exception while saving user password", exception);
			// Show error
			this.report(this.passwordFormReportsTarget, "danger", exception.getMessage());
		}
		
		this.endSubmittingForm();
		form.reset();
	}
	
}
