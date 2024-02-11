<?php
	
	namespace Illuminate\Database\Facades;
	use Illuminate\Database\db;
	
	class Schema
	{
		static function dropIfExists( string $table ): void
		{
			$sql = "
				IF EXISTS ( SELECT * FROM information_schema.tables WHERE table_schema = '".esc( config( 'DB_NAME' ) )."' AND table_name = '".esc( $table )."' )
				THEN
					DROP TABLE ".esc( $table ).";
				END IF;";
			
			db::run( $sql );
		}
		
		static function hasTable( string $table ): bool {
			return db::run( "SHOW TABLES LIKE '".esc( $table )."'" )->count();
		}
		
		static function create( string $table, \Closure $callback ): void
		{
			# Initialize
			$instance = new Blueprint( $table );
			$callback( $instance );
			
			# Run SQL
			db::run( SQL::compile() );
		}
	}