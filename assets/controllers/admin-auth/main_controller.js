import {Controller} from '@hotwired/stimulus';
import {TemplateRoute} from "../../services/navigation.service.js";
import {AbstractMainController} from "../../core/controller/controllers.js";

export default class extends AbstractMainController {
// export default class extends Controller {
	
	routes = [
		// Only authentication routes
		new TemplateRoute("/admin-auth/login", "admin-auth/login.html"),
	];
	
	
	// connect() {
	// 	// TODO Remove test
	// 	console.log('SoCore AdminAuth Main Controller connected');
	// }
	
}
