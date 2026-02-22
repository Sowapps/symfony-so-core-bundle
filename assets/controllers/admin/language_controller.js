import {securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";
import {SawElementLoader} from "../../core/render/ElementLoader.js";
import {appWebService} from "../../services/app-web.service.js";
import {domService} from "../../services/dom.service.js";
import {Is} from "../../helpers/is.helper.js";
import {AbstractPageController} from "../../core/controller/controllers.js";
import DialogController from "../component/dialog_controller.js";

export default class extends AbstractPageController {
	static targets = [
		'languageListBody', 'languageListTemplate', 'languageListTable', 'languageItemTemplate',
		'dialogCreate', 'dialogUpdate', 'dialogExport', 'dialogImport',
		"button", // Any button that is disabled while operating with the server
	];
	static values = {
		loadListTitle: String,
		createSuccess: String,
		createTitle: String,
		updateSuccess: String,
		updateTitle: String,
		exportTitle: String,
		exportSuccess: String,
		importTitle: String,
		importSuccess: String,
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
		} catch (exception) {
			// Form popin stays open
			this.reportValidationException(exception, this.createTitleValue, formController);
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
		} catch (exception) {
			// Form popin stays open
			this.reportValidationException(exception, this.updateTitleValue, formController);
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
		} catch (exception) {
			this.reportException(exception, this.updateTitleValue);
			this.endOperating();
		}
	}
	
	requestUpdateLanguage(event) {
		const data = this.getLanguage(event.params.id);
		console.debug("Open update language dialog with", event.params, data);
		domService.dispatchEvent(this.dialogUpdateTarget, DialogController.EVENT_DIALOG_OPEN, data);
	}
	
	requestExport() {
		console.debug("Request export");
		domService.dispatchEvent(this.dialogExportTarget, DialogController.EVENT_DIALOG_OPEN);
	}
	
	async exportList() {
		this.startOperating();
		try {
			// Request download, the service automates the process
		    await appWebService.downloadFile(`/languages.csv`);
			// Success
			domService.dispatchEvent(this.dialogExportTarget, DialogController.EVENT_DIALOG_CLOSE);
			this.reportSuccess(this.exportSuccessValue, this.exportTitleValue);
		} catch (exception) {
			this.reportException(exception, this.exportTitleValue);
		} finally {
			this.endOperating();
		}
	}
	
	requestImport() {
		console.debug("Request Import");
		domService.dispatchEvent(this.dialogImportTarget, DialogController.EVENT_DIALOG_OPEN);
	}
	
	async importList(event) {
		const input = event.detail;
		this.startOperating();
		try {
			// Upload the file
			const $form = this.dialogImportTarget.querySelector("form");
			const form = domService.getFormData($form);
			
			// Upload the file - Copy the form file to a new FormData
			const formFile = form.get("file");
			const uploadForm = new FormData();
			uploadForm.append("file", formFile, formFile.name);
			uploadForm.append("purpose", 'import_language');
			uploadForm.append("expireDate", '1 day');// Expires in one day
			
			// Upload the file - Process upload to server
			const file = await appWebService.uploadFile(`/file`, uploadForm, {format: 'public'});
			
			// Import the file
			delete input.file;
			input.fileId = file.id;
			console.debug("Import", input);
			const summary = await appWebService.requestPost(`/languages.csv`, input);
			summary.errorCount = summary.errors.length;
			
			// Success
			domService.dispatchEvent(this.dialogImportTarget, DialogController.EVENT_DIALOG_CLOSE);
			this.reportSuccess(domService.renderString(this.importSuccessValue, summary), this.importTitleValue);
			await this.load();
		} catch (exception) {
			// Validation errors are technical here as the user can no act on most of them
			this.reportException(exception, this.importTitleValue);
			this.endOperating();
		}
	}
	
}
