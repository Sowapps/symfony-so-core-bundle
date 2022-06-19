import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
	
	connect() {
		this.element.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
		this.element.dispatchEvent(new Event('change'));
	}
	
}
