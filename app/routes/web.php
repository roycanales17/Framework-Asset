<?php
	
	use Illuminate\Http\Route;
	
	Route::get( '', function() {
		view( "main" );
	});
	
	