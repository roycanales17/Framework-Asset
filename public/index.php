<?php

	# By Default Errors is hidden
	error_reporting(0);
	ini_set('display_errors', 0);

	# Class Importer
	spl_autoload_register(fn($class) => file_exists($path = '../' . str_replace('\\', '/', $class) . '.php') && require_once $path);

	# Built-In Functions
	require_once '../vendor/autoload.php';
	require_once '../app/Kernel.php';