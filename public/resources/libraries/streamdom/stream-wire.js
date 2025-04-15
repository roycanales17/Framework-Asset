import {load} from "./wire-directives.js";

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

	submit(payload, target) {

		if (target) {
			this.component = document.querySelector('[data-component="'+ target +'"]');
			this.identifier = target;
		}

		let models = {};
		let compiled = {};
		let response = null;
		let form = new FormData();
		let timeStarted = performance.now();
		let compiledComponents = this.getAllDataComponentElements(this.component);
		let properties = this.component.getAttribute('data-properties');
		let payloads = JSON.parse(this.component.getAttribute('data-payloads') || '{}');

		this.trigger({'status': false, 'response': response, 'duration': 0});

		this.component.querySelectorAll(this.container).forEach(fragment => {
			const comp = fragment.getAttribute("data-component");
			compiled[comp] = fragment.outerHTML;
		});

		for (const [directive, values] of Object.entries(payloads)) {
			values.forEach(name => {
				const model = this.component.querySelector(`[${CSS.escape(directive)}='${name}']`);
				models[name] = model.value;
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
					onBeforeNodeDiscarded: node => true
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
			this.recompile(compiledComponents, response);

			this.component.setAttribute('data-payloads', JSON.stringify(payloads))
		});
	}

	payload(directive, name) {
		const el = this.component;
		const currentPayloads = JSON.parse(el.getAttribute('data-payloads') || '{}');

		if (currentPayloads[directive] === undefined)
			currentPayloads[directive] = [];

		currentPayloads[directive].push(name);
		el.setAttribute('data-payloads', JSON.stringify(currentPayloads));
	}

	findTheID(element, search, callback) {
		if (element.hasAttribute(search)) {
			const targetID = element.getAttribute(search);
			const targetElement = document.querySelector(`[data-id="${targetID}"]`);

			if (targetElement && targetElement.hasAttribute('data-component')) {
				const identifier = targetElement.getAttribute('data-component');
				callback(identifier);
				return;
			}
		}

		callback(false);
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

	recompile(compiled, updated) {
		const parser = new DOMParser();
		const doc = parser.parseFromString(updated, 'text/html');
		const modified = doc.querySelectorAll('[data-component]');

		const modifiedValues = Array.from(modified).map(el => el.getAttribute('data-component'));
		const originalValues = Array.from(compiled).map(el => el.getAttribute('data-component'));

		const unique = [
			...modifiedValues.filter(val => !originalValues.includes(val)),
			...originalValues.filter(val => !modifiedValues.includes(val))
		];

		unique.forEach(identifier => {
			const isExist = document.querySelector('[data-component="'+ identifier +'"]');
			if (isExist)
				init(identifier);
		});
	}

	getAllDataComponentElements(root) {
		const elements = Array.from(root.querySelectorAll('[data-component]'));
		if (root.hasAttribute('data-component')) {
			elements.unshift(root);
		}
		return elements;
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