<?php
	
	namespace Illuminate\Http;
	
	use Core\{App, Blades, Session, trace, Config};
	
	class Dispatch
	{
		public array
			$params = [],
			$where = [],
			$middleware = [],
			$trace = [];
		
		public string
			$method,
			$name,
			$uri,
			$controller,
			$view,
			$prefix;
		
		public int $code = 302;
		public object|array $address;
		public mixed $actions, $data, $methods;
		
		function __construct( array $config )
		{
			$this->uri = $config[ 'uri' ] ?? '';
			$this->controller = $config[ 'controller' ];
			$this->code = $config[ 'code' ];
			$this->method = $config[ 'method' ];
			$this->params = $config[ 'params' ];
			$this->actions = $config[ 'action' ];
			$this->view = $config[ 'view' ];
			$this->data = $config[ 'data' ];
			$this->methods = $config[ 'methods' ];
			$this->prefix = $config[ 'prefix' ];
			$this->trace = $config[ 'trace' ];
			$this->address = Request::url();
		}
		
		public function start(): void
		{
			if ( config( 'ARTISAN' ) )
			{
				Session::insert( '$_routes-list', [
					'method'		=>	$this->method,
					'uri'			=>	$this->uri,
					'action'		=>	$this->actions,
					'where'			=>	$this->where,
					'params'		=>	$this->params,
					'middleware'	=>	$this->middleware,
					'controller'	=>	$this->controller,
					'methods'		=>	$this->methods,
					'prefix'		=>	$this->prefix,
					'trace'			=>	$this->trace
				]);
			}
			
			switch ( true )
			{
				case !$this->checkMiddleware():
					Route::fallback( function ()
					{
						trace( "Middleware condition failed given method ($this->method)." )->store([
							'method'		=>	$this->method,
							'uri'			=>	$this->uri,
							'action'		=>	$this->actions,
							'where'			=>	$this->where,
							'params'		=>	$this->params,
							'middleware'	=>	$this->middleware,
							'controller'	=>	$this->controller,
							'trace'			=>	$this->trace
						]);
						
						return view( '401' );
					});
					break;
				
				case !$this->checkWhereCondition():
					Route::fallback( function ()
					{
						trace( "Where condition failed given method ($this->method)." )->store([
							'method'		=>	$this->method,
							'uri'			=>	$this->uri,
							'action'		=>	$this->actions,
							'where'			=>	$this->where,
							'params'		=>	$this->params,
							'middleware'	=>	$this->middleware,
							'controller'	=>	$this->controller,
							'trace'			=>	$this->trace
						]);
						
						return view( '401' );
					});
					break;
				
				default:
					
					if ( !config( 'ARTISAN' ) )
					{
						ob_start();

						switch ( $this->method )
						{
							case 'view':

								$obj = new Blades();
								$obj->set_data( $this->data );
								$obj->set_filepath( $this->view );
								
								echo( $obj->render() );
								$this->save_recent_page();

								break;
							
							case 'redirect':
								
								ob_start();
								trace( 'Recent Session' )->store( Session::all() );
								Session::put( 'recent_traces', trace::tracks( 'trace' ) );
								header( "Location: {$this->address->protocol}://{$this->address->host}{$this->actions}", true, $this->code );
								
								break;
							
							default:
								
								$GLOBALS[ 'URI_PARAMS' ] = $this->params;
								switch ( true )
								{
									case is_object( $this->actions ):
										closureMethod( $this->actions );
										break;
									
									case is_array( $this->actions ):
										classMethod( $this->actions[0], $this->actions[1] );
										break;
									
									case is_string( $this->actions ):
										classMethod( $this->controller, $this->actions );
										break;
								}
								
								break;
						}

						$content = ob_get_contents();
						ob_end_clean();

						if ( $content )
						{
							if ( !Request::is_json( $content ) )
								print( "<link rel='stylesheet' href='".config( "APP_URL" )."/resources/".config( "STYLE" )."'>" );
							else 
								Config::define( "APP_DEBUG_DISABLE", true );

							echo( $content );
						}
						
						Session::put( '$_GLOBAL_INPUTS', array_change_key_case( $_GET + $_POST ) );
						Request::exit();
					}
			}
		}
		
		private function checkWhereCondition(): bool
		{
			if ( !config( 'ARTISAN' ) )
			{
				foreach ( $this->where as $param => $regex )
				{
					if ( isset( $this->params[ $param ] ) )
					{
						if ( !preg_match( "/$regex/i", $this->params[ $param ] ) ) {
							return false;
						}
					}
				}
			}
			
			return true;
		}
		
		private function checkMiddleware(): bool
		{
			if ( !config( 'ARTISAN' ) )
			{
				$result = true;
				foreach ( $this->middleware as $index => $actions )
				{
					switch ( true )
					{
						case is_string( $actions ):
							if ( $this->checkController() )
							{
								if ( !classMethod( $this->controller, $actions ) ) {
									$this->middleware[ $index ] = false;
									$result = false;
								}
								else
									$this->middleware[ $index ] = true;
							}
							else app::error( "No class name found for method ($actions) middleware validation." );
							
							break;
						
						case is_array( $actions ):
							if ( count( $actions ) == 2 )
							{
								if ( !classMethod( $actions[0], $actions[1] ) ) {
									$result = false;
									$this->middleware[ $index ][] = false;
								}
								else
									$this->middleware[ $index ][] = true;
							}
							else app::error( "Invalid middleware args applied." );
							break;
					}
				}
				
				return $result;
			}
			
			return true;
		}
		
		private function save_recent_page(): void
		{
			if ( $url = $this->address )
			{
				if ( !Session::has( '$_recent_page' ) )
					Session::put( '$_recent_page', $url->address );
				else
					Session::put( '$_recent_page', Session::get( '$_current_page' ) );
				
				Session::put( '$_current_page', $url->address );
			}
			
			// Remove input keys
			Session::forget( '$_GLOBAL_INPUTS' );
		}
		
		private function checkController(): bool {
			return (bool) $this->controller;
		}
	}