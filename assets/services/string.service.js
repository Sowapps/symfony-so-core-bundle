/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */
import {Is} from "../helpers/is.helper.js";

class StringService {
	
	generateId() {
		return performance.now().toString(36).replace(".", "");
	}
	
	replace(str, replacement) {
		Object.entries(replacement).forEach(([key, value]) => {
			if( !this.isStringConvertible(value) ) {
				return;// Ignore non scalar
			}
			str = str.replace("{" + key + "}", value);
		});
		return str;
	}
	
	capitalize(str) {
		if( typeof str !== "string" ) {
			return "";
		}
		
		return str.charAt(0).toUpperCase() + str.slice(1);
	}
	
	lowerFirst(str) {
		if( typeof str !== "string" ) {
			return "";
		}
		
		return str.charAt(0).toLowerCase() + str.slice(1);
	}
	
	isStringConvertible(str) {
		return (/boolean|number|string/).test(typeof str);
	}
	
	nl2br(text) {
		return (text + "").replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1<br>$2");
	}
	
	escapeAttribute(text) {
		return (text + "")
			.replace(/'/g, "&#39;")
			.replace(/"/g, "&#34;")
			;
	}
	
	escapeHtml(str) {
		if( !Is.string(str) ) {
			throw new Error("Invalid string");
		}
		return str
			.replace(/&/g, "&amp;") // Replace ampersand
			.replace(/</g, "&lt;")  // Replace less than
			.replace(/>/g, "&gt;")  // Replace greater than
			.replace(/"/g, "&quot;") // Replace double quote
			.replace(/'/g, "&apos;"); // Replace single quote
	}
	
}

export const stringService = new StringService();
