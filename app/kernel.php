<?php

	use App\Routes\Route;
	use App\Headers\Request;
	use App\Utilities\Cache;
	use App\Utilities\Session;
	use App\Utilities\Storage;

	try {

		# Session Start
		Session::start();

		# Capture the global variables
		Request::capture();

		# Set up the cache server
		Cache::configure('', '', function ($response) {

		});

		# Default storage directory
		Storage::configure('storage/app');

		# Start application
		Route::configure(dirname(__DIR__), [
			'routes/api.php',
			'routes/web.php'
		])->captured(function(string $content) {

			# Display the content
			echo($content);
		});

	} catch (Exception|Error $e) {
		echo $e->getMessage();
	}