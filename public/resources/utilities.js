const Utils = {
	// Format number with commas
	formatNumber(num) {
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	},

	// Debounce: delay a function until after delay ms of inactivity
	debounce(func, delay = 300) {
		let timeout;
		return (...args) => {
			clearTimeout(timeout);
			timeout = setTimeout(() => func.apply(this, args), delay);
		};
	},

	// Throttle: limit function call to once per limit ms
	throttle(func, limit = 300) {
		let lastFunc;
		let lastRan;
		return function () {
			const context = this;
			const args = arguments;
			if (!lastRan) {
				func.apply(context, args);
				lastRan = Date.now();
			} else {
				clearTimeout(lastFunc);
				lastFunc = setTimeout(function () {
					if ((Date.now() - lastRan) >= limit) {
						func.apply(context, args);
						lastRan = Date.now();
					}
				}, limit - (Date.now() - lastRan));
			}
		};
	},

	// Capitalize first letter
	capitalize(str) {
		return str.charAt(0).toUpperCase() + str.slice(1);
	},

	// Generate random ID
	randomId(length = 8) {
		return Math.random().toString(36).substr(2, length);
	},

	// Check if element is in viewport
	isInViewport(el) {
		const rect = el.getBoundingClientRect();
		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
			rect.right <= (window.innerWidth || document.documentElement.clientWidth)
		);
	},

	// Smooth scroll to element
	scrollToElement(selector, offset = 0) {
		const el = document.querySelector(selector);
		if (el) {
			window.scrollTo({
				top: el.offsetTop - offset,
				behavior: 'smooth'
			});
		}
	},

	// Copy text to clipboard
	copyToClipboard(text) {
		navigator.clipboard.writeText(text).catch(() => {
			const input = document.createElement("textarea");
			input.value = text;
			document.body.appendChild(input);
			input.select();
			document.execCommand("copy");
			document.body.removeChild(input);
		});
	},

	// Wait for condition to be true
	waitFor(conditionFn, checkInterval = 100) {
		return new Promise(resolve => {
			const interval = setInterval(() => {
				if (conditionFn()) {
					clearInterval(interval);
					resolve();
				}
			}, checkInterval);
		});
	},

	// Sleep for x milliseconds
	sleep(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}
};