<?php

	use App\Routes\Route;

	Route::get('/', function () {
		return render('home', ['welcome' => 'Hello Robroy!']);
	});

	Route::get('/about', function () {
		return "This is about page";
	});