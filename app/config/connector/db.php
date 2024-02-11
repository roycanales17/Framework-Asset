<?php
	
	namespace Illuminate\Database;
	use Closure;
	
	class db extends Facade
	{
		static function run( string $sql, array|Closure $callback = [] ): self {
			return new self( $sql, $callback );
		}
		
		static function table( string $table ): Eloquent
		{
			$obj = new Eloquent();
			$obj->table( $table );
			
			return $obj;
		}
		
		function __construct( string $sql, array|Closure $callback = [] )
		{
			$this->sql = $sql;
			$this->method = 'mysqli';
			$this->database = config( 'DB_NAME' );
			$this->callback = $callback;
		}
		
		public function bindValue( string $column, $value ): self
		{
			$method = gettype( $value );
			( new Binds )->$method( $column, $value );
			return $this;
		}
		
		public function setMethod( string $method ): self {
			$this->method = $method;
			return $this;
		}
		
		public function setDatabase( string $name ): self {
			$this->database = $name;
			return $this;
		}
		
		function __destruct()
		{
			if ( $this->destruct ) {
				$this->compile();
			}
		}
	}