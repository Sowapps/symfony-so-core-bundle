// Todo externalize translation into a class ?
let _Translations = {};

function t(key, parameters) {
	let string = _Translations && _Translations[key] ? _Translations[key] : key;
	if( parameters ) {
		for( const [token, value] of Object.entries(parameters) ) {
			string = string.replace('{' + token + '}', value);
		}
	}
	return string;
}

export function provideTranslations(translations) {
	_Translations = {..._Translations, ...translations};
}

provideTranslations({
	'ok': "OK",
	'cancel': "Cancel",
});

function clone(obj) {
	var target = {};
	for( var i in obj ) {
		if( obj.hasOwnProperty(i) ) {
			target[i] = obj[i];
		}
	}
	return target;
}

export function basename(string) {
	string = string.replace(/\\/g, '/');
	return string.substring(string.lastIndexOf('/') + 1);
}

export function nl2br(str, is_xhtml) {
	//	discuss at: http://phpjs.org/functions/nl2br/
	var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br/>' : '<br>';
	return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

export function formatDouble(n) {
	return ("" + n).replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

export function isDefined(v) {
	return v !== undefined;
}

export function isSet(v) {
	return isDefined(v) && v !== null;
}

export function isScalar(obj) {
	return (/string|number|boolean/).test(typeof obj);
}

export function isString(v) {
	return typeof (v) === 'string';
}

export function isObject(v) {
	return v != null && typeof (v) === 'object';
}

export function isPureObject(v) {
	return isObject(v) && v.constructor === Object;
}

export function isArray(v) {
	return isObject(v) && v.constructor === Array;
}

export function isFunction(v) {
	return typeof (v) === 'function';
}

export function isDomElement(obj) {
	return isObject(obj) && obj instanceof HTMLElement;
}

export function isJquery(v) {
	return isObject(v) && typeof (v.jquery) !== 'undefined';
}

export function notJquery(v) {
	return isObject(v) && typeof (v.jquery) === 'undefined';
}

export function daysInMonth(year, month) {
	return new Date(year, month, 0).getDate();
}

export function str2date(val) {
	if( !val ) { /*debug(val);*/
		return null;
	}
	var d = val.split("/");
	if( !d || !d.length || d.length < 3 ) {
		return false;
	}
	return new Date(d[2], d[1] - 1, d[0]);
}

function leadZero(val) {
	val = val * 1;
	return val < 10 ? '0' + val : val;
}

var getLocation = function (uri) {
	var l = document.createElement("a");
	l.href = uri;
	return l;
};

function bintest(value, reference) {
	return checkFlag(value, reference);
}

function checkFlag(value, reference) {
	return ((value & reference) == reference);
}

export function number_format(number, decimals, dec_point, thousands_sep) {
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	const n = !isFinite(+number) ? 0 : +number,
		prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
		sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
		dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
		toFixedFix = function (n, prec) {
			var k = Math.pow(10, prec);
			return '' + Math.floor(n * k) / k;
		};
	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	let s = toFixedFix(n, prec).split('.');
	if( s[0].length > 3 ) {
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	}
	if( (s[1] || '').length < prec ) {
		s[1] = s[1] || '';
		s[1] += new Array(prec - s[1].length + 1).join('0');
	}
	return s.join(dec);
}

String.prototype.capitalize = function () {
	if( typeof this !== "string" ) {
		return this;
	}
	return this.charAt(0).toUpperCase() + this.slice(1).toLowerCase();
};

String.prototype.upFirst = function () {
	return this.charAt(0).toUpperCase() + this.slice(1);
};

Date.prototype.getFullDay = function () {
	return "" + this.getFullYear() + leadZero(this.getMonth()) + leadZero(this.getDate());
};

// Source: http://stackoverflow.com/questions/3954438/remove-item-from-array-by-value
Array.prototype.remove = function () {
	var what, a = arguments, L = a.length, ax;
	while( L && this.length ) {
		what = a[--L];
		while( (ax = this.indexOf(what)) !== -1 ) {
			this.splice(ax, 1);
		}
	}
	return this;
};
if( !Array.prototype.indexOf ) {
	Array.prototype.indexOf = function (what, i) {
		i = i || 0;
		var L = this.length;
		while( i < L ) {
			if( this[i] === what ) return i;
			++i;
		}
		return -1;
	};
}

if( global.KeyEvent === undefined ) {
	global.KeyEvent = {
		DOM_VK_CANCEL: 3,
		DOM_VK_HELP: 6,
		DOM_VK_BACK_SPACE: 8,
		DOM_VK_TAB: 9,
		DOM_VK_CLEAR: 12,
		DOM_VK_RETURN: 13,
		DOM_VK_ENTER: 14,
		DOM_VK_SHIFT: 16,
		DOM_VK_CONTROL: 17,
		DOM_VK_ALT: 18,
		DOM_VK_PAUSE: 19,
		DOM_VK_CAPS_LOCK: 20,
		DOM_VK_ESCAPE: 27,
		DOM_VK_SPACE: 32,
		DOM_VK_PAGE_UP: 33,
		DOM_VK_PAGE_DOWN: 34,
		DOM_VK_END: 35,
		DOM_VK_HOME: 36,
		DOM_VK_LEFT: 37,
		DOM_VK_UP: 38,
		DOM_VK_RIGHT: 39,
		DOM_VK_DOWN: 40,
		DOM_VK_PRINTSCREEN: 44,
		DOM_VK_INSERT: 45,
		DOM_VK_DELETE: 46,
		DOM_VK_0: 48,
		DOM_VK_1: 49,
		DOM_VK_2: 50,
		DOM_VK_3: 51,
		DOM_VK_4: 52,
		DOM_VK_5: 53,
		DOM_VK_6: 54,
		DOM_VK_7: 55,
		DOM_VK_8: 56,
		DOM_VK_9: 57,
		DOM_VK_SEMICOLON: 59,
		DOM_VK_EQUALS: 61,
		DOM_VK_A: 65,
		DOM_VK_B: 66,
		DOM_VK_C: 67,
		DOM_VK_D: 68,
		DOM_VK_E: 69,
		DOM_VK_F: 70,
		DOM_VK_G: 71,
		DOM_VK_H: 72,
		DOM_VK_I: 73,
		DOM_VK_J: 74,
		DOM_VK_K: 75,
		DOM_VK_L: 76,
		DOM_VK_M: 77,
		DOM_VK_N: 78,
		DOM_VK_O: 79,
		DOM_VK_P: 80,
		DOM_VK_Q: 81,
		DOM_VK_R: 82,
		DOM_VK_S: 83,
		DOM_VK_T: 84,
		DOM_VK_U: 85,
		DOM_VK_V: 86,
		DOM_VK_W: 87,
		DOM_VK_X: 88,
		DOM_VK_Y: 89,
		DOM_VK_Z: 90,
		DOM_VK_CONTEXT_MENU: 93,
		DOM_VK_NUMPAD0: 96,
		DOM_VK_NUMPAD1: 97,
		DOM_VK_NUMPAD2: 98,
		DOM_VK_NUMPAD3: 99,
		DOM_VK_NUMPAD4: 100,
		DOM_VK_NUMPAD5: 101,
		DOM_VK_NUMPAD6: 102,
		DOM_VK_NUMPAD7: 103,
		DOM_VK_NUMPAD8: 104,
		DOM_VK_NUMPAD9: 105,
		DOM_VK_MULTIPLY: 106,
		DOM_VK_ADD: 107,
		DOM_VK_SEPARATOR: 108,
		DOM_VK_SUBTRACT: 109,
		DOM_VK_DECIMAL: 110,
		DOM_VK_DIVIDE: 111,
		DOM_VK_F1: 112,
		DOM_VK_F2: 113,
		DOM_VK_F3: 114,
		DOM_VK_F4: 115,
		DOM_VK_F5: 116,
		DOM_VK_F6: 117,
		DOM_VK_F7: 118,
		DOM_VK_F8: 119,
		DOM_VK_F9: 120,
		DOM_VK_F10: 121,
		DOM_VK_F11: 122,
		DOM_VK_F12: 123,
		DOM_VK_F13: 124,
		DOM_VK_F14: 125,
		DOM_VK_F15: 126,
		DOM_VK_F16: 127,
		DOM_VK_F17: 128,
		DOM_VK_F18: 129,
		DOM_VK_F19: 130,
		DOM_VK_F20: 131,
		DOM_VK_F21: 132,
		DOM_VK_F22: 133,
		DOM_VK_F23: 134,
		DOM_VK_F24: 135,
		DOM_VK_NUM_LOCK: 144,
		DOM_VK_SCROLL_LOCK: 145,
		DOM_VK_COMMA: 188,
		DOM_VK_PERIOD: 190,
		DOM_VK_SLASH: 191,
		DOM_VK_BACK_QUOTE: 192,
		DOM_VK_OPEN_BRACKET: 219,
		DOM_VK_BACK_SLASH: 220,
		DOM_VK_CLOSE_BRACKET: 221,
		DOM_VK_QUOTE: 222,
		DOM_VK_META: 224
	};
}
var Modifier = {
	CONTROL: 1,
	SHIFT: 2,
	ALT: 4,
	META: 8,
}

// Export globals
global.t = t;
global.provideTranslations = provideTranslations;
