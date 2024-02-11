<?php
	
	namespace Core;
	
	class Session
	{
		static function start(): void
		{
			$started = false;
			if ( version_compare( phpversion(), '5.4.0', '<' ) )
			{
				if ( session_id() == '' )
				{
					session_start();
					$started = true;
				}
			}
			else
			{
				if ( session_status() == PHP_SESSION_NONE )
				{
					session_start();
					$started = true;
				}
			}
			
			if ( $started )
				trace( "Starting session..." )->store( $_SESSION );
			
			else
				trace( "Failed to start session, might be possible this is called twice." );
		}
		
		static function all(): array|null
		{
			$array = $_SESSION;
			unset( $array[ 'session' ] );
			
			return $array;
		}
		
		static function destroy(): void
		{
			$_SESSION = array();
			
			if ( ini_get( "session.use_cookies" ) )
			{
				$params = session_get_cookie_params();
				setcookie( session_name(), '', time() - 42000,
					$params[ "path" ], $params[ "domain" ],
					$params[ "secure" ], $params[ "httponly" ]
				);
			}
			
			session_destroy();
		}
		
		static function forget( array|string $key ): void
		{
			switch ( true )
			{
				case is_string( $key ):
					if ( isset( $_SESSION[ 'session' ] ) && array_key_exists( $key, $_SESSION[ 'session' ] ) ) {
						unset( $_SESSION[ 'session' ][ $key ] );
					}
					
					unset( $_SESSION[ $key ] );
					break;
				
				case is_array( $key ):
					for ( $i = 0; $i < count( $key ); $i++ )
					{
						if ( isset( $_SESSION[ 'session' ] ) && array_key_exists( $key[ $i ], $_SESSION[ 'session' ] ) ) {
							unset( $_SESSION[ 'session' ][ $key[ $i ] ] );
						}
						
						unset( $_SESSION[ $key[ $i ] ] );
					}
					break;
			}
		}
		
		static function get( string $key, bool $forget = false ): mixed
		{
			$data = $_SESSION[ $key ] ?? null;
			
			if ( isset( $_SESSION[ 'session' ] ) && array_key_exists( $key, $_SESSION[ 'session' ] ) )
			{
				$_SESSION[ 'session' ][ $key ][ 'utilized' ] = true;
				
				if ( $forget ) {
					self::forget( $key );
				}
			}
			
			return $data;
		}
		
		static function insert( string $key, $value ): void
		{
			if ( isset( $_SESSION[ 'session' ] ) && array_key_exists( $key, $_SESSION[ 'session' ] ) ) {
				$_SESSION[ 'session' ][ $key ][ 'updated_at' ] = date( 'Y-m-d H:i:s' );
			}
			else {
				$_SESSION[ 'session' ][ $key ] = [
					'utilized'		=>	false,
					'created_at'	=>	date( 'Y-m-d H:i:s' ),
					'updated_at'	=>	null
				];
			}
			
			if ( !array_key_exists( $key, $_SESSION ) ) {
				$_SESSION[ $key ] = [];
			}
			
			
			$_SESSION[ $key ][] = $value;
		}
		
		static function put( string $key, $value ): void
		{
			if ( isset( $_SESSION[ 'session' ] ) && array_key_exists( $key, $_SESSION[ 'session' ] ) ) {
				$_SESSION[ 'session' ][ $key ][ 'updated_at' ] = date( 'Y-m-d H:i:s' );
			}
			else
			{
				$_SESSION[ 'session' ][ $key ] = [
					'utilized'		=>	false,
					'created_at'	=>	date( 'Y-m-d H:i:s' ),
					'updated_at'	=>	null
				];
			}
			
			$_SESSION[ $key ] = $value;
		}
		
		static function has( string $key ): bool {
			return (bool) ( $_SESSION[ $key ] ?? false );
		}
	}