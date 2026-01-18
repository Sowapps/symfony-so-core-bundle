import {domService} from "../../services/dom.service.js";
import {ApiUserServerError, appWebService} from "../../services/app-web.service.js";
import {Is} from "../../helpers/is.helper.js";
import {sawService} from "../../services/saw.service.js";
import {AbstractPageController} from "./controllers.js";

export class AbstractAdminItemEditableListController extends AbstractPageController {
	static defaults = {
		pageLimit: 10,
	};
	static targets = ["form", "filterForm", "listNavigationTemplate", "listNavigationItemTemplate",
		"list", "listPagination", "listSummary", "pageLimitInput", "itemTemplate", "panelTemplate", "panelContainer", "formReports"];
	static values = {
		page: {type: Number, default: 1}, // Changes auto-load content
		pageLimit: Number, // Changes auto-saved in local storage
		activeId: Number,
	};
	
	name;
	settingPageLimitKey;
	apiRessourcePath;
	selectedFilter;
	text = {
		updateSuccess: null,
		notFound: null,
	};
	itemKey = null;
	pageLimitMax = 50;
	
	connect() {
		if( !this.hasPageLimitValue ) {
			this.pageLimitValue = this.getSettingPageLimit();
		}
		
		this.load();
	}
	
	filterByKey(event) {
		// id is null if no item is selected (previous is unselected)
		this.selectedFilter = event.detail.id ? {key: event.detail.key, value: event.detail.id} : null;
		
		this.filterPage();
	}
	
	filterPage() {
		// Save filters
		this.pageLimitValue = this.pageLimitInputTarget.value;
		// Reload
		this.pageValue = 1;
		this.load();
	}
	
	navigateToListPage(event) {
		this.pageValue = event.params.page;
		this.load();
	}
	
	selectItem(event) {
		const rowElement = domService.queryMeOrParent(event.target, "tr");
		const selectedId = parseInt(rowElement.dataset.id);
		// Select new item if different
		// Unselect item if same item
		this.activeIdValue = this.activeIdValue !== selectedId ? selectedId : undefined;
	}
	
	async submitItemUpdate(event) {
		event.preventDefault();
		if( this.submittingForm || !this.hasActiveIdValue ) {
			return;
		}
		const id = this.activeIdValue;
		const form = event.target;
		const defaultEntity = {published: false};
		const input = this.startSubmittingForm(form, defaultEntity);
		try {
			const entity = await appWebService.requestPatch(`${this.apiRessourcePath}/${id}`, input);
			this.updatePanel(entity);
			// Show success
			this.report(this.formReportsTarget, "success", this.text.updateSuccess);
		} catch (exception) {
			console.error(`Exception while saving ${this.name}`, exception);
			// Show error
			this.report(this.formReportsTarget, "danger", exception.getMessage());
		}
		
		this.endSubmittingForm();
	}
	
	async load() {
		await this.loadList();
		await this.renderPanel();
	}
	
	getFilterParameters(filters) {
		const parameters = {};
		if( filters.terms ) {
			if( Is.stringInteger(filters.terms) ) {
				parameters.termId = filters.terms;
			} else {
				parameters.terms = filters.terms.replace(",", "").split(" ");
			}
		}
		if( filters.published ) {
			parameters.published = filters.published === "true";
		}
		if( filters.genuine ) {
			parameters.genuine = filters.genuine === "true";
		}
		
		return parameters;
	}
	
	getFilterSorting() {
		return "-id";
	}
	
	async loadList() {
		sawService.setLoading(this.listTarget);
		const parameters = {
			page: this.pageValue,
			pageLimit: this.pageLimitValue,
			sort: this.getFilterSorting(),
		};
		
		const filters = domService.getFormObject(this.filterFormTarget);
		Object.assign(parameters, this.getFilterParameters(filters));
		if( this.selectedFilter ) {
			parameters[this.selectedFilter.key] = this.selectedFilter.value;
		}
		// parameters.format = ['admin'];// We don't need so much data (as metadata)
		
		const {pagination, list: entityList} = await appWebService.getPaginatedList(this.apiRessourcePath, parameters);
		this.listPagination = pagination;
		
		// Render item list
		this.listTarget.innerHTML = "";
		const itemTemplate = this.itemTemplateTarget;
		entityList.forEach(entity => {
			const itemElement = domService.renderTemplate(itemTemplate, entity)[0];
			this.listTarget.appendChild(itemElement);
		});
		
		this.#renderPageLimitInput(this.pageLimitInputTarget, this.listPagination);
		this.#renderPaginationSummary(this.listSummaryTarget, this.listPagination);
		this.#renderPagination(this.listPaginationTarget, this.listPagination);
	}
	
	async renderPanel() {
		if( !this.hasActiveIdValue ) {
			// Unselected - No item selected
			const activeInput = this.listTarget.querySelector(`:checked`);
			if( activeInput ) {
				activeInput.checked = false;
			}
			
			this.emptyPanel();
			return;
		}
		// Reload selected entity column even if the same to allow user to refresh data
		const id = this.activeIdValue;
		const rowElement = this.listTarget.querySelector(`[data-id="${id}"]`);
		if( !rowElement ) {
			// The active entity is now more in list
			this.emptyPanel();
			return;
		}
		
		// Inactive previous one in list
		this.listTarget
			.querySelectorAll("tr.table-active")
			.forEach(itemRow => {
				itemRow.classList.remove("table-active");
			});
		
		// Set active in list
		rowElement.classList.add("table-active");
		rowElement.querySelector(".input-active").checked = true;
		
		// Set loading
		rowElement.classList.add("state-loading");
		
		// Get entity from server
		let entity = null;
		try {
			const parameters = {
				format: [appWebService.FORMAT.ADMIN, appWebService.FORMAT.RELATION],
			};
			entity = await appWebService.requestGet(`${this.apiRessourcePath}/${id}`, parameters);
			console.log("Got entity ", entity, "with parameters", parameters);
		} catch (exception) {
			let error = exception;
			if( exception instanceof ApiUserServerError ) {
				if( exception.statusCode === 404 ) {
					error = domService.renderString(this.text.notFound, {id});
				}
			}
			this.reportException(error);
		}
		rowElement.classList.remove("state-loading");
		if( !this.hasActiveIdValue || this.activeIdValue !== id ) {
			// Selected item changed
			return;
		}
		if( !entity ) {
			// Error getting item, error is already processed
			return;
		}
		
		this.updatePanel(entity);
	}
	
	async updatePanel(entity) {
		const elements = domService.renderTemplate(this.panelTemplateTarget, entity);
		this.panelContainerTarget.replaceChildren(...elements);
	}
	
	emptyPanel() {
		this.panelContainerTarget.innerHTML = "";
	}
	
	activeIdValueChanged(value) {
		this.renderPanel();
		if( this.itemKey ) {
			this.dispatchEvent("item.selected", {key: this.itemKey, id: value || null});
		}
	}
	
	pageLimitValueChanged(value, previousValue) {
		if( previousValue === undefined ) {
			// Ignore stimulus defaulting the value to 0
			return;
		}
		this.setSettingPageLimit(this.pageLimitValue);
	}
	
	setSettingPageLimit(value = null) {
		if( value === null ) {
			localStorage.removeItem(this.settingPageLimitKey);
		} else {
			localStorage.setItem(this.settingPageLimitKey, value);
		}
		
		return this.getSettingPageLimit();
	}
	
	getSettingPageLimit() {
		let value = localStorage.getItem(this.settingPageLimitKey);
		value = value !== null ? value * 1 : this.constructor.defaults.pageLimit;
		if( value < 1 ) {
			// 0 (All) is not more allowed
			value = this.constructor.defaults.pageLimit;
		}
		
		return value;
	}
	
	/**
	 * @param {HTMLSelectElement} element
	 * @param {Pagination} pagination
	 */
	#renderPageLimitInput(element, pagination) {
		const bases = [10, 20, 50];
		let i = 0;
		const list = [];
		do {
			// Calculate value
			const base = bases[i % bases.length];
			const pow = Math.floor(i / bases.length);
			const value = base * Math.pow(10, pow);
			// Verify value is in limits or stops
			if( value >= pagination.total || value > this.pageLimitMax ) {
				break;
			}
			// Add value
			list.push(value);
			i++;
		} while (true);
		let selected = this.pageLimitValue;
		const defaultPageLimit = this.constructor.defaults.pageLimit;
		element.innerHTML = "";
		for (const value of list) {
			const defaultSelected = value === defaultPageLimit;
			// Select only if there is results, else
			const option = new Option(value, value, defaultSelected, value === selected);
			element.add(option);
		}
		const maxValue = list.at(-1) || 0;// If there is no entry, there is no last item
		if( selected > maxValue ) {
			// Add select if this value is unavailable
			const option = new Option(selected, selected, selected === defaultPageLimit, true);
			option.disabled = true;
			element.add(option);
		}
		// Add "All" - No more allowed
		// element.add(new Option("All", "0", false, 0 === selected));
	}
	
	/**
	 * @param {HTMLSelectElement} element
	 * @param {Pagination} pagination
	 */
	#renderPaginationSummary(element, pagination) {
		element.innerText = domService.renderString(element.dataset.text, pagination);
	}
	
	/**
	 * @param {Element} element
	 * @param {Pagination} pagination
	 */
	#renderPagination(element, pagination) {
		const navigation = pagination.getNavigation();
		let paginationPages = "";
		for (const pageItem of navigation.pages) {
			paginationPages += domService.renderTemplateAsString(this.listNavigationItemTemplateTarget, pageItem);
		}
		
		const paginationElement = domService.renderTemplate(this.listNavigationTemplateTarget, {paginationPages})[0];
		["first", "previous", "next", "last"]
			.forEach(key => {
				const item = navigation[key];
				[...paginationElement.getElementsByClassName("navigate-" + key)].forEach(navElement => {
					navElement.classList.add(item.state);
					navElement.firstChild.setAttribute(`data-${this.identifier}-page-param`, item.page);
				});
			});
		element.replaceChildren(paginationElement);
	}
	
}
