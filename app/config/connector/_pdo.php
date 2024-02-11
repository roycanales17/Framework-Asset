<?php
	
	namespace Illuminate\Database;
	use PDO;
	
	trait _pdo
	{
		protected static PDO|null $resources = null;
		protected static array $binds = [];
		
		public static function resource( string $database ): PDO
		{
			if ( self::$resources )
				return self::$resources;
			
			$res = new pdo( 'mysql:host='.config( 'DB_HOST' ).';dbname='.$database.';charset=UTF8', config( 'DB_USER' ), config( 'DB_PASSWORD' ) );
			$res->setAttribute( pdo::ATTR_PERSISTENT, true );
			$res->setAttribute( pdo::ATTR_EMULATE_PREPARES, false );
			$res->setAttribute( pdo::ATTR_CURSOR, pdo::CURSOR_FWDONLY );
			$res->setAttribute( pdo::ATTR_ERRMODE, pdo::ERRMODE_EXCEPTION );
			$res->setAttribute( pdo::ATTR_DEFAULT_FETCH_MODE, pdo::FETCH_ASSOC );
			$res->setAttribute( pdo::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8" );
			
			return self::$resources = $res;
		}
		
		public static function perform( string $database, string $sql, string $action ): string|int|bool|array
		{
			$res  =	_pdo::resource( $database );
			$stmt =	$res->prepare( $sql );
			
			foreach ( self::$binds as $key => $attr )
			{
				if ( ( $data_type = self::type( $attr[ 'value' ] ) ) === null ) {
					$stmt->bindValue( ":$key", $attr[ 'value' ] );
				}
				else {
					$stmt->bindValue( ":$key", $attr[ 'value' ], $data_type );
				}
			}
			
			$stmt->execute();
			self::reset();
			
			if ( $action != 'execute' )
			{
				return match ( $action ) {
					'count' 	=> 	$stmt->rowCount(),
					'lastID' 	=> 	$res->lastInsertID(),
					default 	=> 	$stmt->fetchAll(),
				};
			}
			return false;
		}
		
		public static function type( $value ): mixed {
			
			$type = null;
			switch ( $value )
			{
				case is_string( $value ):
					$type = PDO::PARAM_STR;
					break;
				
				case is_bool( $value ):
				case is_int( $value ):
					$type = PDO::PARAM_INT;
					break;
				
				case is_null( $value ):
					$type = PDO::PARAM_NULL;
					break;
			}
			
			return $type;
		}
		
		public static function register( string $key, mixed $value, string $type ): void
		{
			self::$binds[ $key ] = [
				'value'	=>	$value,
				'type'	=>	$type
			];
		}
		
		public static function count(): int {
			return count( self::$binds );
		}
		
		public static function reset(): void {
			self::$binds = [];
		}
	}