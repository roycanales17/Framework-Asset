import {load} from "./wire-directives.js";

/**
 *	## Todo:
 *  1. `Allow to submit different component`
 */

class stream {

	constructor(identifier) {
		const component = document.querySelector('[data-component="'+ identifier +'"]');
		if (!component) {
			console.error("Component not found for identifier:", identifier);
		}

		this.container = 'fragment';
		this.component = component;
		this.identifier = identifier;
		this.token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
	}

	wire(directive, callback, externals = []) {
		const baseSelector = this.escape(directive);

		if (externals.length) {
			this.wire(directive, callback);

			const matchedExternals = externals.filter(val => this.component.outerHTML.includes(val));
			const combinations = this.getCombinationsOnly(matchedExternals);

			combinations.forEach(mods => {
				const suffix = mods.length ? '.' + mods.join('.') : '';
				const fullDirective = directive + suffix;
				const selector = this.escape(fullDirective);

				this.getScopedElements(selector).forEach(element => {
					this.perform({
						element: element,
						directive: fullDirective,
						fragment: this.component,
						identifier: this.identifier,
						expression: element.getAttribute(fullDirective)
					}, callback);
				});
			});
		} else {
			this.getScopedElements(baseSelector).forEach(element =>
				this.perform({
					element: element,
					directive: directive,
					fragment: this.component,
					identifier: this.identifier,
					expression: element.getAttribute(directive)
				}, callback)
			);
		}
	}

	submit(payload) {

		let models = {};
		let compiled = {};
		let response = null;
		let form = new FormData();
		let timeStarted = performance.now();
		let properties = this.component.getAttribute('data-properties');

		this.trigger({'status': false, 'response': response, 'duration': 0});

		this.component.querySelectorAll(this.container).forEach(fragment => {
			const comp = fragment.getAttribute("data-component");
			compiled[comp] = fragment.outerHTML;
		});

		for (let i = 0; i < this.payloads.length; i++) {
			let directive = this.payloads[i];
			this.getScopedElements(this.escape(directive)).forEach(element => {
				const name = element.getAttribute(directive);
				models[name] = element.value;
			});
		}

		Object.entries(payload).forEach(([key, value]) => {
			form.append(key, value);
		});

		form.append('_component', this.identifier);
		form.append('_properties', properties);
		form.append('_models', JSON.stringify(models));
		form.append('_compiled', JSON.stringify(compiled));

		fetch(`/api/stream-wire/${this.identifier}`, {
			method: "POST",
			headers: {
				"X-STREAM-WIRE": true,
				"X-CSRF-TOKEN": this.token
			},
			body: form
		})
		.then(response => response.text())
		.then(html => {
			if (html) {
				morphdom(this.component, html, {
					getNodeKey: node => {
						if (node.nodeType !== 1) return null;
						return node.id || node.getAttribute("data-component");
					},
					onBeforeElUpdated: (fromEl, toEl) => {
						if (fromEl.isEqualNode(toEl))
							return false;

						return true;
					},
					onBeforeNodeDiscarded: (node) => {
						return true;
					}
				});
				response = html;
			} else {
				console.warn("Updated component not found in response.");
			}
		})
		.catch(error => {
			console.error("Error submitting request:", error);
		})
		.finally(() => {
			let timeEnded = performance.now();
			let totalMs = timeEnded - timeStarted;
			this.trigger({'status': true, 'response': response, 'duration': totalMs});
		});
	}

	payload(directive) {
		if (!this.payloads)
			this.payloads = [];

		this.payloads.push(directive);
	}

	escape(str) {
		const escaped = str
			.replace(/:/g, '\\:')
			.replace(/\./g, '\\.');

		return `[${escaped}]`;
	}

	perform(params, action) {
		const paramNames = this.getParamNames(action);
		const args = paramNames.map(name => params[name]);

		action(...args);
	}

	getScopedElements(selector) {
		const root = this.component;
		const excludeTag = this.container;
		const elements = root.querySelectorAll(selector);

		return Array.from(elements).filter(el => {
			let current = el.parentElement;
			while (current && current !== root) {
				if (current.tagName.toLowerCase() === excludeTag.toLowerCase()) {
					return false;
				}
				current = current.parentElement;
			}
			return true;
		});
	}

	getParamNames(func) {
		const fnStr = func.toString().replace(/\/\/.*$|\/\*[\s\S]*?\*\//gm, '');
		const result = fnStr.slice(fnStr.indexOf('(') + 1, fnStr.indexOf(')')).match(/([^\s,]+)/g);
		return result === null ? [] : result;
	}

	getCombinationsOnly(array) {
		const results = [];

		const recurse = (prefix, rest) => {
			if (prefix.length > 0) results.push([...prefix]);
			for (let i = 0; i < rest.length; i++) {
				recurse([...prefix, rest[i]], rest.slice(i + 1));
			}
		};

		recurse([], array);
		return results;
	}

	stringToIntId(str) {
		let hash = 0;
		for (let i = 0; i < str.length; i++) {
			hash = (hash << 5) - hash + str.charCodeAt(i);
			hash |= 0;
		}

		return Math.abs(hash);
	}

	ajax(callback) {
		window.addEventListener(`wire-loader-${this.stringToIntId(this.identifier)}`, (event) => callback(event.detail))
	}

	trigger(data) {
		window.dispatchEvent(new CustomEvent(`wire-loader-${this.stringToIntId(this.identifier)}`, {detail: data}))
	}

	static init(component) {
		load(new stream(component));
	}
}

export function init(identifier) {
	stream.init(identifier)
}