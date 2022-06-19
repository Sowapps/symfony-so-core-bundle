/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

class StringService {
	
	generateId() {
		return performance.now().toString(36).replace('.', '');
	}
	
	composeSentence(sentences) {
		let sentence = '{more}';
		for( const sentencePart of sentences ) {
			sentence = stringService.replace(sentence, {
				more: sentencePart,
			});
		}
		
		return stringService.replace(sentence, {
			more: '',
		});
	}
	
	replace(str, replacement) {
		Object.entries(replacement).forEach(([key, value]) => {
			if( !this.isStringConvertible(value) ) {
				return;// Ignore non scalar
			}
			str = str.replace('{' + key + '}', value);
		});
		return str;
	}
	
	capitalize(str) {
		if( typeof str !== "string" ) {
			return "";
		}
		
		return str.charAt(0).toUpperCase() + str.slice(1);
	}
	
	isStringConvertible(str) {
		return (/boolean|number|string/).test(typeof str);
	}
	
	nl2br(text) {
		return (text + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
	}
	
}

export const stringService = new StringService();
