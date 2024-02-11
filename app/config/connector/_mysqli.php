<?php
	
	namespace Illuminate\Database;
	use mysqli;
	
	trait _mysqli
	{
		protected static mysqli|null $resources = null;
		
		public static function resource( string $database ): mysqli
		{
			if ( self::$resources )
				return self::$resources;
			
			mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
			
			$res = new mysqli( config( 'DB_HOST' ), config( 'DB_USER' ), config( 'DB_PASSWORD' ), $database );
			$res->options( MYSQLI_OPT_CONNECT_TIMEOUT, 5 );
			$res->options( MYSQLI_OPT_READ_TIMEOUT, 10 );
			$res->options( MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1 );
			$res->options( MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, !config( 'DEVELOPMENT' ) );
			$res->options( MYSQLI_INIT_COMMAND, "SET NAMES 'utf8'");
			$res->options( MYSQLI_OPT_LOCAL_INFILE, false );
			
			return self::$resources = $res;
		}
		
		public static function perform( string $database, string $sql, string $action ): string|int|bool|array
		{
			$resources = self::resource( $database );
			$result = mysqli_query( $resources, $sql );
			
			if ( $action != 'execute' )
			{
				switch ( $action )
				{
					case 'count':
						return mysqli_affected_rows( $resources );
					
					case 'lastID':
						return mysqli_insert_id( $resources );
					
					default:
						$response = [];
						if ( mysqli_num_rows( $result ) )
						{
							while( $row = mysqli_fetch_assoc( $result ) )
								$response[] = $row;
						}
						return $response;
				}
			}
			
			return false;
		}
	}