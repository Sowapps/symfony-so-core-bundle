import {securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";
import {SawElementLoader} from "../../core/render/ElementLoader.js";
import {ApiValidationError, appWebService} from "../../services/app-web.service.js";
import {domService} from "../../services/dom.service.js";
import {Is} from "../../helpers/is.helper.js";
import {AbstractPageController} from "../../core/controller/controllers.js";
import DialogController from "../component/dialog_controller.js";

export default class extends AbstractPageController {
	static targets = [
		'languageListBody', 'languageListTemplate', 'languageListTable', 'languageItemTemplate',
		'dialogCreate', 'dialogUpdate',
		"button", // Any button that is disabled while operating with the server
	];
	static values = {
		loadListTitle: String,
		createSuccess: String,
		createTitle: String,
		updateSuccess: String,
		updateTitle: String
	};
	/** @type {Object[]} */
	languages;
	
	connect() {
		// TODO Check permissions
		if( !securityService.isAuthenticated() ) {
			navigationService.navigate("/");
			return;
		}
		console.log("Connect language admin with", this.updateSuccessValue);
		
		this.load();
	}
	
	/**
	 * Load or reload everything that relies on contents
	 */
	async load() {
		this.startOperating();// Could be already started
		await this.loadLanguageList();
		this.endOperating();
	}
	
	async loadLanguageList() {
		const languageList = await SawElementLoader
			.connect(this.languageListBodyTarget)
			.setNotificationTitle(this.loadListTitleValue)
			.watch(appWebService.requestGet(`/language`));
		
		this.languageListBodyTarget.replaceChildren(...domService.renderTemplate(this.languageListTemplateTarget, {hasLanguages: !!languageList.length}));
		// Item list
		languageList.forEach(language => {
			this.languageListTableTarget.append(...domService.renderTemplate(this.languageItemTemplateTarget, language));
		});
		// Save language list in controller
		const languages = {};
		languageList.forEach(language => languages[language.id] = language);
		this.languages = languages;
	}
	
	getLanguage(id) {
		return this.languages[parseInt(id)];
	}
	
	getButtons() {
		// Button targets excluding those in an unloaded template
		return this.buttonTargets.filter($element => !domService.queryMeOrParent($element, '.template-unloaded'));
	}
	
	getEventRowData(event) {
		return event.target.closest('tr').dataset.item;
	}
	
	async createLanguage(event) {
		const input = event.detail;
		if( Is.empty(input) ) {
			throw new Error("createLanguage triggered with no detail in event");
		}
		console.log("createLanguage", input, event);
		
		this.startOperating();
		const formElement = this.dialogCreateTarget.querySelector("form");
		/** @type {FormController} */
		const formController = this.getFormController(formElement);
		// Reset current feedbacks
		formController.resetValidation();
		
		try {
			const language = await appWebService.requestPost(`/language`, input);
			
			domService.dispatchEvent(this.dialogCreateTarget, DialogController.EVENT_DIALOG_CLOSE);
			this.reportSuccess(domService.renderString(this.createSuccessValue, language), this.createTitleValue);
			await this.load();
		} catch (error) {
			// Form popin stays open
			this.reportValidationException(error, this.createTitleValue, formController);
			this.endOperating();
		}
	}
	
	requestCreateLanguage() {
		domService.dispatchEvent(this.dialogCreateTarget, DialogController.EVENT_DIALOG_OPEN, {
			name: null,
			locale: null,
			primaryCode: null,
			regionCode: null,
		});
	}
	
	async updateLanguage(event) {
		const input = event.detail;
		if( Is.empty(input) ) {
			throw new Error("updateLanguage triggered with no detail in event");
		}
		console.debug("updateLanguage", input, event);
		
		this.startOperating();
		const formElement = this.dialogUpdateTarget.querySelector("form");
		/** @type {FormController} */
		const formController = this.getFormController(formElement);
		// Reset current feedbacks
		formController.resetValidation();
		
		try {
			const language = await appWebService.requestPatch(`/language/${input.id}`, input);
			
			domService.dispatchEvent(this.dialogUpdateTarget, DialogController.EVENT_DIALOG_CLOSE);
			this.reportSuccess(domService.renderString(this.updateSuccessValue, language), this.updateTitleValue);
			await this.load();
		} catch (error) {
			// Form popin stays open
			this.reportValidationException(error, this.updateTitleValue, formController);
			this.endOperating();
		}
	}
	
	async enableLanguage(event) {
		return this.updateEnabledLanguage(event, true);
	}
	
	async disableLanguage(event) {
		return this.updateEnabledLanguage(event, false);
	}
	
	async updateEnabledLanguage(event, enabled) {
		const id = event.params.id;
		if( Is.empty(id) ) {
			throw new Error("enableLanguage triggered with no ID param in event");
		}
		console.debug("updateEnableLanguage", id, enabled);
		
		this.startOperating();
		
		try {
			const language = await appWebService.requestPatch(`/language/${id}/enabled`, {enabled: enabled});
			this.reportSuccess(domService.renderString(this.updateSuccessValue, language), this.updateTitleValue);
			await this.load();
		} catch (error) {
			this.reportException(error, this.updateTitleValue);
			this.endOperating();
		}
	}
	
	requestUpdateLanguage(event) {
		const data = this.getLanguage(event.params.id);
		console.debug("Open update language dialog with", event.params, data);
		domService.dispatchEvent(this.dialogUpdateTarget, DialogController.EVENT_DIALOG_OPEN, data);
	}
	
}
