import { Controller } from '@hotwired/stimulus';
import { DataTable } from "simple-datatables";

export default class extends Controller {
	static targets = ['table'];
	static values = {labels: Object};
	
	// static values = {url: String, order: Array};
	
	initialize() {
		this.$table = this.hasTableTarget ? this.tableTarget : this.element;
		this.connectTable();
	}
	
	connectTable() {
		const options = this.generateOptions();
		this.datatable = new DataTable(this.$table, options);
	}
	
	reload() {
		this.datatable.ajax.reload();
	}
	
	generateOptions() {
		return {
			labels: this.generateOptionLabels(),
		};
	}
	
	generateOptionLabels() {
		return this.hasLabelsValue ? this.labelsValue : null;
		// return {
		// 	placeholder: t('page.admin_user_list.dataTable.placeholder'), // The search input placeholder
		// 	perPage: "{select} entrées par page", // per-page dropdown label
		// 	noRows: "Aucune entrée trouvée", // Message shown when there are no records to show
		// 	noResults: "Aucun résultat pour votre recherche", // Message shown when there are no search results
		// 	info: "Page {start} sur {end} pour {rows} entrées" // Info message below table
		// 	// placeholder: "Search...", // The search input placeholder
		// 	// perPage: "{select} entries per page", // per-page dropdown label
		// 	// noRows: "No entries found", // Message shown when there are no records to show
		// 	// noResults: "No results match your search query", // Message shown when there are no search results
		// 	// info: "Showing {start} to {end} of {rows} entries" //
		// };
	}
}
