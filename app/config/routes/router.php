<?php
	
	namespace Illuminate\Http;
	
	use Core\Session;
	use ReflectionClass;
	use ReflectionProperty;
	
	class Router extends Actions
	{
		/**
		 * This contains the path info of the route.
		 *
		 * @var array
		 */
		private array $trace_r = [];
		
		/**
		 * These are the instance that doesn't require URI.
		 *
		 * @var array|string[]
		 */
		public array $no_URI = [
			'domain',
			'name',
			'prefix',
			'controller',
			'middleware',
			'fallback'
		];
		
		/**
		 * These are the available method for the grouping routes.
		 *
		 * @var array|string[]
		 */
		public array $compiler2 = [
			'controller',
			'middleware',
			'fallback',
			'redirect',
			'prefix'
		];
		
		/**
		 * Valid HTTP Code that will be accepted from client request.
		 *
		 * @var array|string[]
		 */
		public array $valid_http = [
			'GET',
			'POST',
			'PUT',
			'PATCH',
			'DELETE',
			'HEAD',
			'OPTIONS',
			'MATCH'
		];
		
		/**
		 * Excluded from the prefix.
		 *
		 * @var array|string[]
		 */
		public array $excluded_prefix = [
			'redirect',
			'view'
		];
		
		public array $blacklisted_domain = [];
		
		private function prepare(): void
		{
			$this->uri = $this->registerURI();
			$this->headers = $this->registerHeaders();
			$this->methods = $this->registerMethods();
			$this->action = $this->registerActions();
			$this->code = $this->registerCode();
			$this->view = $this->registerView();
			$this->data = $this->registerData();
			
			$this->referrer_domain = $this->getReferrerDomain();
			$this->referrer_method = $this->getHTTP();
		}
		
		private function run(): object
		{
			if ( config( 'ARTISAN' ) )
				$dispatch = true;
			
			else
			{
				switch ( true )
				{
					case $this->checkBlacklistedDomain( $this->referrer_domain ): stop();
					
					case !$this->checkMethodURI():
					case !$this->checkMethodServer():
						$dispatch = false;
						break;
					
					default:
						$dispatch = true;
						break;
				}
			}
			
			$object = $this->createInstance( $this->validateMethodInstance( $dispatch ) );
			switch ( $this->instance )
			{
				case 'prefix':
					self::register( 'prefix', $this->action );
					break;
					
				case 'middleware':
					self::register( 'middleware', $this->action );
					break;
					
				case 'controller':
					if ( !self::status( 'controller' ) ) {
						self::register( 'controller', $this->action );
					}
					break;
					
				default:
					if ( self::status( 'middleware' ) )
					{
						$actions = self::fetch( 'middleware' );
						for ( $i = 0; $i < count( $actions ); $i++ ) {
							$object->middleware( $actions[ $i ] );
						}
					}
					break;
			}
			
			if ( $dispatch && !config( 'ARTISAN' ) )
			{
				trace( "Executing route given method ({$this->instance})..." )->store([
					'method'	=>	$this->methods,
					'action'	=>	$this->action,
					'url'		=> 	$this->serializeURI( $this->uri ),
					'view'		=>	$this->view
				]);
				
				if ( config( "APP_LOGGER" ) && in_array( strtoupper( $this->methods ), $this->valid_http ) )
					$this->trackClient( Request::ip_address() );
			}
			
			return $object;
		}
		
		private function trackClient( string $ip_address ): void
		{
			$directory 	= 	config( 'LOGS' )."/client/";
			$location 	= 	$directory.date( 'Y-m-d', strtotime( "now" ) );
			$path 		= 	"$location/".str_replace( '.', '_', $ip_address ).".json";
			
			if ( !file_exists( $directory ) )
				mkdir( $directory, 0755, true );
			
			if ( !file_exists( $location ) )
				mkdir( $location, 0755, true );
			
			$content = file_get_contents( $path );
			$data = json_decode( $content, true );
			$index = $data ? count( $data ) : 0;
			
			$attributes = [
				'index'			=>	$index,
				'uri'			=> 	$this->serializeURI( $this->uri ),
				'args'			=>	$this->args,
				'method'		=>	$this->methods,
				'action'		=>	$this->action,
				'redirected'	=>	Session::has( 'recent_traces' ),
				'variables'		=>	[
					'POST'		=>	$_POST,
					'GET'		=>	$_GET,
					'PARAMS'	=>	$this->params,
					'FILE'		=>	$_FILES
				],
				'headers'		=>	getallheaders(),
				'time_created' 	=> 	date( 'H:i:s', strtotime( "now" ) )
			];
			
			$update = true;
			if ( $data )
			{
				usort( $data, function ( $a, $b ) {
					return $a[ 'index' ] - $b[ 'index' ];
				});
				
				$remove_keys = function ( $array, $key ) {
					return array_diff_key( $array, array_flip( $key ) );
				};
				
				$keysToRemove = [ "index", "time_created" ];
				$temp1 = $remove_keys( $data[ $index - 1 ], $keysToRemove );
				$temp2 = $remove_keys( $attributes, $keysToRemove );
				
				if ( $temp1 == $temp2 )
					$update = false;
			}
			
			if ( $update )
			{
				$data[] = $attributes;
				
				usort( $data, function ( $a, $b ) { return $b[ 'index' ] - $a[ 'index' ]; });
				file_put_contents( $path, json_encode( $data, JSON_PRETTY_PRINT ), LOCK_EX );
				
				chmod( $path, 0666 );
			}
		}
		
		private function createInstance( $dispatch ): object
		{
			$config = [];
			$reflection = new ReflectionClass( $this );
			$properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED );
			
			foreach ( $properties as $property )
			{
				$property_name = $property->getName();
				switch ( $property_name )
				{
					case 'instance':
						$config[ 'method' ] = $this->$property_name;
						break;
						
					default:
						$config[ $property_name ] = $this->$property_name;
						break;
				}
			}
			
			$config[ 'methods' ] = $this->methods;
			$config[ 'controller' ] = self::fetch( 'controller' );
			$config[ 'trace' ] = $this->trace_r;
			$config[ 'prefix' ] = self::status( 'prefix' ) ? self::fetch( 'prefix' ) : "";
			$object = str_replace( [' ', "\t"], '', strtolower( ( "Illuminate\\Http\\".( in_array( $this->instance, $this->compiler2 ) ? self::COMPILER.'2' : self::COMPILER ) ) ) );
			
			return new $object( $dispatch, $config );
		}
		
		public function registerTrace( array $trace ): void {
			$this->trace_r = $trace;
		}
		
		public function __call( $name, $arguments )
		{
			$this->instance = $name;
			$this->args = $arguments;
			
			$this->prepare();
			return $this->run();
		}
		
		public static function getValidHttps(): array {
			return ( new self )->valid_http;
		}
		
		const COMPILER = 'C O M P I L E R';
	}