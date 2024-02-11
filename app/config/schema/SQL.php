<?php
	
	namespace Illuminate\Database\Facades;
	
	class SQL {
		
		protected const default = [
			'open' => "",
			'columns' => [],
			'close' => " );"
		];
		
		protected static array $table = self::default;
		protected static array $primary = [];
		protected static array $unique = [];
		protected static array $index = [];
		
		static function start( string $table ): void {
			self::$table[ 'open' ] = "CREATE TABLE `$table` ( ";
		}
		
		static function add_column( string $sql ): void {
			self::$table[ 'columns' ][] = $sql;
		}
		
		static function add_extension( string $sql ): void {
			self::$table[ 'close' ] = " ) $sql;";
		}
		
		static function add_index( string $type, string $name ): void {
			array_push( self::$$type, $name );
		}
		
		static function compile(): string
		{
			$sql = self::$table[ 'open' ];
			$sql .= implode( ', ', self::$table[ 'columns' ] );
			
			$index_r = [ 'primary', 'unique', 'index' ];
			foreach ( $index_r as $type ) {
				if ( count( self::$$type ) ) {
					$sql .= ", ".strtoupper( $type )." KEY ( ".implode( ', ', self::$$type )." )";
				}
			}
			$sql .= self::$table[ 'close' ];
			
			self::$table = self::default;
			return $sql;
		}
	}