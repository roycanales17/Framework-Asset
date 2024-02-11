<?php
	
	/*********************************************
	 *		 DO NOT ADD ANOTHER FILE HERE		 *
	 *********************************************/
	
	foreach ([
	  ROOT_DIRECTORY . 'app/config/exception.php',
	  ROOT_DIRECTORY . 'app/config/app.php',
	  ROOT_DIRECTORY . 'app/kernel.php'
 	] as $path ) require_once $path;