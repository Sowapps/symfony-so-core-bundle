import {AbstractMainController} from "../../core/controller/controllers.js";
import {securityService} from "../../services/security.service.js";
import {CallbackRoute, navigationService, TemplateRoute} from "../../services/navigation.service.js";

/**
 * @property {Element} adminMenuTarget
 * @property {Boolean} hasAdminMenuTarget
 *
 * TODO Document Stimulus Controller
 */
export default class extends AbstractMainController {
	static targets = super.targets.concat(["adminMenu"]);
	
	routes = [
		new CallbackRoute("/admin", () => this.navigateToIndex()),
		new TemplateRoute("/admin/dashboard", "admin/dashboard.html"),
		new TemplateRoute("/admin/user", "admin/user-list.html"),
		new TemplateRoute("/admin/user/{id}", "admin/user-edit.html").setMenuPath("/admin/user"),
	];
	
	onUserDisconnected(user) {
		// Unable to redirect to admin login page without the key
		navigationService.redirectTo("/");
	}
	
	refreshLayoutMenus() {
		// console.log('this.adminMenuTarget : ' + (this.hasAdminMenuTarget ? 'OK' :  'MISSING'));
		// console.dir(this.element);
		this.refreshMenu(this.adminMenuTarget);
	}
	
	navigateToIndex() {
		// Redirect to admin dashboard if authenticated
		// Else redirect to public home page, admin login page is only accessible with access key, we can not redirect to it
		if( securityService.isAuthenticated() ) {
			return navigationService.navigate("/admin/dashboard");
		}
		navigationService.redirectTo("/");
	}
	
}
