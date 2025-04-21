<?php

	# Class Importer
	spl_autoload_register(fn($class) => file_exists($path = '../' . str_replace('\\', '/', $class) . '.php') && require_once $path);

	# Built-In Functions
	require_once '../vendor/autoload.php';
	require_once '../app/Kernel.php';