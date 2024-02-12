<?php
	
	date_default_timezone_set( 'Asia/Manila' );
	define( 'START_TIME', microtime( true ) );
	
	use Core\{App, AppException, Blades};
	
    try
    {
		# Initialize Application
		$app = new App();
	
		# Global Variables
		$app->loadConstants( ROOT_DIRECTORY. '.env' );
	
		# Included Files
		$app->loadMajorFile( ROOT_DIRECTORY . 'vendor/autoload.php' );
		$app->loadMajorFile( ROOT_DIRECTORY. 'app/config/include.php' );
		
		# Default Apache Settings
		$app->loadInit( ROOT_DIRECTORY. 'app/config/init.php' );
		
		# Start the application
		if ( config( 'ARTISAN' ) )
		{
			# Handle the error accordingly in Artisan
			error_reporting( 0 );
			
			# Initialize Artisan
			$cmd = new \App\Terminal\Artisan();
			
			# Artisan Required Files
			$app->loadFiles( 'model', ROOT_DIRECTORY. 'app/model' );
			$app->loadFiles( 'controller', ROOT_DIRECTORY. 'app/controllers' );
			$app->loadFiles( 'rules', ROOT_DIRECTORY. 'app/requests' );
			$app->loadFiles( 'routes', ROOT_DIRECTORY. 'app/routes' );
			$app->loadFiles( 'migrate', ROOT_DIRECTORY. 'app/database/migration' );
			$app->loadFiles( 'seeds', ROOT_DIRECTORY. 'app/database/seeds' );
			
			# Additional Commands
			$cmd->register( "db", "migrate", "Run the SQL tables." );
			$cmd->register( "db", "seeds", "Generate data directly into database." );
			$cmd->register( "route", "list", "View all the route list." );
			$cmd->register( "make", "controller", "Generate new controller class." );
			$cmd->register( "make", "folders", "Generate default directories." );
			$cmd->register( "make", "model", "Generate new model class." );
			$cmd->register( "make", "request", "Generate new request class." );
			$cmd->register( "make", "route", "Generate new route file." );
			$cmd->register( "make", "auth", "Generate authentication layer." );
			$cmd->register( "make", "table", "Generate table file inside of migration directory." );
			$cmd->register( "make", "seeds", "Generate table file inside of seeds directory." );
			$cmd->register( "clear", "cache", "Remove all cache." );
			$cmd->register( "clear", "logs", "Remove all files from logs directory." );
			
			# Start the application through CLI
			$app->run();
			$cmd->run( $argv );
		}
		else
		{
			# Front-End Required Files
			$app->loadFiles( 'model', ROOT_DIRECTORY. 'app/model' );
			$app->loadFiles( 'controller', ROOT_DIRECTORY. 'app/controllers' );
			$app->loadFiles( 'rules', ROOT_DIRECTORY. 'app/requests' );
			$app->loadFiles( 'routes', ROOT_DIRECTORY. 'app/routes' );
			
			# This config below is not necessary
			$app->allowCache( config( "APP_CACHE" ) );
			$app->allowLogger( config( "APP_LOGGER" ) );
			$app->allowDebug( config( "APP_DEBUG" ) );
			
			# Run the test file
			if ( config( 'APP_TEST' ) )
			{
				$obj = new Blades();
				$obj->set_filepath( ROOT_DIRECTORY. 'public/test.php', false );
				echo( $obj->render() );
			}
			
			# Start the application
			else $app->run();
		}
    }
	catch ( AppException | Exception | Throwable | Error $e )
    {
		dump([
			'message'	=>	$e->getMessage(),
			'path'		=>	$e->getFile(),
			'line'		=>	$e->getLine(),
			'trace'		=>	$e->getTrace()
		]);
    }