<?php

	use App\Routes\Route;
	use App\Headers\Request;

	use App\Utilities\Stream;
	use App\Utilities\Application;
	use App\Utilities\Cache;
	use App\Utilities\Session;
	use App\Utilities\Logger;
	use App\Utilities\Mail;

	Application::run(function ($conf) {

		// Set the root directory for views
		Stream::load($conf['stream'] ?? '');

		// Start session
		Session::start();

		// Capture global request variables
		Request::capture();

		// Custom preload files
		foreach ($conf['preload_files'] ?? [] as $path) {
			if (file_exists($path)) {
				require_once $path;
			} else {
				$path = trim($path, '/');
				$path = config('APP_ROOT') . "/$path";
				if (file_exists($path))
					require_once $path;
			}
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
