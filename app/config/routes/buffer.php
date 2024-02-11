<?php
	namespace Illuminate\Http;
	
	class Buffer
	{
		# Route Config
		protected mixed $args, $action, $data;
		protected string $instance, $view;
		protected array|string $methods;
		protected string|null $uri;
		protected array $headers;
		protected int $code;
		
		# Referrer Config
		protected string|null $referrer_domain;
		protected string|null $referrer_method;
		protected array $params = [];
		
		# Group Routes
		private static string $prefix = "";
		private static string $controller = "";
		private static array $middleware = [];
		
		public static function register( string $method, string|array $action ): void
		{
			if ( isset( self::$$method ) )
			{
				switch ( $method )
				{
					case 'middleware':
						array_push( self::$$method, $action );
						break;
					
					default:
						self::$$method = $action;
						break;
				}
			}
		}
		
		public static function fetch( string $method ): string|array {
			return self::$$method;
		}
		
		public static function reset( string $method ): void
		{
			if ( isset( self::$$method ) )
			{
				switch ( true )
				{
					case is_string( self::$$method ):
						self::$$method = "";
						break;
					
					case is_array( self::$$method ):
						self::$$method = [];
						break;
				}
			}
		}
		
		public static function status( string $method ): bool {
			return (bool) self::$$method;
		}
	}