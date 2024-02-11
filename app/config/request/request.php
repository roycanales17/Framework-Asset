<?php
	
	namespace Illuminate\Http;
	
	use Core\{App, Session, trace};
	use stdClass;
	
	class Request extends StaticRequest
	{
		private static array $input = [], $params = [], $child_class = [];
		private array $response = [];
		
		private bool $root_perform = false;
		private bool $status = true;
		
		function input( string $name ): mixed
		{
			$inputs = $this->prepare_inputs();
			
			return $inputs[ strtolower( $name ) ] ?? "";
		}
		
		function all(): array
		{
			return $this->prepare_inputs();
		}
		
		function has( string $key ): bool
		{
			$inputs = $this->prepare_inputs();
			
			return (bool) $inputs[ strtolower( $key ) ];
		}
		
		function only( array $input_keys ): array
		{
			$inputs = $this->prepare_inputs();
			
			$array = [];
			foreach ( $input_keys as $key )
			{
				$key = strtolower( $key );
				if ( isset( $inputs[ $key ] ) ) {
					$array[ $key ] = $inputs[ $key ];
				}
			}
			
			return $array;
		}
		
		function except( array $input_keys ): array
		{
			$inputs = $this->prepare_inputs();
			
			$array = [];
			foreach ( $inputs as $key => $value )
			{
				if ( !in_array( $key, array_map( 'strtolower', $input_keys ) ) ) {
					$array[ $key ] = $value;
				}
			}
			
			return $array;
		}
		
		function params( string $name = "" ): int|string|array|bool|null
		{
			if ( empty( $name = trim( $name ) ) )
				return (array) $GLOBALS[ 'URI_PARAMS' ] ?? [];
			
			else
			{
				if ( self::$params ) {
					return self::$params[ $name ] ?? null;
				}
				
				$params = new stdClass();
				foreach ( $GLOBALS[ 'URI_PARAMS' ] as $key => $value ) {
					self::$params[ $key ] = $value;
					$params->$key = $value;
				}
				
				if ( isset( $params->$name ) )
					return $params->$name;
			}
			
			return null;
		}
		
		function method(): string {
			return $_SERVER[ 'REQUEST_METHOD' ];
		}
		
		function isSuccess(): bool
		{
			$this->perform_root();
			
			if ( count( $this->response ) || Session::get( 'request_response' ) ) {
				return false;
			}
			
			return $this->status;
		}
		
		function isFailed(): bool
		{
			$this->perform_root();
			
			if ( $this->status && ( $this->response || Session::get( 'request_response' ) ) ) {
				return true;
			}
			
			return !$this->status;
		}
		
		function response(): array
		{
			$this->perform_root();
			
			$temp = [];
			$request_response = Session::get( 'request_response' );
			
			if ( $request_response )
			{
				$this->status = false;
				Session::forget( 'request_response' );
				
				foreach ( $request_response as $attr ) {
					$temp[ $attr[ 'key' ] ] = $attr[ 'info' ];
				}
				
				$this->response = $temp;
			}
			
			return $this->response;
		}
		
		function validateMin( string $value, int $min ): bool {
			return strlen( $value ) > $min;
		}
		
		function validateMax( string $value, int $max ): bool {
			return strlen( $value ) < $max;
		}
		
		function validateArray( $value ): bool {
			return is_array( $value );
		}
		
		function validateNull( $value ): bool {
			return is_null( $value );
		}
		
		function validateNumeric( $value ): bool {
			return is_numeric( $value );
		}
		
		function validateString( $value ): bool {
			return is_string( $value );
		}
		
		function validateInteger( $value ): bool {
			return is_integer( $value );
		}
		
		function validateEmail( $email ): bool {
			return filter_var( $email, FILTER_VALIDATE_EMAIL );
		}
		
		function validateRequired( $value ): bool {
			return (bool) $value;
		}
		
		function validatePassword( string $key, $value ): bool {
			
			$status = true;
			
			// Check for minimum length
			if ( strlen( $value ) < 8 ) {
				$this->insert_response( $key, "Password must be at least 8 characters long." );
				$status = false;
			}
			
			// Check for at least one uppercase letter
			if ( !preg_match('/[A-Z]/', $value ) ) {
				$this->insert_response( $key, "Password must contain at least one uppercase letter." );
				$status = false;
			}
			
			// Check for at least one lowercase letter
			if ( !preg_match( '/[a-z]/', $value ) ) {
				$this->insert_response( $key, "Password must contain at least one lowercase letter." );
				$status = false;
			}
			
			// Check for at least one number
			if ( !preg_match('/[0-9]/', $value ) ) {
				$this->insert_response( $key, "Password must contain at least one number." );
				$status = false;
			}
			
//			// Check for special characters
/*			if ( !preg_match( '/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $value ) ) {*/
//				$this->insert_response( $key, "Password must contain at least one special character." );
//				$status = false;
//			}
			
			return $status;
		}
		
		function validate( array $array ): bool
		{
			if ( !$this->has( 'token' ) )
			{
				$this->insert_response( 'default', "Please refresh the page, something went wrong.");
				$this->set_status( false );
			}
			else
			{
				if ( $this->input( 'token' ) !== Session::get( '$_csrf-token-recent' ) )
				{
					$this->insert_response( 'default', "Please refresh the page, something went wrong.");
					$this->set_status( false );
				}
			}
			
			foreach ( $array as $key => $rules )
			{
				$policies = explode( '|', $rules );
				for ( $i = 0; $i < count( $policies ); $i++ )
				{
					$rule = explode( ':', $policies[ $i ] );
					$rule_key = $rule[ 0 ];
					$rule_val = $rule[ 1 ] ?? 0;
					$input = $this->input( $key );
					
					if ( $rule_val )
					{
						switch ( $rule_key )
						{
							case 'max':
								
								if ( !$this->validateMax( $input, $rule_val ) )
								{
									$this->insert_response( $key, "Must not exceed the maximum ($rule_val) character/s, given value ($input).");
									$this->set_status( false );
								}
								break;
							
							case 'min':
								
								if ( !$this->validateMin( $input, $rule_val ) )
								{
									$this->insert_response( $key, "Must not below ($rule_val) character/s, given value ($input).");
									$this->set_status( false );
								}
								break;
							
							default:
								
								if ( !self::$child_class ) {
									self::$child_class = getChildClass( 'Illuminate\Database\Model' );
								}
								
								$found = false;
								$child = self::$child_class;
								
								for ( $j = 0; $j < count( $child ); $j++ )
								{
									$name = $child[ $j ];
									$obj = explode( '\\', $name );

									if ( strtolower( $obj[ count( $obj ) - 1 ] ) === strtolower( $rule_key ) )
									{
										if ( $name::where( $rule_val, $input )->count() === 1 )
										{
											$this->insert_response( $key, ucfirst( $key )." is already exist.");
											$this->set_status( false );
										}
										$found = true;
										break;
									}
								}

								if ( !$found ) {
									app::error( "We cannot locate the class ({$rule_key}), please make the class is extended with abstract class model." );
								}
								
								break;
						}
					}
					else
					{
						switch ( $rule_key )
						{
							case 'required':
								if ( !$this->validateRequired( $input ) )
								{
									$this->insert_response( $key ,$this->get_message( $policies, $rule_key ) ?: ucfirst( $key )." is required.");
									$this->set_status( false );
								}
								break;
							
							case 'array':
								if ( !$this->validateArray( $input ) )
								{
									$this->insert_response( $key, $this->get_message( $policies, $rule_key ) ?: "Must be array given value ($input)." );
									$this->set_status( false );
								}
								break;
							
							case 'null':
								if ( !$this->validateNull( $input ) )
								{
									$this->insert_response( $key, $this->get_message( $policies, $rule_key ) ?: "Must be null given value ($input)." );
									$this->set_status( false );
								}
								break;
							
							case 'numeric':
								if ( !$this->validateNumeric( $input ) )
								{
									$this->insert_response( $key, $this->get_message( $policies, $rule_key ) ?: "Must be numeric given value ($input)." );
									$this->set_status( false );
								}
								break;
							
							case 'integer':
								if ( !$this->validateInteger( $input ) )
								{
									$this->insert_response( $key, $this->get_message( $policies, $rule_key ) ?: "Must be integer given value ($input).");
									$this->set_status( false );
								}
								break;
							
							case 'string':
								if ( !$this->validateString( $input ) )
								{
									$this->insert_response( $key, $this->get_message( $policies, $rule_key ) ?: "Must be string given value ($input).");
									$this->set_status( false );
								}
								break;
							
							case 'email':
								if ( !$this->validateEmail( $input ) )
								{
									$this->insert_response( $key, $this->get_message( $policies, $rule_key ) ?: "Invalid email address given ($input)." );
									$this->set_status( false );
								}
								break;
							
							case 'password':
								if ( !$this->validatePassword( $rule_key, $input ) ) {
									$this->set_status( false );
								}
								break;
							
							case 'confirmed':
								if ( !$this->input( 'confirm_password' ) )
								{
									$this->insert_response( 'confirm_password', $this->get_message( $policies, $rule_key ) ?: "Password confirmation is required.");
									$this->set_status( false );
								}
								else
								{
									if ( $input !== $this->input( 'confirm_password' ) ) {
										$this->insert_response( 'confirm_password', $this->get_message( $policies, $rule_key ) ?: "Password and confirmation password should be match.");
										$this->set_status( false );
									}
								}
								break;
						}
					}
				}
			}
			
			if ( !$this->status ) {
				trace( "Request validation failed." )->store( $this->response );
			}
			
			else trace( "Request validation success." );
			
			return $this->status;
		}
		
		function add_error_message( string $key, string $message ) {
			$this->insert_response( $key, $message );
			$this->set_status( false );
		}
		
		private function get_message( array $policies, string $key ): string|bool
		{
			for ( $i = 0; $i < count( $policies ); $i++ )
			{
				$rule = explode( ':', $policies[ $i ] );
				$rule_key = $rule[ 0 ];
				
				if ( preg_match( '/\{(.*)\}/i', trim( $rule_key ), $message ) )
				{
					if ( preg_match( '/^(.*?)\{/i', trim( $rule_key ), $input_key ) )
					{
						if ( trim( $input_key[ 1 ] ) === $key )
						{
							$message = $message[1] ?? "";
							if ( preg_match( '/\[(.*?)\]/i', $message, $input ) )
								$message = str_replace( $input[0], $this->input( $input[1] ), $message );
							
							return $message;
						}
					}
				}
			}
			
			return false;
		}
		
		private function root(): string {
			return get_called_class();
		}
		
		private function perform_root(): void
		{
			$class = $this->root();
			
			if ( class_exists( $class ) && !$this->root_perform )
			{
				$namespace = explode( '\\', $class );
				
				if ( $namespace[1] === 'Rules' )
				{
					$obj = new $class();
					
					if ( method_exists( $obj, 'authorize' ) && !$this->authorize() )
					{
						$this->insert_response( 'default', "Not authorized." );
						$this->set_status( false );
					}
					else
					{
						if ( method_exists( $obj, 'rules' ) && $rules = $this->rules() ) {
							$this->validate( $rules );
						}
					}
					
					$this->root_perform = true;
				}
			}
		}
		
		private function set_status( bool $status = true ): void {
			$this->status = $status;
		}
		
		private function prepare_inputs(): array
		{
			if ( !self::$input ) {
				self::$input = array_change_key_case( $_GET + $_POST );
			}
			
			return self::$input;
		}
		
		private function insert_response( string $name, string $message ): void
		{
			if ( !array_key_exists( $name, $this->response ) )
			{
				$this->response[ $name ] = $message;
				Session::insert( 'request_response', [
					'key'	=>	$name,
					'info'	=>	$message
				]);
			}
		}
	}