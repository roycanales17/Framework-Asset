<?php

	use App\Routes\Route;

	Route::get('/', function () {
		return compile('home', ['welcome' => 'Hello Robroy!']);
	});