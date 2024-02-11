<?php
	
	namespace Illuminate\Http;
	use Core\App;
	
	class Actions extends Buffer
	{
		protected function validateMethodInstance( bool &$dispatch ): bool
		{
			if ( $dispatch === false )
			{
				$dispatch = match ( $this->instance )
				{
					'fallback'	=>	true,
					default	=>	false
				};
			}
			
			return $dispatch;
		}
		
		protected function checkMethodServer(): bool
		{
			switch ( $this->instance )
			{
				case 'view':
				case 'redirect':
					return true;
				
				case 'match':
					foreach ( $this->methods as $method ) {
						if ( in_array( strtoupper( $method ), $this->valid_http ) )
						{
							if ( $this->compareMethods( $method ) ) {
								return true;
							}
						}
					}
					break;
				
				default:
					if ( in_array( strtoupper( $this->instance ), $this->valid_http ) )
					{
						if ( $this->compareMethods( $this->methods ) ) {
							return true;
						}
					}
					break;
			}
			
			return false;
		}
		
		protected function checkMethodURI(): bool
		{
			if ( !in_array( $this->instance, $this->no_URI ) && $this->compareURL( $this->uri ) )
				return true;
			
			return false;
		}
		
		protected function checkBlacklistedDomain( string|null $domain ): bool
		{
			if ( empty( $this->blacklisted_domain ) )
				return false;
			
			return in_array( strtolower( $domain ), $this->blacklisted_domain );
		}
		
		protected function compareMethods( string|array $methods ): bool
		{
			if ( $this->getHTTP() == strtoupper( $methods ) ) {
				return true;
			}
			
			return false;
		}
		
		protected function compareURL( string|null $uri ): bool
		{
			$matched 	= 	0;
			$address 	= 	Request::url();
			$uri 		= 	$this->serializeURI( $uri );
			$route_uri 	= 	$this->separateSubDirectories( $uri );
			$route_url 	= 	$this->separateSubDirectories( $address->uri );
			
			if ( count( $route_uri ) === count( $route_url ) )
			{
				foreach ( $route_uri as $index => $directory )
				{
					if ( isset( $route_url[ $index ] ) )
					{
						if ( preg_match( '/^\{[^{}]+\}$/', $directory ) )
						{
							$this->params[ str_replace(['{', '}'], '', $directory ) ] = preg_replace('/\?.*/', '', $route_url[ $index ] );
							$matched++;
						}
						else
						{
							if ( strtolower( $directory ) === strtolower( strstr( $route_url[ $index ], '?', true ) ?: $route_url[ $index ] ) ) {
								$matched++;
							}
						}
					}
				}
			}
			else $matched = -1;
			
			return ( $matched === count( $route_uri ) );
		}
		
		protected function separateSubDirectories( null|string $value ): array
		{
			return array_values( array_filter( explode( '/', $value ), function ( $value ) {
				return $value !== "";
			}));
		}
		
		protected function mergePrefix( string|null $uri ): string|null
		{
			if ( !in_array( $this->instance, $this->excluded_prefix ) && self::status( 'prefix' ) ) {
				$uri = self::fetch( 'prefix' ).( preg_match( '/^\\//', $uri ) ? "" : "/" )."$uri";
			}
			
			return $uri;
		}
		
		protected function serializeURI( string|null $uri ): string
		{
			$uri = $this->mergePrefix( $uri );
			return serialize_uri( $uri );
		}
		
		protected function getHTTP()
		{
			$method = null;
			
			if ( !config( 'ARTISAN' ) && !in_array( ( $method = $_SERVER[ 'REQUEST_METHOD' ] ), $this->valid_http ) )
				app::error( "Invalid HTTP referrer '$method'" );
			
			return $method;
		}
		
		protected function getReferrerDomain(): string|null
		{
			if ( ( $referrer = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? filter_var( $_SERVER[ 'HTTP_REFERER' ], FILTER_VALIDATE_URL ) : null ) !== null )
			{
				if ( filter_var( $referrer, FILTER_VALIDATE_URL ) === false ) {
					app::error( "Invalid Referrer URL given '$referrer'" );
				}
			}
			
			return !empty( trim( $referrer ) ) ? parse_url( $referrer, PHP_URL_HOST ) : $referrer;
		}
		
		protected function registerData(): mixed
		{
			return match ( $this->instance )
			{
				'view'	=> $this->args[2] ?? [],
				default => []
			};
		}
		
		protected function registerView(): string
		{
			return match ( $this->instance )
			{
				'view'	=>	$this->args[1] ?? '404',
				default => ''
			};
		}
		
		protected function registerCode(): int
		{
			return match ( $this->instance )
			{
				'redirect'	=>	$this->args[2] ?? 302,
				default => 302
			};
		}
		
		protected function registerHeaders()
		{
			return match ( $this->instance )
			{
				'view' => $this->args[4] ?? [],
				default => [],
			};
		}
		
		protected function registerActions()
		{
			switch ( true )
			{
				case in_array( $this->instance, [ 'get', 'post', 'put', 'patch', 'delete', 'options', 'any' ]):
					return $this->args[1] ?? null;
				
				case $this->instance == 'redirect':
					return $this->serializeURI( $this->args[1] ?? null );
				
				case $this->instance == 'match':
					return $this->args[2] ?? null;
				
				case $this->instance == 'controller':
				case $this->instance == 'middleware':
				case $this->instance == 'fallback':
				case $this->instance == 'prefix':
					return $this->args[0] ?? null;
				
				default:
					return null;
			}
		}
		
		protected function registerMethods(): array|string
		{
			return match ( $this->instance )
			{
				'match' => $this->args[0],
				default => $this->instance,
			};
		}
		
		protected function registerURI(): array|string|null
		{
			if ( !in_array( $this->instance, $this->no_URI ) )
			{
				return match ( $this->instance ) {
					'match' => $this->args[1],
					default => $this->args[0],
				};
			}
			
			return null;
		}
	}