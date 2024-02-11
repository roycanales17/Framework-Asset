<?php
	
	namespace Illuminate\Database;
	
	use mysqli_sql_exception;
	use PDOException;
	
	Abstract class Facade implements Layer
	{
		protected string $sql;
		protected string $database;
		protected string $method;
		protected array|\Closure $callback;
		protected bool $destruct = true;
		
		public function fetch(): array {
			$this->destruct = false;
			return $this->compile( __FUNCTION__ );
		}
		
		public function col(): array {
			$this->destruct = false;
			return $this->compile( __FUNCTION__ );
		}
		
		public function field(): mixed {
			$this->destruct = false;
			return $this->compile( __FUNCTION__ );
		}
		
		public function row(): array {
			$this->destruct = false;
			return $this->compile( __FUNCTION__ );
		}
		
		public function lastID(): int|null {
			$this->destruct = false;
			return $this->compile( __FUNCTION__ );
		}
		
		public function count(): int {
			$this->destruct = false;
			return $this->compile( __FUNCTION__ );
		}
		
		protected function compile( string $action = 'execute' ): mixed
		{
			$response = true;
			
			try
			{
				$this->prepare();
				$data = $this->response( $action );
				
				switch ( $action )
				{
					case 'count':
						$response = (int) $data;
						break;
					
					case 'lastID':
						$response = $data ?? false;
						break;
					
					case 'fetch':
						$response = $data ?: [];
						break;
					
					case 'col':
						$temp = [];
						foreach ( $data as $item ) {
							$temp[] = current( $item );
						}
						$response = $temp;
						break;
					
					case 'field':
						$response = false;
						if ( $data ) {
							$response = reset( $data[0] );
						}
						break;
					
					case 'row':
						$response = [];
						if ( $data ) {
							$response = $data[0];
						}
						break;
				}
				
			}
			catch ( mysqli_sql_exception | PDOException $e )
			{
				$trace = debug_backtrace();
				dump([
					'message'	=>	"[ ".strtoupper( $this->method )." ] ". $e->getMessage(),
					'query'		=>	$this->sql,
					'trace'		=>	$trace
				]);
				stop();
			}
			finally
			{
				if ( $action === 'lastID' ) {
					$action = 'create';
				}
				
//				trace( "Query executed given action ($action)" )->important()->store([
//					'sql'			=>	$this->sql,
//					'database'		=>	$this->database,
//					'connection'	=>	$this->method,
//					'action'		=>	$action
//				]);
			}
			
			return $response;
		}
		
		protected function prepare(): void
		{
			# INFO: We force to use `PDO` connection layer if binds were filled up.
			switch ( true )
			{
				case is_array( $this->callback ):
					if ( $this->callback )
					{
						$binds = new Binds();
						foreach ( $this->callback as $key => $value )
						{
							$method = gettype( $value );
							$binds->$method( $key, $value );
						}
						$this->method = 'pdo';
					}
					break;
				
				case is_object( $this->callback ):
					closureMethod( $this->callback );
					if ( _pdo::count() ) {
						$this->method = 'pdo';
					}
					break;
			}
			
			if ( _pdo::count() ) {
				$this->method = 'pdo';
			}
		}
		
		protected function response( $action ): mixed {
			$object = [ 'Illuminate', 'Database', "_{$this->method}" ];
			$function = str_replace( [' ', "\t"], '', strtolower( 'P E R F O R M' ) );
			return ( "\\".implode( '\\', $object ) )::$function( $this->database, $this->sql, $action );
		}
	}