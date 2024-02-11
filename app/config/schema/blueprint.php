<?php
	
	namespace Illuminate\Database\Facades;
	
	class Blueprint {
		
		function __construct( string $table ) {
			SQL::start( $table );
		}
		
		public function tinyInt( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function smallInt( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function mediumInt( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function bigInt( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function int( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function decimal( string $col, int $length = 65 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function float( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function double( string $col, int $length = 11 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function string( string $col, int $length = 250 ): Structure {
			return new Structure( 'varchar', $col, $length );
		}
		
		public function enum( string $col, array $values ): Structure {
			return new Structure( __FUNCTION__, $col, $values );
		}
		
		public function blob( string $col ): Structure {
			return new Structure( __FUNCTION__, $col );
		}
		
		public function text( string $col ): Structure {
			return new Structure( __FUNCTION__, $col );
		}
		
		public function timestamp( string $col ): Structure {
			return new Structure( __FUNCTION__, $col );
		}
		
		public function date( string $col ): Structure {
			return new Structure( __FUNCTION__, $col );
		}
		
		public function dateTime( string $col ): Structure {
			return new Structure( __FUNCTION__, $col );
		}
		
		public function time( string $col, int $length = 6 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
		
		public function year( string $col, $length = 25 ): Structure {
			return new Structure( __FUNCTION__, $col, $length );
		}
	}
	