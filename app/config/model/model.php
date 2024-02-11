<?php
	
	namespace Illuminate\Database;
	use Core\App;
	use ReflectionClass;
	
	abstract class Model
	{
		private const PRIMARY_KEY = 'id';
		
		static function all(): array
		{
			$obj = new Eloquent();
			return $obj->select( '*' )->table( self::class_object()->table )->fetch();
		}
		
		static function paginate( int $per_page = 10, array $columns = ['*'], string $page_name = '' ): array
		{
			$class_name = self::class_object()->className;
			$class_table = self::class_object()->table;
			
			$obj = new Eloquent( $class_name );
			for ( $i = 0; $i < count( $columns ); $i++ ) {
				$obj->select( $columns[ $i ] );
			}
			$obj->table( $class_table );
			$obj->fragment( $class_name );
			
			return $obj->paginate( $per_page, $page_name );
		}
		
		static function create( array $binds ): int
		{
			// prepare
			self::remove_unfillable( $binds );
			
			// table columns only
			$columns = array_keys( $binds );
			
			// return last inserted ID
			return db::run( "INSERT INTO `".self::class_object()->table."` ( `". implode( '`, `', $columns ) ."` ) VALUES ( :". implode( ', :', $columns ) ." )", $binds )->lastID();
		}
		
		static function replace( array $binds ): int
		{
			// prepare
			self::remove_unfillable( $binds );
			
			// table columns only
			$columns = array_keys( $binds );
			
			// return last inserted ID
			return db::run( "REPLACE INTO `".self::class_object()->table."` ( `". implode( '`, `', $columns ) ."` ) VALUES ( :". implode( ', :', $columns ) ." )", $binds )->lastID();
		}
		
		static function find( int $id ): array
		{
			$obj = new Eloquent();
			$obj->select( "*" );
			$obj->table( self::class_object()->table );
			$obj->where( isset( self::class_object()->primaryKey ) ? self::class_object()->primaryKey : self::PRIMARY_KEY, $id );
			
			return $obj->row();
		}
		
		static function select(): Eloquent
		{
			$args = func_get_args();
			$obj = new Eloquent();
			
			for ( $i = 0; $i < count( $args ); $i++ ) {
				$obj->select( $args[ $i ] );
			}
			
			return $obj->table( self::class_object()->table );
		}
		
		static function where( string $column, mixed $operator_or_value, mixed $value = 'default-null' ): Eloquent
		{
			$obj = new Eloquent();
			$obj->table( self::class_object()->table );
			$obj->where( $column, $operator_or_value, $value );
			
			return $obj;
		}
		
		private static function remove_unfillable( array &$array ): void
		{
			foreach ( $array as $key => $value ) {
				
				if ( !is_null( self::class_object()->fillable ) )
				{
					if ( !in_array( $key, self::class_object()->fillable ) ) {
						unset( $array[ $key ] );
					}
				}
			}
		}
		
		private static function class_object(): object
		{
			$childClassName = get_called_class();
			$reflectionClass = new ReflectionClass( $childClassName );
			
			$instance = $reflectionClass->newInstanceWithoutConstructor();
			$instance->className = call_user_func( function() use ( $reflectionClass )
			{
				$class = $reflectionClass->getName();
				$class = explode( '\\', $class );
				return $class[ count( $class ) - 1 ];
			});
			
			return $instance;
		}
	}
	