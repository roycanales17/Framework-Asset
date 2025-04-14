import {load} from "./wire-directives.js";

class stream {

	constructor(identifier, container) {
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
			const combinations = this.getCombinations(externals);

			combinations.forEach(mods => {
				const suffix = mods.length ? '.' + mods.join('.') : '';
				const fullDirective = directive + suffix;
				const selector = this.escape(fullDirective);

				this.component.querySelectorAll(selector).forEach(element => {
					this.perform({
						element: element,
						directive: fullDirective,
						identifier: this.identifier,
						expression: element.getAttribute(fullDirective),
						fragment: this.component
					}, callback);
				});
			});
		} else {
			this.component.querySelectorAll(baseSelector).forEach(element =>
				this.perform({
					element: element,
					directive: directive,
					identifier: this.identifier,
					expression: element.getAttribute(directive),
					fragment: this.component
				}, callback)
			);
		}
	}

	submit(payload) {

		let models = {};
		let compiled = {};
		let response = null;
		let timeStarted = performance.now();
		let form = new FormData();
		let properties = this.component.getAttribute('data-properties');

		this.trigger({'status': false, 'response': response, 'duration': 0});

		this.component.querySelectorAll(this.container).forEach(fragment => {
			const comp = fragment.getAttribute("data-component");
			compiled[comp] = fragment.outerHTML;
		});

		for (let i = 0; i < this.payloads.length; i++) {
			let directive = this.payloads[i];
			this.component.querySelectorAll(this.escape(directive)).forEach(element => {
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

	getParamNames(func) {
		const fnStr = func.toString().replace(/\/\/.*$|\/\*[\s\S]*?\*\//gm, ''); // remove comments
		const result = fnStr.slice(fnStr.indexOf('(') + 1, fnStr.indexOf(')')).match(/([^\s,]+)/g);
		return result === null ? [] : result;
	}

	getCombinations(array) {
		const results = [];

		const recurse = (prefix, rest) => {
			results.push(prefix);
			for (let i = 0; i < rest.length; i++) {
				recurse([...prefix, rest[i]], rest.slice(i + 1));
			}
		};

		recurse([], array);

		const permute = (arr) => {
			if (arr.length <= 1) return [arr];
			let perms = [];
			for (let i = 0; i < arr.length; i++) {
				let rest = [...arr.slice(0, i), ...arr.slice(i + 1)];
				for (let sub of permute(rest)) {
					perms.push([arr[i], ...sub]);
				}
			}
			return perms;
		};

		const final = new Set();
		results.forEach(r => {
			if (r.length > 0) {
				permute(r).forEach(p => final.add(p.join('.')));
			}
		});
		final.add('');

		return Array.from(final)
			.filter(Boolean)
			.map(str => str.split('.'));
	}

	ajax(callback) {
		window.addEventListener('wire-loader', (event) => callback(event.detail))
	}

	trigger(data) {
		window.dispatchEvent(new CustomEvent('wire-loader', {detail: data}))
	}

	static init(component) {
		load(new stream(component));
	}
}

export function init(identifier) {
	stream.init(identifier)
}