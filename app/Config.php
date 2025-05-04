<?php

	use App\Utilities\Blueprints\CacheDriver;

	return [

	/*
	|--------------------------------------------------------------------------
	| Global Variables
	|--------------------------------------------------------------------------
	|
	| Defines global constants via the `define()` function.
	| This allows for the creation of global variables across the application.
	|
	*/
	'defines' => [],

	/*
	|--------------------------------------------------------------------------
	| Preloaded Files
	|--------------------------------------------------------------------------
	|
	| Specify custom PHP files to include before the application handles any routes.
	| These can contain helper functions, macros, or any bootstrap logic.
	|
	*/
	'preload_files' => [],

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
	| Session Configuration
	|--------------------------------------------------------------------------
	|
	| Controls how sessions are managed in the application.
	| You can specify the handler, lifetime, storage path, and more.
	|
	*/
	'session' => [
		'driver' => 'file',          // Supported: file, redis, database, array, custom
		'lifetime' => 120,           // Session lifetime in minutes
		'expire_on_close' => false,  // Whether session expires when the browser closes
		'encrypt' => false,          // Encrypt session data (if you implement encryption)
		'path' => '/',               // Path where the session is available
		'domain' => null,            // Cookie domain
		'secure' => false,           // Only send cookie over HTTPS
		'http_only' => true,         // Prevent JavaScript access to the cookie
		'same_site' => 'Lax',        // Options: Lax, Strict, None
		'storage_path' => '../storage/sessions', // For 'file' driver
	],

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
	],

	/*
	|--------------------------------------------------------------------------
	| Mailing Configuration
	|--------------------------------------------------------------------------
	|
	| This section controls the application's outbound mailing capabilities.
	| You can configure SMTP credentials and toggle mailing on or off via
	| environment variables. This setup is compatible with providers such
	| as Mailgun, SendGrid, and custom SMTP servers.
	|
	*/
	'mailing' => [

		/*
		|--------------------------------------------------------------------------
		| Enable Mailing
		|--------------------------------------------------------------------------
		|
		| Toggle mailing functionality for the application. Set to true to enable
		| email sending or false to disable all outgoing emails.
		|
		*/
		'enabled' => config('MAILING_ENABLED', false),

		/*
		|--------------------------------------------------------------------------
		| Mail Transport (SMTP)
		|--------------------------------------------------------------------------
		|
		| Defines the mailer to use for sending emails. Default is SMTP.
		| Other options like "sendmail", "mailgun", or "log" can also be set.
		|
		*/
		'smtp' => config('MAIL_MAILER', 'smtp'),

		/*
		|--------------------------------------------------------------------------
		| SMTP Host Address
		|--------------------------------------------------------------------------
		|
		| The address of your SMTP server. Common examples:
		| - smtp.mailgun.org
		| - smtp.gmail.com
		|
		*/
		'host' => config('MAIL_HOST', 'smtp.mailgun.org'),

		/*
		|--------------------------------------------------------------------------
		| SMTP Port
		|--------------------------------------------------------------------------
		|
		| The port used to connect to the SMTP server.
		| - 587 for TLS
		| - 465 for SSL
		|
		*/
		'port' => config('MAIL_PORT', '587'),

		/*
		|--------------------------------------------------------------------------
		| Email Encryption Protocol
		|--------------------------------------------------------------------------
		|
		| Encryption method for secure email transmission.
		| Common values: 'tls', 'ssl'
		|
		*/
		'encryption' => config('MAIL_ENCRYPTION', 'tls'),

		/*
		|--------------------------------------------------------------------------
		| SMTP Username & Password
		|--------------------------------------------------------------------------
		|
		| Authentication credentials for your SMTP server.
		|
		*/
		'username' => config('MAIL_USERNAME', ''),
		'password' => config('MAIL_PASSWORD', ''),

		/*
		|--------------------------------------------------------------------------
		| Global "From" Address
		|--------------------------------------------------------------------------
		|
		| Default sender address and name used in all outgoing emails.
		|
		*/
		'from' => config('MAIL_FROM_ADDRESS'),
		'from_name' => config('MAIL_FROM_NAME')
	]
];