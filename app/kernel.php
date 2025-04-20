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

	Application::run(function () {

		// Load environment variables
		Config::load('../.env');

		// Set the root directory for views
		Stream::load(($conf = require 'Config.php')['stream']);

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

		// Validate CSRF Token
		if (!in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS']) && request()->header('X-CSRF-TOKEN') !== Session::get('csrf_token'))
			exit(response(['message' => 'Bad Request'], 400)->json());

		// Configure cache
		if (($conf['cache'] ?? false) && ($conf['cache']['enabled'] ?? false))
			Cache::configure($conf['cache']['server'], $conf['cache']['port']);

		// Configure Routes
		foreach ($conf['routes'] ?? [] as $key => $route) {
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
