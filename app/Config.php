<?php

return [
	'environment' => '../.env',
	'stream' => '../views/',

	'cache' => [
		'server' => config('MEMCACHE_SERVER_NAME'),
		'port' => config('MEMCACHE_PORT'),
		'enabled' => false
	],
	'routes' => [
		'web' => [
			'captured' => function (string $content, int $code) {
				if ($code == 404)
					return;

				// Load the content with template
				App\Content\Blade::render('public/index.html', extract: [
					'g_page_lang' => config('APP_LANGUAGE'),
					'g_page_title' => config('APP_NAME'),
					'g_page_url' => config('APP_URL'),
					'g_page_description' => "Page description here",
					'g_page_content' => $content
				]);
			}
		],
		'api' => [
			'routes' => [
				'api.php'
			],
			'prefix' => 'api',
			'captured' => function (string $content) {
				echo($content);
			}
		]
	]
];