<?php

	use App\Routes\Route;

	Route::get('/', function () {
		return render('home', ['welcome' => 'Hello Robroy!']);
	});