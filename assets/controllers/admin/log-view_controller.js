import {Controller} from "@hotwired/stimulus";
import {sawService} from "../../services/saw.service.js";
import {domService} from "../../services/dom.service.js";
import {appWebService} from "../../services/app-web.service.js";
import {securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";

export default class extends Controller {
	static targets = ["detailsContent", "detailsTemplate"];
	
	connect() {
		// TODO Check permissions
		if( !securityService.isAuthenticated() ) {
			navigationService.navigate("/");
			return;
		}
		
		this.load();
	}
	
	load() {
		this.loadDetails();
	}
	
	async loadDetails() {
		sawService.setLoading(this.detailsContentTarget);
		
		const logDetails = await appWebService.requestGet(`/log/default`);
		const elements = domService.renderTemplate(this.detailsTemplateTarget, logDetails);
		this.detailsContentTarget.replaceChildren(...elements);
	}
	
}
