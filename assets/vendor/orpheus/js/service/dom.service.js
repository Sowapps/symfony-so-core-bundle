import { isDefined, isDomElement, isJquery, isObject } from "../orpheus.js";

class DomService {
	
	filters = {
		'upper': value => {
			return value.toUpperCase();
		},
		'date': (value, format) => {
			value = moment(value);
			return value.format(format);
		},
	}
	
	getInputs(element) {
		return element.querySelectorAll('input,select,textarea');
	}
	
	getViewportSize() {
		// https://stackoverflow.com/questions/1248081/how-to-get-the-browser-viewport-dimensions
		return {
			width: Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0),
			height: Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0),
		};
	}
	
	addJsLibrary(url, options = {}) {
		const deferred = $.Deferred();
		
		const script = document.createElement('script');
		
		script.addEventListener('load', () => {
			deferred.resolve();
		});
		script.addEventListener('error', () => {
			deferred.reject('Failed to load script');
		});
		
		// load the script file
		script.src = url;
		document.body.appendChild(script);
		
		return deferred.promise();
	}
	
	resolveCondition(condition, data) {
		const conditionParts = condition.split(' ');
		var invert = conditionParts[0] === 'not';
		var property = conditionParts.length > 1 ? conditionParts[1] : conditionParts[0];
		return invert ^ Number(!!data[property]);
	}
	
	renderTemplateString(string, data) {
		// Clean contents
		let template = string.replace(/>\s+</ig, '><');
		
		// Resolve values in attributes
		// Deprecated for TWIG compatibility, double brackets
		template = template.replace(/\{\{ ([^\}]+) \}\}/ig, (all, variable) => {
			return this.resolveValue(variable, data);
		});
		// Yes we want this one, simple brackets
		template = template.replace(/\{ ?([^\}]+) ?\}/ig, (all, variable) => {
			return this.resolveValue(variable, data);
		});
		// For url compatibility, url encoded brackets
		template = template.replace(/\%7B\%20([^\%]+)\%20\%7D/ig, (all, variable) => {
			return this.resolveValue(variable, data);
		});
		
		return template;
	}
	
	resolveValue(value, data) {
		// First identify the value
		const firstChar = value.charAt(0);
		if( firstChar === '"' || firstChar === "'" ) {
			// Raw string
			value = value.slice(1, value.length - 1);
			return value;
		}
		let variable = value;
		let tokens = variable.split('|');
		// Calculate value
		const propertyTree = tokens.shift().trim().split('.');
		value = data;
		for( const property of propertyTree ) {
			value = value[property];
			if( value === undefined || value === null ) {
				// Stop now
				break;
			}
		}
		// Apply filters on value
		tokens.forEach(filterCall => {
			filterCall = filterCall.trim();
			// noinspection RegExpRedundantEscape
			const filterCallMatch = filterCall.match(/^([^\(]+)(?:\(([^\)]*)\))?$/);
			const filter = filterCallMatch[1];
			let filterArgs = [];
			if( filterCallMatch[2] ) {
				filterArgs = filterCallMatch[2].split(/,\s?/).map(argValue => this.resolveValue(argValue, data));
			}
			// Add value as first argument
			filterArgs.unshift(value);
			
			const filterCallback = this.filters[filter];
			if( filterCallback ) {
				value = filterCallback(...filterArgs);
			} else {
				console.warn('Unknown filter ' + filter);
			}
		});
		return value;
	}
	
	renderTemplateElement($template, data, prefix) {
		// Resolve conditional displays
		$template.find('[data-if]').each((index, $element) => {
			$element = $($element);
			if( !this.resolveCondition($element.data('if'), data) ) {
				$element.remove();
			}
		});
		// Fix image loading preventing
		$template.find('[data-src]').each((index, $element) => {
			$element = $($element);
			$element.attr('src', $element.data('src'));
			$element.removeAttr('data-src').data('src', null);
		});
		// Fix link crawling
		$template.find('[data-href]').each((index, $element) => {
			$element = $($element);
			$element.attr('href', $element.data('href'));
			$element.removeAttr('data-href').data('href', null);
		});
		// Resolve values in content
		if( prefix ) {
			$template.fill(prefix, data);
		}
	}
	
	loadTemplate(key, target) {
		const deferred = $.Deferred();
		const $target = $(target);
		$target.load('/api/template/' + key, (responseText, textStatus, jqXHR) => {
			if( textStatus === 'success' ) {
				deferred.resolve($target);
			} else {
				deferred.reject($target);
			}
		});
		return deferred.promise();
	}
	
	global() {
		return $(window);
	}
	
	updateTemplateItem($item, data) {
		$item = $($item);
		if( !$item.data('rendering_template') ) {
			console.error('Not a template item', $item);
			return;
		}
		return this.renderTemplate($item.data('rendering_template'), data, $item.data('rendering_prefix'), $item.data('rendering_wrap'))
			.then(($newItem) => {
				$item.after($newItem);
				$item.remove();
				return $newItem;
			});
	}
	
	renderTemplate(template, data, options, wrapBc) {
		let isHtml = true;
		if( !template ) {
			throw new Error('Empty template');
		}
		if( isDomElement(template) ) {
			template = $(template);
		}
		if( !isObject(options) ) {
			options = {prefix: options};
		}
		if( isDefined(wrapBc) ) {
			options.wrap = wrapBc;
		}
		options = $.extend({
			prefix: null,
			wrap: false,
			immediate: false,// Some lib does not handle async
		}, options);
		if( isJquery(template) ) {
			if( template.is('template') ) {
				let content = template.html();
				if( !content ) {
					isHtml = false;
					content = template.prop('content').textContent.trim();
				}
				if( !content && template.data('key') ) {
					return this.loadTemplate(template.data('key'), template)
						.then(() => {
							return this.renderTemplate(template, data, options.prefix);
						});
				}
				if( !content ) {
					console.error('Template has no contents', template);
				}
				template = content;
			} else {
				template = template.html();
			}
		}
		let renderedTemplate = this.renderTemplateString(template, data);
		// Create jquery object preventing jquery to preload images
		renderedTemplate = renderedTemplate.replace('\\ssrc=', ' data-src=');
		// If using text, we need to create a text node to use jQuery
		let $item = $(isHtml ? renderedTemplate : document.createTextNode(renderedTemplate));
		if( options.wrap ) {
			$item = $('<div class="template-contents"></div>').append($item);
		}
		$item.data('rendering_template', template);
		$item.data('rendering_options', options);
		this.renderTemplateElement($item, data, options.prefix);
		return options.immediate ? $item : $.when($item);
	}
	
	toggle(element, show) {
		element.style.display = show ? null : 'none';
	}
	
	showElement($element) {
		$element.show();
	}
	
	buildAlert(message, type) {
		return $('<div class="alert" role="alert"></div>').addClass('alert-' + type).text(message);
	}
	
	buildErrorAlert(message) {
		return this.buildAlert(message, 'danger');
	}
	
	show(selector, object, hideSelector, prefix) {
		$(selector).each((index, element) => {
			let $element = $(element);
			if( $element.is('template') ) {
				this.renderTemplate($element, object, prefix)
					.then(($clone) => {
						$clone.addClass($element.prop('class'));
						$clone.data('controller', $element.data('controller'));
						$element.after($clone);
						this.showElement($clone);
					});
			} else {
				this.showElement($element);
			}
		});
		$(hideSelector).each((index, element) => {
			let $element = $(element);
			if( $element.data('rendering_template') ) {
				// Remove templates' clone
				$element.remove();
			} else {
				// Only hide others
				$element.hide();
			}
		});
	}
	
}

export const domService = new DomService();
