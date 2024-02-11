<?php
	
	namespace App\Terminal\Route;
	
	use Core\Session;
	use Illuminate\Http\Router;
	use jc21\CliTable;
	
	class routeList {
		
		function __construct( $artisan, $method )
		{
			$routes = Session::get( '$_routes-list' );
			
			if ( $routes )
			{
				$data = [];
				foreach ( $routes as $attr )
				{
					$temp = [];
					$valid_http = Router::getValidHttps();
					
					if ( strtoupper( $attr[ 'method' ] ) === 'MATCH' )
					{
						$found = false;
						foreach ( $attr[ 'methods' ] as $meth )
						{
							if ( in_array( strtoupper( $meth ), $valid_http ) ) {
								$found = true;
								break;
							}
						}
						
						if ( $found )
						{
							if ( $method )
								$condition = in_array( strtoupper( $method ), array_map( 'strtoupper', $attr[ 'methods' ] ) );
							else
								$condition = true;
						}
						else
							$condition = false;
					}
					else
					{
						if ( $method )
							$condition = strtoupper( $attr[ 'method' ] ) === strtoupper( $method );
						else
							$condition = in_array( strtoupper( $attr[ 'method' ] ), $valid_http );
					}
					
					if ( $condition )
					{
						$temp[ 'method' ] = ( strtoupper( $attr[ 'method' ] ) == 'MATCH' ? "$attr[method](".implode( ',', $attr[ 'methods' ] ).")" : $attr[ 'method' ] );
						$temp[ 'uri' ] = $attr[ 'uri' ];
						$temp[ 'prefix' ] = $attr[ 'prefix' ];
						
						if ( $attr[ 'action' ] )
						{
							switch ( true )
							{
								case is_array( $attr[ 'action' ] ):
									$temp[ 'action' ] = $attr['action'][0]."@".$attr['action'][1];
									break;
								
								case is_string( $attr[ 'action' ] ):
									$temp[ 'action' ] = "$attr[controller]@$attr[action]";
									break;
									
								case is_object( $attr[ 'action' ] ):
									$temp[ 'action' ] = "Closure";
									break;
							}
						}
						else $temp[ 'action' ] = "N/A";
						
						$middleware = [];
						if ( $attr[ 'middleware' ] )
						{
							foreach ( $attr[ 'middleware' ] as $middleware_attr )
							{
								if ( is_array( $middleware_attr ) )
									$middleware[] = "$middleware_attr[0]@$middleware_attr[1]";
								else
									$middleware[] = "$attr[controller]@$middleware_attr";
							}
							
						}
						$temp[ 'middleware' ] = implode( ', ', $middleware );
						
						$where = [];
						foreach ( $attr[ 'where' ] as $name => $pattern )
							$where[] = "$name($pattern)";
						$temp[ 'where' ] = implode( ', ', $where );
						
						
						$position = strpos( $attr[ 'trace' ][ 'file' ], "/app/routes/");
						if ( $position !== false )
							$temp[ 'path' ] = substr( $attr[ 'trace' ][ 'file' ], $position );
						else
							$temp[ 'path' ] = $attr[ 'trace' ][ 'file' ];
						
						$temp[ 'line' ] = $attr[ 'trace' ][ 'line' ];
						
						$data[] = $temp;
					}
				}
				
				echo "\n";
				$table = new CliTable;
				$table->setTableColor('blue');
				$table->setHeaderColor('cyan');
				$table->addField('METHOD', 'method',    false, 'green');
				$table->addField('PREFIX',  'prefix',     false, 'gray');
				$table->addField('URI',  'uri',     false, 'gray');
				$table->addField('ACTION',  'action',     false, 'yellow');
				$table->addField('MIDDLEWARE',  'middleware',     false, 'red');
				$table->addField('WHERE',  'where',     false, 'blue');
				$table->addField('PATH',  'path',     false, 'green');
				$table->addField('LINE',  'line',     false, 'green');
				$table->injectData( $data );
				$table->display();
				echo "\n";
			}
			else
			{
				$artisan->title( 'ERROR', 31 );
				$artisan->info( 'No routes found' );
				$artisan->print([
					[ "command", "View the list of commands." ],
					[ "help", "Not available for the meantime." ],
					[ "exit", "Terminate the session." ]
				]);
			}
		}
	}