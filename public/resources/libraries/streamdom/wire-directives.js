export function load(stream)
{
	stream.wire('wire:model', function(directive, expression) {
		stream.payload(directive, expression);
	});

	stream.wire('wire:click', function(element, expression, directive) {
		element.addEventListener('click', (e) => {
			if (directive.includes('.prevent')) {
				e.preventDefault();
			}
			stream.submit({'_method': expression});
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
				}, delays[matchedDelay]);
			} else {
				stream.submit({ '_method': action });
			}
		});
	}, ['100ms', '300ms', '500ms', '1000ms', '1300ms', '1500ms', '2000ms']);

	stream.wire('wire:keydown.enter', function (element, expression, directive) {
		element.addEventListener('keydown', (e) => {
			let pressedKey = e.key.toLowerCase();
			let action = expression;

			if (pressedKey === 'enter') {
				if (directive.includes('.prevent'))
					e.preventDefault();

				if (action.includes("event.target.value"))
					action = action.replace("event.target.value", `'${element.value}'`);

				stream.submit({'_method': action});

				if (directive.includes('.clear'))
					element.value = '';
			}
		});
	}, ['clear','prevent']);

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

				if (directive.includes('.clear'))
					element.value = '';
			}
		});
	}, ['clear','prevent']);

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

				if (directive.includes('.clear'))
					element.value = '';
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
}

