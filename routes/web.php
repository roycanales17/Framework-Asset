<?php

	use App\Routes\Route;

	Route::get('/', function () {
		return views('home', ['welcome' => 'Hello World!']);
	});