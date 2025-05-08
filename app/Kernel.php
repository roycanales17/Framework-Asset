<?php

	use App\Routes\Route;
	use App\Utilities\Application;
	use App\Utilities\Cache;
	use App\Utilities\Logger;
	use App\Utilities\Mail;

	Application::run(function ($conf) {

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

			Mail::configure($mail['host'], $mail['port'], $credentials);
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
