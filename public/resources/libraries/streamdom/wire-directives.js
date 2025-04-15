export function load(stream)
{
	stream.wire('wire:model', function(directive, expression) {
		stream.payload(directive, expression);
	});

	stream.wire('wire:click', function(element, expression, directive, identifier) {
		element.addEventListener('click', (e) => {
			if (directive.includes('.prevent'))
				e.preventDefault();

			stream.findTheID(element, 'wire:target', function(target) {
				if (target)
					identifier = target;

				stream.submit({'_method': expression}, identifier);
			});
		});
	}, ['prevent']);

	stream.wire('wire:submit', function(element, expression, directive) {
		element.addEventListener("submit", (e) => {
			if (directive.includes('.prevent')) {
				e.preventDefault();
			}
			stream.submit({'_method': expression});
		});
	}, ['prevent']);

	stream.wire('wire:keydown.keypress', function (element, expression, directive) {
		let debounceTimer = null;

		element.addEventListener('input', (e) => {
			if (debounceTimer)
				clearTimeout(debounceTimer);

			let activeEl = document.activeElement;
			let value = e.target.value;
			let action = expression;

			if (action.includes("event.target.value"))
				action = action.replace("event.target.value", `'${value}'`);

			const delays = {
				'100ms': 100,
				'300ms': 300,
				'500ms': 500,
				'1000ms': 1000,
				'1300ms': 1300,
				'1500ms': 1500,
				'2000ms': 2000
			};

			const matchedDelay = Object.keys(delays).find(key => directive.includes(key));

			if (matchedDelay) {
				debounceTimer = setTimeout(() => {
					stream.submit({ '_method': action });
					stream.ajax((res) => {
						if (directive.includes('.clear'))
							element.value = '';

						if (res.status && activeEl === element && res.duration >= 1000) {
							element.focus();
						}
					});
				}, delays[matchedDelay]);
			} else {
				stream.submit({ '_method': action });
				stream.ajax((res) => {
					if (directive.includes('.clear'))
						element.value = '';

					if (res.status && activeEl === element && res.duration >= 1000) {
						element.focus();
					}
				});
			}
		});
	}, ['100ms', '300ms', '500ms', '1000ms', '1300ms', '1500ms', '2000ms', 'clear']);

	stream.wire('wire:keydown.enter', function (element, expression, directive, identifier) {
		element.addEventListener('keydown', (e) => {
			let activeEl = document.activeElement;
			let pressedKey = e.key.toLowerCase();
			let action = expression;

			if (pressedKey === 'enter') {
				if (directive.includes('.prevent'))
					e.preventDefault();

				if (action.includes("event.target.value"))
					action = action.replace("event.target.value", `'${element.value}'`);

				stream.findTheID(element, 'wire:target', function(target) {
					if (target)
						identifier = target;

					stream.submit({'_method': action}, identifier);
					stream.ajax(({ status }) => {
						if (status && directive.includes('.clear'))
							element.value = '';

						if (activeEl === element)
							element.focus();
					});
				});
			}
		});
	}, ['clear', 'prevent']);

	stream.wire('wire:keydown.escape', function (element, expression, directive) {
		element.addEventListener('keydown', (e) => {
			let pressedKey = e.key.toLowerCase();
			let action = expression;

			if (pressedKey === 'escape') {
				if (directive.includes('.prevent'))
					e.preventDefault();

				if (action.includes("event.target.value"))
					action = action.replace("event.target.value", `'${element.value}'`);

				stream.submit({'_method': action});
				stream.ajax(({ status }) => {
					if (status && directive.includes('.clear'))
						element.value = '';
				});
			}
		});
	}, ['clear', 'prevent']);

	stream.wire('wire:keydown.backspace', function (element, expression, directive) {
		element.addEventListener('keydown', (e) => {
			let pressedKey = e.key.toLowerCase();
			let action = expression;

			if (pressedKey === 'backspace') {
				if (directive.includes('.prevent'))
					e.preventDefault();

				if (action.includes("event.target.value"))
					action = action.replace("event.target.value", `'${element.value}'`);

				stream.submit({'_method': action});
			}
		});
	}, ['prevent']);

	stream.wire('wire:keydown.tab', function (element, expression, directive) {
		element.addEventListener('keydown', (e) => {
			let pressedKey = e.key.toLowerCase();
			let action = expression;

			if (pressedKey === 'tab') {
				if (directive.includes('.prevent'))
					e.preventDefault();

				if (action.includes("event.target.value"))
					action = action.replace("event.target.value", `'${element.value}'`);

				stream.submit({'_method': action});
				stream.ajax(({ status }) => {
					if (status && directive.includes('.clear'))
						element.value = '';
				});
			}
		});
	}, ['clear','prevent']);

	stream.wire('wire:keydown.delete', function (element, expression, directive) {
		element.addEventListener('keydown', (e) => {
			let pressedKey = e.key.toLowerCase();
			let action = expression;

			if (pressedKey === 'delete') {
				if (directive.includes('.prevent'))
					e.preventDefault();

				if (action.includes("event.target.value"))
					action = action.replace("event.target.value", `'${element.value}'`);

				stream.submit({'_method': action});
			}
		});
	}, ['prevent']);

	stream.wire('wire:loader', function (element, directive, expression) {
		stream.ajax(({status}) => {

			if (directive.includes('classList.add'))
				element.classList[!status ? 'add' : 'remove'](expression);

			if (directive.includes('classList.remove'))
				element.classList[!status ? 'remove' : 'add'](expression);

			if (directive.includes('style')) {
				if (!status) {
					expression.split(';').forEach(style => {
						const [property, value] = style.split(':');
						if (property && value) {
							element.style[property.trim()] = value.trim();
						}
					});
				} else {
					expression.split(';').forEach(style => {
						const [property] = style.split(':');
						if (property) {
							element.style.removeProperty(property.trim());
						}
					});
				}
			}

			if (directive.includes('attr'))
				!status
					? element.setAttribute(expression, true)
					: element.removeAttribute(expression);
		});
	}, ['classList.add', 'classList.remove', 'attr', 'style']);
}

