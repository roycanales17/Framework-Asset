<?php
	
	namespace Core;
	
	use DirectoryIterator;
	use Illuminate\Http\Request;
	use Illuminate\Http\Route;
	use JetBrains\PhpStorm\NoReturn;
	
	class Helper extends Facades {
		
		public static function error( $message = "" ) {
			return self::exit( $message, __FUNCTION__ );
		}
		
		public static function success( $message = "" ) {
			return self::exit( $message, __FUNCTION__ );
		}
		
		public static function warning( $message = "" ) {
			return self::exit( $message, __FUNCTION__ );
		}
	}
	
	class Facades
	{
		protected array $temp_files = [];
		
		protected function getFiles( string $directory ): array
		{
			foreach ( new DirectoryIterator( $directory ) as $item )
			{
				if ( !$item->isDot() )
				{
					if ( $item->isDir() )
						$this->getFiles( "$directory/$item" );
					
					else
					{
						if ( $this->checkIfValid( "$directory/$item" , $item->getFilename() ) )
							$this->temp_files[] = "$directory/$item";
					}
				}
			}
			
			return $this->temp_files;
		}
		
		protected function checkIfValid( string $file , string $class ): bool
		{
			$_file = pathinfo( $file );
			if ( $_file[ 'extension' ] == 'php' )
			{
				foreach ( $this->temp_files as $value )
				{
					$filename = explode( '/' , $value );
					$filename = $filename[ sizeof( $filename ) - 1 ];
					
					if ( $filename === $class )
						app::error( "$class model is duplicated, please check from model directories." );
				}
			}
			else app::error( "Invalid PHP file given path ($file)." );
			
			return true;
		}
		
		/**
		 * @throws AppException
		 */
		protected static function exit( $message, $type ) {
			throw new AppException( $message, $type );
		}
	}
	
	class Config {
		
		public static function get( $name ): string | bool
		{
			if ( self::exist( $name ) )
			{
				$value = constant( $name );
				switch ( true )
				{
					case in_array( strtolower( $value ), [ 'true', 'false' ] ):
						switch ( $value )
						{
							case 'true':
								$value = true;
								break;
							case 'false':
								$value = false;
								break;
						}
						break;
					case is_numeric( $value ):
						$value = intval( $value );
						break;
				}
				
				return $value;
			}
			else app::error( "Defined constant is undefined, given name ($name)" );
			
			return false;
		}
		
		public static function exist( $name ): bool
		{
			if ( empty( $name ) )
				return false;
			
			return defined( $name );
		}
		
		public static function define( $name, $value )
		{
			if ( !self::exist( $name ) )
				define( $name, $value );
			else
				app::error( "Defined constant is already exist, given name ($name, $value)" );
			
			return $value;
		}
	}
	
	class App extends Helper
	{
		private array $configuration = [];
		
		public function loadConstants( string $path ): array
		{
			$array = [];
			if ( file_exists( $path ) )
			{
				$this->configuration[ 'env' ][ 'path' ] = $path;
				$envLines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
				
				foreach ( $envLines as $line )
				{
					list( $key, $value ) = explode( '=', $line, 2 );
					if ( $key !== null )
					{
						$key = trim( $key );
						$value = isset( $value ) ? trim( $value ) : '';
						
						define( $key, $value );
						$this->configuration[ 'env' ][ 'constants' ][] = [
							'key'   => $key,
							'value' => $value
						];
					}
				}
				
			}
			else self::error( "ENV file is not exist, given directory ($path)." );
			
			return $array;
		}
		
		public function loadMajorFile( string $path ): array
		{
			$array = [];
			if ( file_exists( $path ) )
			{
				$this->configuration[ 'files' ][ 'path' ] = $path;
				foreach ( ( require $path ) as $name => $directory )
				{
					$this->configuration[ 'files' ][ 'directories' ][] = $array[] = [
						'name'	=>	$name,
						'path'	=>	$directory
					];
					include_once $directory;
				}
			}
			else app::error( "Cannot find the file given directory ($path)" );
			
			return $array;
		}
		
		public function loadInit( string $path ): array {
			
			$array = [];
			if ( file_exists( $path ) )
			{
				$this->configuration[ 'init' ][ 'path' ] = $path;
				
				if ( !Config::exist( "DEVELOPMENT" ) )
					Config::define( "DEVELOPMENT", ( config( "APP_ENV" ) === 'local' ? "1" : "0" ) );
				
				$status = config( "DEVELOPMENT" );
				
				ini_set( 'display_errors', $status );
				ini_set( 'display_exits' , $status );
				ini_set( 'display_startup_errors', $status );
				ini_set( 'display_startup_exits' , $status );
				error_reporting( $status ? E_ALL & ~E_DEPRECATED & ~E_STRICT : 0 );
				
				$init = $array = $this->configuration[ 'init' ][ 'config' ] = include $path;
				foreach ( $init as $name => $value )
				{
					switch ( $name )
					{
						case 'xdebug_config':
							foreach ( $value as $debugkey => $debugvalue ) {
								ini_set( $debugkey , $debugvalue );
							}
							break;
						
						case 'session_config':
							foreach ( $value as $session_key => $session_value )
							{
								if ( $session_key == 'session_name' )
								{
									if ( !empty( $session_value ) )
										session_name( $session_value );
								}
								elseif ( $session_key == 'session_regenerate_id' )
								{
									if ( $session_value )
										session_regenerate_id( true );
								}
								elseif ( $session_key == 'save_path' )
								{
									if ( !config( 'ARTISAN' ) )
									{
										if ( !file_exists( $session_value ) )
											mkdir( $session_value );
										
										ini_set( 'session.'.$session_key , $session_value );
									}
								}
								else
									if ( is_string( $session_value ) )
										ini_set( 'session.'.$session_key , $session_value );
							}
							break;
							
						case 'session_set_cookie_params':
							session_set_cookie_params( $value );
							break;
							
						default:
							ini_set( $name, $status );
							break;
					}
				}
			}
			else self::error( "Init file is not exist, given directory ($path)." );
			
			return $array;
		}
		
		public function loadFiles( string $name, string $path ): array
		{
			$array = $this->getFiles( $path );
			$this->temp_files = [];
			
			$this->configuration[ 'blueprints' ][] = $name;
			$this->configuration[ $name ][ 'path' ] = $path;
			return $this->configuration[ $name ][ 'class' ] = $array;
		}
		
		public function allowDebug( bool|int|string $status ): bool
		{
			if ( is_string( $status ) && in_array( $status, [ 'true', 'false' ] ) )
			{
				switch ( $status )
				{
					case "true":
						$status = true;
						break;
						
					case "false":
						$status = false;
						break;
				}
			}
			else $status = (bool) $status;
			
			$this->configuration[ 'debug' ] = $status;
			return $status;
		}
		
		public function allowCache( bool $status ): bool
		{
			$this->configuration[ 'cache' ] = $status;
			return $status;
		}
		
		public function allowLogger( bool $status ): bool
		{
			$this->configuration[ 'logger' ] = $status;
			return $status;
		}
		
		protected function startApplication()
		{
			trace( "Starting the application...", 3 );
			
			foreach ( $this->configuration[ 'blueprints' ] as $mvc ) {
				foreach ( $this->configuration[ $mvc ][ 'class' ] as $directory )
					require_once $directory;
			}
		}
		
		protected function generateToken( int $length = 32 ): void
		{
			if ( Session::has( '$_csrf-token' ) )
				Session::put( '$_csrf-token-recent', Session::get( '$_csrf-token' ) );
			else
				Session::put( '$_csrf-token-recent', null );
			
			Session::put( '$_csrf-token', generateRandomString( $length ) );
		}
		
		public function run()
		{
			if ( !config( 'ARTISAN' ) )
			{
				# Handle the session
				session_set_save_handler( new Handler );
				
				# Closing Session
				register_shutdown_function( 'Core\Handler::session_close' );
			}
			
			# Start the session
			Session::start();
			
			# Generate token
			$this->generateToken( 100 );
			
			# Start the application
			$this->startApplication();
			
			# Throw 404 page
			if ( !config( 'ARTISAN' ) )
			{
				Route::fallback( function () {
					trace( "No Route Found!", 3 )->store( $this->configuration );
					return view( '404' );
				});
			}
		}
		
		#[NoReturn] function __destruct()
		{
			if ( !config( 'ARTISAN' ) )
			{
				if ( config( "APP_DEBUG" ) )
				{
					trace( "Session variables." )->store( Session::all() );
					
					// Display the debug
					exitTrace();
				}
				
				// End line
				Request::exit();
			}
		}
	}