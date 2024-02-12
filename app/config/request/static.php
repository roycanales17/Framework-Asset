<?php
	
	namespace Illuminate\Http;
	use JetBrains\PhpStorm\NoReturn;
	
	class StaticRequest
	{
		static function url(): object
		{
			$url 			= 	new \stdClass();
			$url->protocol 	= 	( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] === 'on' ) ? "https" : "http";
			$url->host 		= 	$_SERVER[ 'HTTP_HOST' ];
			$url->uri 		= 	$_SERVER[ 'REQUEST_URI' ];
			$url->port 		= 	$_SERVER[ 'SERVER_PORT' ];
			$url->address 	=	"$url->protocol://$url->host$url->uri";
			$url->full 		= 	parse_url( "$url->protocol://$url->host$url->uri" );
			return $url;
		}
		
		static function ip_address(): string|null {
			return ( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) ? $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] : $_SERVER[ 'REMOTE_ADDR' ];
		}
		
		static function is_json( $string ) 
		{
			json_decode ($string );
    		return ( json_last_error() == JSON_ERROR_NONE );
		}

		#[NoReturn] static function exit(mixed $message = 0 ): void {
			exit( $message );
		}
	}