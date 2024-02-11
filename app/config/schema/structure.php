<?php
	
	namespace Illuminate\Database\Facades;
	
	class Structure {
		
		protected const SETUP = [
			
			'name'			=>	"",
			'type'			=> 	"int",
			'length'		=>	"",
			'default'		=>	"none",
			'collation'		=>	"",
			'attributes'	=>	"",
			'nullable'		=>	false,
			'comment'		=>	"",
			
			'increment'		=>	false,
			'primary'		=>	false,
			'unique'		=>	false,
			'index'			=>	false
		];
		
		protected string $sql;
		protected array $conf;
		protected int $starting_index;
		
		function __construct( string $type, string $col, mixed $length_values = "" )
		{
			$this->sql = "";
			$this->conf = self::SETUP;
			$this->starting_index = 0;
			$this->conf[ 'name' ] = $col;
			$this->conf[ 'type' ] = $type;
			$this->conf[ 'length' ] = $length_values;
		}
		
		public function autoIncrement( int $starting_index = 0 ): self
		{
			if ( $starting_index )
				$this->starting_index = $starting_index;
			
			$this->conf[ 'increment' ] = true;
			return $this;
		}
		
		public function primary(): self {
			$this->conf[ __FUNCTION__ ] = true;
			return $this;
		}
		
		public function unique(): self {
			$this->conf[ __FUNCTION__ ] = true;
			return $this;
		}
		
		public function index(): self {
			$this->conf[ __FUNCTION__ ] = true;
			return $this;
		}
		
		public function binary(): self {
			$this->conf[ 'attributes' ] = __FUNCTION__;
			return $this;
		}
		
		public function unsigned(): self {
			$this->conf[ 'attributes' ] = __FUNCTION__;
			return $this;
		}
		
		public function on_update(): self {
			$default = "DEFAULT CURRENT_TIMESTAMP ";
			if ( $this->conf[ 'default' ] !== 'none' )
				$default = "";
			
			$this->conf[ 'attributes' ] = $default."ON UPDATE CURRENT_TIMESTAMP";
			return $this;
		}
		
		public function current_date(): self {
			$default = "DEFAULT ";
			if ( $this->conf[ 'default' ] !== 'none' )
				$default = "";
			
			$this->conf[ 'default' ] = $default."CURRENT_TIMESTAMP";
			return $this;
		}
		
		public function default( null|string $value ): self {
			$this->conf[ 'default' ] = "DEFAULT ".( is_null( $value ) ? $value : "'$value'" );
			return $this;
		}
		
		public function nullable(): self {
			$this->conf[ 'nullable' ] = true;
			return $this;
		}
		
		public function comment( string $info ): self {
			$this->conf[ 'comment' ] = preg_replace( '/[^\w\s]/', '', trim( $info ) );
			return $this;
		}
		
		public function collation( string $type ): self {
			$this->conf[ 'collation' ] = $type;
			return $this;
		}
		
		function __destruct()
		{
			# Configuration
			$struct = $this->conf;
			
			# name
			$this->sql .= "`$struct[name]` ";
			
			# type
			$this->sql .= strtoupper( $struct[ 'type' ] ).( $struct[ 'length' ] ? "(".( is_array( $struct[ 'length' ] ) ? "'".implode( "', '", $struct[ 'length' ] )."'" : $struct[ 'length' ] ).") " : " " );
			
			# default
			$this->sql .= $struct[ 'default' ] === 'none' ? "" : ( is_null( $struct[ 'default' ] ) ? "NULL " : ( strtoupper( $struct[ 'default' ] ) === "CURRENT_TIMESTAMP " ?: "$struct[default] " ) );
			
			# collation
			$this->sql .= $struct[ 'collation' ] ? "COLLATE $struct[collation] " : "";
			
			# attributes
			$this->sql .= $struct[ 'attributes' ] ? strtoupper( $struct[ 'attributes' ] )." " : "";
			
			# nullable
			$this->sql .= $struct[ 'nullable' ] ? "NULL " : "NOT NULL ";
			
			# comment
			$this->sql .= $struct[ 'comment' ] ? "COMMENT '$struct[comment]' " : "";
			
			# INDEX
			foreach ( [ 'primary', 'unique', 'index' ] as $type )
			{
				if ( $struct[ $type ] )
				{
					switch ( $type )
					{
						case 'primary':
							if ( !$struct[ 'increment' ] )
								SQL::add_index( 'primary', $struct[ 'name' ] );
							break;
						
						default:
							SQL::add_index( $type, $struct[ 'name' ] );
							break;
					}
				}
			}
			
			# auto increment
			$this->sql .= $struct[ 'increment' ] ? "PRIMARY KEY AUTO_INCREMENT " : "";
			
			# index auto increment
			if ( $this->starting_index )
				SQL::add_extension( "AUTO_INCREMENT = $this->starting_index" );
			
			# Insert Column
			SQL::add_column( trim( $this->sql ) );
		}
	}