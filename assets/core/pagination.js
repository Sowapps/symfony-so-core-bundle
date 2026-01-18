export class Pagination {
	
	constructor(currentPage, currentCount, pageLimit, total) {
		this.currentPage = currentPage;
		this.currentCount = currentCount;
		this.pageLimit = pageLimit;
		this.total = total;
		this.pageMin = 1;
		this.pageMax = Math.ceil(total / pageLimit);
		this.currentFirst = currentCount ? (currentPage - 1) * pageLimit + 1 : 0;
		this.currentLast = currentCount ? this.currentFirst + this.currentCount - 1 : 0;
	}
	
	/**
	 * Get pagination for numeric pages only
	 */
	getNavigation() {
		const deltaSmall = this.#calculateDelta1(this.pageMax);
		const deltaLarge = this.#calculateDelta2(this.pageMax);
		
		const pages = [...new Set([
			this.restrictPageToBounds(this.currentPage - deltaLarge),
			this.restrictPageToBounds(this.currentPage - deltaSmall),
			this.currentPage,
			this.restrictPageToBounds(this.currentPage + deltaSmall),
			this.restrictPageToBounds(this.currentPage + deltaLarge),
		])];
		
		return {
			first: this.getPageItem(this.pageMin, true),
			previous: this.getPageItem(this.currentPage - 1, true),
			pages: pages.map(page => this.getPageItem(page)),
			next: this.getPageItem(this.currentPage + 1, true),
			last: this.getPageItem(this.pageMax, true),
		};
	}
	
	restrictPageToBounds(page) {
		return Math.max(Math.min(page, this.pageMax), this.pageMin);
	}
	
	/**
	 * @param {number} page
	 * @param {boolean} notActive Must not be the active
	 * @return {{page, state: string}}
	 */
	getPageItem(page, notActive = false) {
		return {page, state: this.getPageState(page, notActive)};
	}
	
	/**
	 * @param {number} page
	 * @param {boolean} notActive Must not be the active
	 * @return {string}
	 */
	getPageState(page, notActive = false) {
		if( page === this.currentPage ) {
			return notActive ? "disabled" : "active";
		}
		if( page < this.pageMin || this.pageMax < page ) {
			return "disabled";
		}
		return "link";
	}
	
	/**
	 * Calculate small delta
	 *
	 * @param {number} value
	 * @return {number}
	 */
	#calculateDelta1(value) {
		return Math.ceil(this.#calculatePowMultiplier(value / 5) * this.#mapStepD1(this.#calculateFirstDigit(value * 2)));
	}
	
	/**
	 * Calculate large delta
	 *
	 * @param {number} value
	 * @return {number}
	 */
	#calculateDelta2(value) {
		return Math.ceil(this.#calculatePowMultiplier(value / 5) * this.#mapStepD2(this.#calculateFirstDigit(value * 2)));
	}
	
	/**
	 * Calculate the multiplier for first digit
	 *
	 * @param {number} value
	 * @return {number}
	 * @example 200->100 700->100 15->10 70->10 6->1
	 */
	#calculatePowMultiplier(value) {
		return Math.pow(10, Math.floor(Math.log10(value)));
	}
	
	#calculateFirstDigit(value) {
		return Math.floor(value / Math.pow(10, Math.floor(Math.log10(value))));
	}
	
	#mapStepD1(n) {
		if( n === 1 ) {
			return 1;
		}
		if( n < 5 ) {
			return 2;
		}
		return 5;
	}
	
	#mapStepD2(n) {
		if( n === 1 ) {
			return 2;
		}
		if( n < 5 ) {
			return 5;
		}
		return 10;
	}
	
}

