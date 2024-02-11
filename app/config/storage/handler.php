<?php
	
	namespace Core;
	use Illuminate\Database\Binds;
	use Illuminate\Database\db;
	use Illuminate\Database\Facades\Blueprint;
	use Illuminate\Database\Facades\Schema;
	use Illuminate\Http\Request;
	use SessionHandlerInterface;
	
	class Handler implements SessionHandlerInterface
	{
		protected string $table = 'session';
		
		function __construct()
		{
			if ( !Schema::hasTable( $this->table ) )
			{
				Schema::create( $this->table, function( Blueprint $col )
				{
					$col->string( 'id', 128 )->primary();
					$col->blob( 'data' );
					$col->string( 'ip', 64 );
					$col->dateTime( 'expires_at' );
				});
			}
		}
		
		public function open( $save_path, $session_name ): bool {
			# Nothing here
			return true;
		}
		
		public function close(): bool {
			# Nothing here
			return true;
		}
		
		public function read( $session_id ): string|false
		{
			$data = db::table( $this->table )
				->select( 'data' )
				->where( 'id', $session_id )
				->where( 'id', $session_id )
				->field();
			
			return $data !== false ? decrypt( $data ) : '';
		}
		
		public function write( $session_id, $data ): bool
		{
			$result = db::run( "REPLACE INTO $this->table ( `id`, `data`, `ip`, `expires_at` ) VALUES ( :session_id, :data, :ip, :expires_at )", function ( Binds $params ) use ( $session_id, $data )
			{
				$expires_at = date( 'Y-m-d H:i:s', time() + ini_get( 'session.gc_maxlifetime' ) );
				$params->string( 'session_id', $session_id );
				$params->string( 'data', encrypt( $data ) );
				$params->string( 'ip', Request::ip_address() );
				$params->string( 'expires_at', $expires_at );
			})->count();
			
			return $result != false;
		}
		
		public function destroy( $session_id ): bool
		{
			db::run( "DELETE FROM $this->table WHERE id = :id", function( Binds $params ) use ( $session_id ) {
				$params->string( 'id', $session_id );
			});
			
			return true;
		}
		
		public function gc( $maxlifetime ): int|false
		{
			db::run( "DELETE FROM $this->table WHERE expires_at < NOW()" );
			return true;
		}
		
		public static function session_close(): void
		{
			session_write_close();
			trace( 'Session is successfully closed!' , 3 );
		}
	}