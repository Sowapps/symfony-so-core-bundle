import {domService} from "../../services/dom.service.js";
import {appWebService} from "../../services/app-web.service.js";
import {securityService} from "../../services/security.service.js";
import {navigationService} from "../../services/navigation.service.js";
import {AbstractPageController} from "../../core/controller/controllers.js";
import {SawElementLoader} from "../../core/render/ElementLoader.js";

/**
 * Admin log page to see and manage logs
 */
export default class extends AbstractPageController {
	static targets = ["detailsContent", "detailsTemplate", "entryListBody", "entryBodyTemplate", "entryListContent", "entryEmptyListTemplate", "entryItemTemplate", "occurrenceTemplate",
		"viewListContent", "viewEmptyListTemplate", "viewItemTemplate",
		"button", // Any button that is disabled while operating with the server
		"input" // Any input synchronized with a value of this controller (param value is required to work)
	];
	static values = {
		loadDetailsTitle: String,
		loadEntriesTitle: String,
		loadViewTitle: String,
		removeNonErrorReportsSuccess: String,
		removeAllReportsSuccess: String,
		removeReportSuccess: String,
		viewQuantity: {type: Number, default: 50}
	};
	
	statusClasses = {rare: 'text-primary', occasionally: 'text-warning', frequently: 'text-danger', solved: 'text-success'};
	logEntries = null;
	
	connect() {
		// TODO Check permissions
		if( !securityService.isAuthenticated() ) {
			navigationService.navigate("/");
			return;
		}
		
		this.load();
	}
	
	// TODO Move as generic
	getElementParam(element, name) {
		return element.getAttribute(`data-${this.identifier}-${name}-param`); // string | null
	}
	
	// TODO Move as generic
	inputTargetConnected(input) {
		const valueName = this.getElementParam(input, 'value');
		console.log('Connected input ', input, valueName);
		// Register input in callback of value change
		if( this.constructor.values[valueName] === undefined ) {
			throw new Error("Value " + valueName + " is not defined in values of controller");
		}
		this.valueInputs ??= [];
		this.valueInputs[valueName] ??= [];
		this.valueInputs[valueName].push(input);
		console.log("Input connect to value ", valueName, this.valueInputs[valueName]);
		if( this.valueInputs[valueName].length === 1 ) {
			// On the first input, we register the callback
			this[valueName + "ValueChanged"] = (value, previousValue) => {
				const inputs = this.valueInputs[valueName];
				console.log("Value changed from ", previousValue, " to ", value, "for inputs", inputs);
				inputs.forEach(input => this.setInputValue(input, value));
			};
		}
		this.setInputValue(input, this[valueName + "Value"]);
	}
	
	// TODO Move as generic
	setInputValue(input, value) {
		input.value = value;
		// TODO Trigger change event if input was having a different value ?
	}
	
	// TODO Move as generic
	updateValue(event) {
		const valueName = event.params.value;
		console.log("updateValue of " + valueName, 'or from attr : ', this.getElementParam(event.target, 'value'));
		this[valueName + 'Value'] = event.target.value;
	}
	
	/**
	 * Load or reload all data
	 */
	load() {
		this.loadDetails();
		this.loadLogContents();
	}
	
	/**
	 * Load or reload everything that relies on log contents
	 */
	loadLogContents() {
		this.startOperating();// Could be already started
		this.loadEntries();
		this.loadView();
		this.endOperating();
	}
	
	async loadDetails() {
		const logDetails = await SawElementLoader
			.connect(this.detailsContentTarget)
			.setNotificationTitle(this.loadDetailsTitleValue)
			.watch(appWebService.requestGet(`/log/default`));
		const elements = domService.renderTemplate(this.detailsTemplateTarget, logDetails);
		this.detailsContentTarget.replaceChildren(...elements);
	}
	
	async loadEntries() {
		const logSummary = await SawElementLoader
			.connect(this.entryListContentTarget)
			.setNotificationTitle(this.loadEntriesTitleValue)
			.watch(appWebService.requestGet(`/log/default/entry`));
		const errors = Object.values(logSummary.items);
		
		// Legend
		this.entryListBodyTarget.replaceChildren(...domService.renderTemplate(this.entryBodyTemplateTarget, {errorCount: errors.length, otherCount: logSummary.otherCount}));
		
		// Item list
		this.entryListContentTarget.innerHTML = "";// Empty
		errors.forEach(item => {
			item.panelId = "LogItem" + item.groupKey;
			item.statusClass = this.getStatusClass(item);
			const element = domService.renderTemplate(this.entryItemTemplateTarget, item)[0];
			const occurrenceList = element.querySelector('.log-occurrence-list');
			Object.values(item.occurrences).forEach(([date, line]) => {
				const occurrenceElement = domService.renderTemplate(this.occurrenceTemplateTarget, {date, line})[0];
				occurrenceList.append(occurrenceElement);
			});
			this.entryListContentTarget.append(element);
		});
		if( !this.entryListContentTarget.innerHTML ) {
			const elements = domService.renderTemplate(this.entryEmptyListTemplateTarget);
			this.entryListContentTarget.append(...elements);
		}
		this.logEntries = logSummary.items;
	}
	
	getStatusClass(log) {
		return this.statusClasses[log.status];
	}
	
	async loadView() {
		const items = await SawElementLoader
			.connect(this.viewListContentTarget)
			.setNotificationTitle(this.loadViewTitleValue)
			.watch(appWebService.requestGet(`/log/default/raw?max=${this.viewQuantityValue}`));
		
		this.viewListContentTarget.innerHTML = "";// Empty
		Object.entries(items).forEach(([line, log]) => {
			const elements = domService.renderTemplate(this.viewItemTemplateTarget, {line, log});
			this.viewListContentTarget.append(...elements);
		});
		if( !this.viewListContentTarget.innerHTML ) {
			const elements = domService.renderTemplate(this.viewEmptyListTemplateTarget);
			this.viewListContentTarget.append(...elements);
		}
	}
	
	/**
	 * Start any server operation, so any button or form is disabled
	 * TODO Move as generic
	 */
	startOperating() {
		this.operating = true;
		this.#renderOperating();
	}
	
	/**
	 * End any server operation, so any button or form can be enabled again
	 * TODO Move as generic
	 */
	endOperating() {
		this.operating = false;
		this.#renderOperating();
	}
	
	// TODO Move as generic
	#renderOperating() {
		this.buttonTargets.forEach(button => {
			this.#renderElementAccordingToOperatingMode(button);
		});
	}
	
	// TODO Move as generic
	#renderElementAccordingToOperatingMode(button) {
		button.disabled = this.operating;
	}
	
	buttonTargetConnected(button) {
		this.#renderElementAccordingToOperatingMode(button);
	}
	
	itemTargetDisconnected(button) {
		this.#renderElementAccordingToOperatingMode(button);
	}
	
	downloadLogFile() {
		window.location.href = "/api/log/default/file";
	}
	
	async removeNonError() {
		this.startOperating();
		await appWebService.requestDelete(`/log/default/raw/non-error`);
		
		this.reportSuccess(this.removeNonErrorReportsSuccessValue);
		this.loadLogContents();
	}
	
	async removeAll() {
		this.startOperating();
		await appWebService.requestDelete(`/log/default/raw/all`);
		
		this.reportSuccess(this.removeAllReportsSuccessValue);
		this.loadLogContents();
	}
	
	async removeEntry(event) {
		const groupKey = event.params.group;
		const logEntry = this.logEntries[groupKey];
		const lines = logEntry.occurrences.map(occurrence => occurrence[1]);
		this.startOperating();
		await appWebService.requestDelete(`/log/default/raw`, lines);
		
		this.reportSuccess(this.removeReportSuccessValue);
		this.loadLogContents();
	}
}
