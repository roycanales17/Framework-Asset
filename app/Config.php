<?php

	use App\Utilities\Blueprints\CacheDriver;

	return [

	/*
	|--------------------------------------------------------------------------
	| Stream Wire Path
	|--------------------------------------------------------------------------
	|
	| Specifies the path to locate view files used for Stream Wire components.
	| This allows rendering components via file path instead of class names.
	|
	*/
	'stream' => ['../views/'],

	/*
	|--------------------------------------------------------------------------
	| Cache Configuration
	|--------------------------------------------------------------------------
	|
	| Define the caching system used throughout the application.
	| Set the 'driver' key to either 'redis' or 'memcached' to
	| specify the default cache engine.
	|
	| Each cache driver supports its own server configuration:
	| - 'server': Hostname or IP of the cache server
	| - 'port': Port the cache service is listening on
	|
	| You can toggle or configure these settings based on your
	| infrastructure and caching preferences.
	|
	*/
	'cache' => [
		// The default cache driver to use: 'redis' or 'memcached'
		'driver' => 'redis',

		// Redis configuration
		'redis' => [
			'driver' => CacheDriver::Redis,
			'server' => config('REDIS_SERVER_NAME', 'redis'),
			'port' => config('REDIS_PORT', '6379')
		],

		// Memcached configuration
		'memcached' => [
			'driver' => CacheDriver::Memcached,
			'server' => config('MEMCACHE_SERVER_NAME', 'memcached'),
			'port' => config('MEMCACHE_PORT', '11211')
		]
	],

	/*
	|--------------------------------------------------------------------------
	| Route Configuration
	|--------------------------------------------------------------------------
	|
	| Define route-specific settings and content capturing behaviors for both
	| web and API routes. These handlers can be used for templating or raw output.
	|
	*/
	'routes' => [

		/*
		|--------------------------------------------------------------------------
		| Default Web Routes Configuration
		|--------------------------------------------------------------------------
		|
		| Handles rendering of content responses. If the HTTP status code is 404,
		| the capture will be skipped. Otherwise, content will be injected into
		| the specified Blade template.
		|
		*/
		'web' => [
			'captured' => function (string $content, int $code) {
				if ($code == 404) return;

				App\Content\Blade::render('public/index.html', extract: [
					'g_page_lang' => config('APP_LANGUAGE'),
					'g_page_title' => config('APP_NAME'),
					'g_page_url' => config('APP_URL'),
					'g_page_description' => "Page description here",
					'g_page_content' => $content
				]);
			}
		],

		/*
		|--------------------------------------------------------------------------
		| API Routes Configuration
		|--------------------------------------------------------------------------
		|
		| Handles raw output of captured API content.
		|
		*/
		'api' => [
			'routes' => ['api.php'],
			'prefix' => 'api',
			'captured' => function (string $content) {
				echo($content);
			}
		]
	]
];