import {Controller} from "@hotwired/stimulus";
import {sawService} from "../../services/saw.service.js";
import {domService} from "../../services/dom.service.js";
import {appWebService} from "../../services/app-web.service.js";
import {securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";

export default class extends Controller {
	static targets = ["list", "templateItem"];
	
	connect() {
		// TODO Check permissions
		if( !securityService.isAuthenticated() ) {
			navigationService.navigate("/");
			return;
		}
		
		this.load();
	}
	
	async load() {
		sawService.setLoading(this.listTarget);
		
		const users = await appWebService.getList("/user?format=admin");
		this.listTarget.innerHTML = "";
		const itemTemplate = this.templateItemTarget;
		users.forEach(user => {
			const itemElement = domService.renderTemplate(itemTemplate, user)[0];
			this.listTarget.appendChild(itemElement);
		});
	}
	
}
