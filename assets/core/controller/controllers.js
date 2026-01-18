import {Controller} from "@hotwired/stimulus";
import {domService} from "../../services/dom.service.js";
import {Exception} from "../exceptions.js";
import * as bootstrap from "bootstrap";
import {sawService} from "../../services/saw.service.js";
import {NavigationEvent, navigationService} from "../../services/navigation.service.js";
import {SecurityEvent, securityService} from "../../services/security.service.js";
import {appWebService} from "../../services/app-web.service.js";

export class AbstractMainController extends Controller {
	static targets = ["content", "userLabel", "userMenu", "notificationList", "templateNotificationError"];
	
	routes = [];
	promises = [];
	
	async connect() {
		// document.documentElement.dataset.bsTheme = "dark";
		sawService.setDefaultVar("mainController", this.identifier);
		
		this.watchNavigation();
		this.watchUserAuthentication();
		this.watchDocumentClick();
		
		const user = await this.loadSession();
		if( user ) {
			this.triggerUserConnected(user);
		}
		await this.navigateFromCurrentUrl();
	}
	
	async disconnect() {
		navigationService.off(NavigationEvent.NAVIGATED);
		securityService.off(SecurityEvent.USER_AUTHENTICATED);
		securityService.off(SecurityEvent.USER_CONNECTED);
		securityService.off(SecurityEvent.USER_DISCONNECTED);
		this.unbindPromises();
	}
	
	refreshLayoutMenus() {
		// Do nothing
	}
	
	refreshMenu(menuElement) {
		const {route} = navigationService.currentRequestRoute;
		const currentUrl = navigationService.getCurrentUrl();
		// Find active menu item
		const menuItems = menuElement.querySelectorAll("a.nav-link");
		let activeItem = null;
		for (const menuItem of menuItems) {
			// Reset menu item
			menuItem.classList.remove("active");
			menuItem.ariaCurrent = null;
			// Compare URL
			const menuItemUrl = new URL(menuItem.href);
			if( menuItemUrl.origin === currentUrl.origin && menuItemUrl.pathname === route.menuPath ) {
				if( activeItem && menuItemUrl.search ) {
					// Handle menu item with query string (when there is an usage)
				} else {
					activeItem = menuItem;
				}
			}
		}
		// Set menu item active
		if( activeItem ) {
			activeItem.classList.add("active");
			activeItem.ariaCurrent = "page";
		}
	}
	
	refreshContents() {
		// Default behavior
		
		// Show user menu if authenticated
		if( !this.hasUserLabelTarget ) {
			// Loading page, user is loaded but page is not yet
			return;
		}
		const authenticatedUser = securityService.user;
		const isAuthenticated = !!authenticatedUser;
		
		// Update user menu
		this.userLabelTarget.innerText = isAuthenticated ? authenticatedUser.name : "";
		this.userMenuTarget.hidden = !isAuthenticated;
	}
	
	onNavigationChanged(path, parameters) {
		console.info("onNavigationRequest", path, parameters);
		return this.navigateFromPath(path, parameters);
	}
	
	onUserAuthenticated(user) {
		this.triggerUserConnected(user);
	}
	
	onUserConnected(user) {
		securityService.user = user;
		this.refreshContents();
	}
	
	/**
	 * @param {Object|null} user The disconnected user (or null if arriving not authenticated)
	 */
	onUserDisconnected(user) {
		// Do nothing
	}
	
	triggerUserConnected(user) {
		securityService.trigger(SecurityEvent.USER_CONNECTED, {user: user});
	}
	
	async loadSession() {
		// Load current user
		try {
			return await appWebService.getAuthenticatedUser();
		} catch (exception) {
			console.error("Error getting authenticated user", exception);
			return null;
		}
	}
	
	async logout() {
		await appWebService.disconnectUser();
		securityService.revokeUser();
	}
	
	watchNavigation() {
		navigationService.on(NavigationEvent.NAVIGATED)
			.then(event => this.onNavigationChanged(event.data.path, event.data.parameters));
	}
	
	watchUserAuthentication() {
		securityService.on(SecurityEvent.USER_AUTHENTICATED)
			.then(event => this.onUserAuthenticated(event.data.user));
		securityService.on(SecurityEvent.USER_CONNECTED)
			.then(event => this.onUserConnected(event.data.user));
		securityService.on(SecurityEvent.USER_DISCONNECTED)
			.then(event => this.onUserDisconnected(event.data.user));
	}
	
	watchDocumentClick() {
		// Must capture children event too because document capture event once and target is the clicked element, not the clicked link
		this.promises.push(domService.on("a, a *", "click")
			.then(event => {
				const target = domService.queryMeOrParent(event.target, "a");
				const targetUrl = target.href && new URL(target.href);
				const currentUrl = navigationService.getCurrentUrl();
				
				if( targetUrl.origin === currentUrl.origin ) {
					try {
						const {route, parameters} = navigationService.getRequestRoute(this.routes, targetUrl);
						event.preventDefault();
						if( (targetUrl.pathname + targetUrl.search) !== (currentUrl.pathname + currentUrl.search) ) {
							// Navigate if target is not the current page
							navigationService.navigate(route, parameters);
						}
					} catch {
						// Page was not found, this is out of perimeter, we should go to this page as usual
					}
				} // Else let it go to another website
			}));
	}
	
	unbindPromises() {
		this.promises.forEach(promise => domService.off(promise));
	}
	
	/**
	 * @param templatePath
	 * @param templateParams
	 * @return Promise<void>
	 */
	async setContentsToTemplate(templatePath, templateParams = {}) {
		await sawService.assignTemplate(this.contentTarget, templatePath, templateParams);
		this.refreshContents();
	}
	
	async navigateFromCurrentUrl() {
		console.log("history.state", Object.assign({}, history.state));
		let stateRoute = navigationService.getCurrentStateRoute();
		if( !stateRoute ) {
			const {route, parameters} = navigationService.getRequestRoute(this.routes);
			stateRoute = {path: route.path, parameters};
			
			navigationService.setCurrentStateRoute(stateRoute);
		} else {
			console.info("Restored state route", stateRoute);
		}
		
		await this.navigateFromPath(stateRoute.path, stateRoute.parameters);
	}
	
	async navigateFromPath(path, parameters) {
		const route = navigationService.findPathRoute(this.routes, path);
		if( !route ) {
			// Route not found, throw error, it should be handled before
			throw new Error(`No route found for route path ${path} (${typeof route})`);
		}
		navigationService.currentRequestRoute = {route, parameters};
		
		await route.applyTo(this, parameters);
		this.refreshLayoutMenus();
	}
	
	showError(event) {
		this.pushNotificationError(event.detail.error);
	}
	
	pushNotificationError(error) {
		const output = {title: "System", message: error instanceof Exception ? error.getMessage() : error};
		const notificationElement = domService.renderTemplate(this.templateNotificationErrorTarget, output)[0];
		this.pushNotification(notificationElement, {autohide: false});
	}
	
	/**
	 * @param {Element} notificationElement
	 * @param options
	 */
	pushNotification(notificationElement, options) {
		this.notificationListTarget.append(notificationElement);
		const toast = new bootstrap.Toast(notificationElement, options);
		toast.show();
	}
	
}

/**
 * @member {Element[]} formTargets
 */
export class AbstractPageController extends Controller {
	submittingForm = null;
	
	/**
	 * Set form to submitting state, every field is disabled
	 *
	 * @param form
	 * @param defaults
	 * @return {{}}
	 */
	startSubmittingForm(form, defaults = {}) {
		this.submittingForm = form;
		const input = Object.assign(defaults, domService.getFormObject(form));
		this.disableAllForms();
		
		return input;
	}
	
	/**
	 * Remove form from submitting state, every field is enabled again
	 */
	endSubmittingForm() {
		this.submittingForm = null;
		this.enableAllForms();
	}
	
	enableAllForms() {
		this.formTargets.forEach(form => domService.enableForm(form));
	}
	
	disableAllForms() {
		this.formTargets.forEach(form => domService.disableForm(form));
	}
	
	async report(container, type, message) {
		const [reportElement] = domService.renderTemplate(document.getElementById("TemplateAlert"), {type, message});
		container.replaceChildren(reportElement);
		container.hidden = false;
		await domService.fadeOut(reportElement, 10000, true);
		if( !container.hasChildNodes() ) {
			container.hidden = true;
		}
	}
	
	dispatchEvent(event, detail = null, options = {}) {
		domService.dispatchEvent(this.element, event, detail, options);
	}
	
	reportException(exception) {
		this.dispatchEvent("app.error", {error: exception});
	}
	
}
