
class stream {

	constructor(identifier) {
		const component = document.querySelector('[data-component="'+ identifier +'"]');

		if (!component) {
			console.warn("Component not found for identifier:", identifier);
			return;
		}

		[...component.children].forEach(child => {
			if (child.tagName === "FRAGMENT") {
				while (child.firstChild) {
					component.insertBefore(child.firstChild, child);
				}
				child.remove();
			}
		});

		this.component = component;
		this.clickEvent();
		this.keyEvent();
		this.formEvent();
		this.modelEvent();
	}

	modelEvent() {
		const models_r = {};
		const models = this.component.querySelectorAll("[wire\\:model]");
		models.forEach(model => {
			const name = model.getAttribute("wire:model");
			models_r[name] = model.value;
		});

		return models_r;
	}

	formEvent() {
		const forms = this.component.querySelectorAll("[wire\\:submit]");
		forms.forEach(form => {
			form.addEventListener("submit", (e) => {
				e.preventDefault();
				const action = form.getAttribute("wire:submit");
				const formData = new FormData(form);

				formData.append('_method', action);
				this.submitRequest(formData);
			});
		});
	}

	clickEvent() {
		const events = this.component.querySelectorAll("[wire\\:click]");
		events.forEach(elem => {
			elem.addEventListener('click', (e) => {
				e.preventDefault();
				const action = elem.getAttribute("wire:click");
				const formData = new FormData();

				formData.append('_method', action);
				this.submitRequest(formData);
			});
		});
	}

	keyEvent() {
		const keys = [
			"enter", "escape", "backspace", "arrowUp", "arrowDown", "arrowLeft", "arrowRight",
			"space", "shift", "ctrl", "alt", "tab", "delete", "keyPress"
		];
		const selector = `[wire\\:keydown]` + keys.map(key => `, [wire\\:keydown\\.${key}]`).join('');
		const elements = this.component?.querySelectorAll(selector) || [];

		elements.forEach(element => {
			for (let attr of element.attributes) {
				if (attr.name.startsWith("wire:keydown.")) {
					element.addEventListener("keydown", (e) => {

						let keyEvent = attr.name.split(".")[1];
						let action = element.getAttribute(`wire:keydown.${keyEvent}`);

						let pressedKey = e.key.toLowerCase();
						let mappedKey = keyEvent.toLowerCase();

						const keyMap = {
							"arrowup": "arrowUp",
							"arrowdown": "arrowDown",
							"arrowleft": "arrowLeft",
							"arrowright": "arrowRight",
							" ": "space"
						};

						if (pressedKey === mappedKey || keyMap[pressedKey] === mappedKey) {
							const formData = new FormData();

							if (action.includes("event.target.value") && element.value !== undefined)
								action = action.replace("event.target.value", `'${element.value}'`);

							formData.append('_method', action);
							this.submitRequest(formData);
						}
					});
				}
			}
		});
	}

	submitRequest(formData) {

		const token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
		const component = this.component.getAttribute('data-component');
		const properties = this.component.getAttribute('data-properties');
		const models = this.modelEvent();

		formData.append('_component', component);
		formData.append('_properties', properties);
		formData.append('_models', JSON.stringify(models));

		fetch(`/api/stream-wire/${component}`, {
			method: "POST",
			headers: {
				"X-STREAM-WIRE": "true",
				"X-CSRF-TOKEN": token
			},
			body: formData
		})
		.then(response => response.text())
		.then(html => {
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, "text/html");
			const newComponent = doc.querySelector(`[data-component="${this.component.getAttribute('data-component')}"]`);

			if (newComponent) {
				this.component.replaceWith(newComponent);
				this.component = newComponent;

				document.querySelectorAll("script[data-dynamic]").forEach(script => script.remove());

				newComponent.querySelectorAll("script").forEach(script => {
					const newScript = document.createElement("script");
					if (script.src) {
						newScript.src = script.src;
						newScript.async = script.async;
					} else {
						newScript.textContent = script.textContent;
					}
					newScript.setAttribute("data-dynamic", "true");
					document.body.appendChild(newScript);
				});

			} else {
				console.warn("Updated component not found in response.");
			}
		})
		.catch(error => {
			console.error("Error submitting request:", error);
		});
	}
}