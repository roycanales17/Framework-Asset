<?php

	use App\Routes\Route;
	use App\Utilities\Stream;

	Route::get('/', function () {
		return response([
			'message' => 'Welcome to Framework API',
			'version' => '1.0.0',
		])->json();
	});

	Route::post('/stream-wire/{identifier}', [Stream::class, 'capture']);