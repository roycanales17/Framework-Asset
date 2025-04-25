function initialize(resources, callback) {
	const urls = Array.isArray(resources) ? resources : [resources];
	const head = document.head;
	let loadedCount = 0;

	const nodes = head.childNodes;
	let existingComment = null;

	nodes.forEach(node => {
		if (node.nodeType === Node.COMMENT_NODE && node.nodeValue === 'Imported Utilities') {
			existingComment = node;
		}
	});

	if (existingComment) {
		head.removeChild(existingComment);
	}

	const comment = document.createComment('Imported Utilities');
	head.appendChild(comment);

	const jsUrls = urls.filter(url => url.endsWith('.js'));

	urls.forEach((url) => {
		const isCSS = url.endsWith('.css');
		const isJS = url.endsWith('.js');

		const alreadyLoaded = isCSS ?
			[...document.querySelectorAll('link[rel="stylesheet"]')].some(link => link.href === url) :
			[...document.scripts].some(script => script.src === url);

		if (alreadyLoaded) {
			if (isJS) {
				loadedCount++;
				if (loadedCount === jsUrls.length) callback();
			}
			return;
		}

		if (isCSS) {
			const link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = url;
			head.appendChild(link);
		} else if (isJS) {
			const script = document.createElement('script');
			script.src = url;
			script.async = true;
			script.onload = () => {
				loadedCount++;
				if (loadedCount === jsUrls.length) callback();
			};
			head.appendChild(script);
		}
	});
}

initialize('/resources/utilities.js', function() {
	console.log('🚀 Application has started! Ready to build something amazing!');
});