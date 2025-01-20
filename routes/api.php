<?php

	use App\Routes\Route;

	Route::domain('localhost:82')
		->prefix('api')
		->group(function () {

		Route::get('/', function () {
			return response([
				'message' => 'Welcome to Framework API',
				'version' => '1.0.0',
			])->json();
		});
	});