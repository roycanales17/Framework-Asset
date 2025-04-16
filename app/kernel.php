<?php

	use App\Routes\Route;
	use App\Content\Blade;
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
		Stream::load('../views/');

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
		if (!in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS']) && request()->header('X-CSRF-TOKEN') !== Session::get('csrf_token')) {
			exit(response(['message' => 'Bad Request'], 400)->json());
		}

		// Configure cache
		Cache::configure('', '');

		// Route path
		$route = config('APP_ROOT')."/routes";

		// Configure web routes
		Route::configure($route, [
			'web.php'
		])->captured(function (string $content, int $code) {

			if ($code == 404)
				return;

			$template = file_exists($path = config('APP_PUBLIC') . '/index.html')
				? file_get_contents($path)
				: '';

			Blade::eval(Blade::compile($template ?: $content), [
				'g_page_lang' => config('APP_LANGUAGE'),
				'g_page_title' => config('APP_NAME'),
				'g_page_url' => config('APP_URL'),
				'g_page_description' => "Page description here",
				'g_page_content' => $content
			]);
		});

		// Configure API routes
		Route::configure($route, [
			'api.php'
		], 'api')->captured(function (string $content) {
			echo($content);
		});

	})->failed(function (Exception|Error $exception) {
		$logger = new Logger('../logs', logFile: 'error.log');
		$logger->error($exception->getMessage(), [
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTraceAsString()
		]);
	});
