<?php

	use App\Routes\Route;
	use App\Headers\Request;

	use App\Utilities\Stream;
	use App\Utilities\Config;
	use App\Utilities\Application;
	use App\Utilities\Cache;
	use App\Utilities\Session;
	use App\Utilities\Logger;
	use App\Utilities\Server;
	use App\Utilities\Mail;

	Application::run(function () {

		// Load environment variables
		Config::load('../.env');

		// Set the root directory for views
		Stream::load(($conf = require 'Config.php')['stream'] ?? '');

		// Start session
		Session::start();

		// Capture global request variables
		Request::capture();

		// Define global constants
		define('APP_HOST', Server::HostName());
		define('APP_SCHEME', Server::IsSecureConnection() ? "https" : "http");
		define('APP_URI_PARAMS', Server::RequestURI());
		define('APP_ROOT', dirname(__DIR__));
		define('APP_PUBLIC', config('APP_ROOT') . "/public");
		define('APP_URL', config('APP_SCHEME') . "://" . config('APP_HOST'));
		define('APP_FULL_URL', config('APP_URL') . config('APP_URI_PARAMS'));
		define('DEVELOPMENT', in_array(config('APP_ENV'), ['development', 'production', 'local', 'staging']));
		define('CSRF_TOKEN', csrf_token());

		// Custom global variables
		foreach ($conf['defines'] as $key => $value) {
			if (!defined($key))
				define($key, $value);
		}

		// Validate CSRF Token
		validate_token();

		// Configure cache
		if ($cache = $conf['cache']['driver'] ?? '') {
			$cache_attr = $conf['cache'][$cache];
			Cache::configure($cache_attr['driver'], $cache_attr['server'], $cache_attr['port']);
		}

		// Configure mail
		$mail = $conf['mailing'] ?? [];
		if (!empty($mail['enabled'])) {
			$credentials = [];

			if (!empty($mail['username']) && !empty($mail['password'])) {
				$credentials = [
					'username' => $mail['username'],
					'password' => $mail['password'],
				];
			}

			Mail::config($mail['host'], $mail['port'], $credentials);
		}

		// Configure Routes
		foreach ($conf['routes'] ?? [] as $route) {
			Route::configure(
				$route['root'] ?? "../routes",
				$route['routes'] ?? ['web.php'],
				$route['prefix'] ?? ''
			)->captured($route['captured']);
		}

	})->failed(function (Exception|Error $exception) {
		$logger = new Logger('../logs', logFile: 'error.log');
		$logger->error($exception->getMessage(), [
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTraceAsString()
		]);
	});
