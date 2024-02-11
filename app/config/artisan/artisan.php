<?php
	
	namespace App\Terminal;
	
	use Core\App;
	
	class Artisan
	{
		protected static array $session = [];
		protected array $list = [];
		protected array $actions = [];
		protected string $template = "";
		
		public function startup(): void
		{
			$this->title( "AVAILABLE COMMANDS" );
			$this->print( array_merge( $this->list, [
				[ "serve",	"Start the application." ],
				[ "help", 'Not available for the meantime.'],
				[ "clear", 'Clear the terminal history.'],
				[ "exit", 'Terminate the session.']
			]));
		}
		
		public function execute( array|string $input_r ): void
		{
			$args = null;
			$input = $input_r;
			if ( is_array( $input_r ) )
			{
				$input = $input_r[ 1 ];
				
				unset( $input_r[ 0 ] );
				unset( $input_r[ 1 ] );
				
				$args = array_values( $input_r );
			}
			
			switch ( $input )
			{
				case 'exit':
					exit();
				
				case 'serve':
					
					$start_application = "sudo php -S ".config( 'DB_HOST' ).":".config( 'APP_PORT' )." -t ".getcwd()."/public";
					$password_command = "sudo -nv";
					
					$this->title( "PROCESSING", 37 );
					
					if ( getenv('HTACCESS_PROCESSED') )
						$this->print( "The .htaccess file has been processed." );
					
					else
						$this->print( "The .htaccess file is not found." );
					
					$this->title( "SUCCESS", 32 );
					exec( $password_command, $output, $returnStatus );
					
					if ( $returnStatus === 0 )
						shell_exec( $start_application );
					
					elseif ( $returnStatus === 1 ) {
						$this->print( "Please enter the password below." );
						shell_exec( $start_application );
					}
					
					else
						$this->info( "Error executing the command." );
					
					break;
				
				case 'migrate':
					$this->perform([
						'type'	=>	'db',
						'class'	=>	'migrate',
						'args'	=>	!$args
					]);
					break;
				
				case 'seeds':
					$this->perform([
						'type'	=>	'db',
						'class'	=>	'seeds',
						'args'	=>	!$args
					]);
					break;
				
				case 'help':
					$this->title( 'ERROR', 31 );
					$this->info( "Not available for the meantime." );
					$this->print([
						[ "command", "View the list of commands." ],
						[ "help", "Not available for the meantime." ],
						[ "exit", "Terminate the session." ]
					]);
					break;
				
				case 'clear':
				case 'command':
					$this->clear();
					$this->startup();
					break;
				
				default:
					
					if ( $this->has_session() )
					{
						$attr = $this->get_session_attr();
						$this->perform([
							'type'	=>	$attr[ 'type' ],
							'class'	=>	$attr[ 'class' ],
							'args'	=>	$input
						]);
					}
					else
					{
						$action = explode( ':', $input );
						$class_args = explode( ' ', trim( preg_replace('/\s+/', ' ', $action[ 1 ] ?? null ) ) );
						
						$type = $action[ 0 ];
						$class = $class_args[ 0 ];
						$args = $class_args[ 1 ] ?? null;
						
						if ( $this->check_function( $type, $class ) )
						{
							switch ( $class )
							{
								case 'list':
									$class = 'routeList';
									break;
							}
							
							$this->perform([
								'type'	=>	$type,
								'class'	=>	$class,
								'args'	=>	$args
							]);
						}
						else
						{
							$this->reset_template();
							$this->title( "ERROR", 31 );
							$this->print( "Invalid command given ($input)." );
							$this->print([
								[ "command", "View the list of commands." ],
								[ "help", "Not available for the meantime." ],
								[ "exit", "Terminate the session." ]
							]);
						}
					}
					break;
			}
		}
		
		public function register( string $name, string $action, string $description = '' ): void
		{
			$this->actions[ $name ][] = [
				'class'		=>	$action,
				'details'	=>	$description
			];
			
			$this->list[] = [ "$name:$action", $description ];
		}
		
		public function title( string $name, int $code = 33 ): void {
			echo "\n\033[1;$code;44m [ $name ] \033[0m\n\n";
		}
		
		public function info( mixed $info ): void {
			echo "INFO: $info\n\n";
		}
		
		public function print( array|string $commands ): void
		{
			if ( is_array( $commands ) )
			{
				$temp_name = "";
				$count = count( $commands );
				
				echo "\n";
				foreach ( $commands as $index => $command )
				{
					if ( strpos( $command[0], ":" ) !== false )
					{
						$controllers = explode( ":", $command[0] );
						$description = trim( $command[1] );
						
						$nextCommand = ( $index + 1 < $count ) ? $commands[ $index + 1 ] : null;
						$nextContainsColon = $nextCommand ? strpos( $nextCommand[0], ":" ) !== false : false;
						
						if ( !empty( $temp_name ) && $temp_name != $controllers[0] ) {
							echo "\n";
						}
						
						echo "\t\033[46m{$controllers[0]}\033[0m:\033[35m{$controllers[1]}\033[0m\t\t - $description\n";
						$temp_name = $controllers[0];
						
						if ( !$nextContainsColon ) {
							echo "\n";
						}
					}
					
					else echo "\t\033[46m{$command[0]}\033[0m\t\t\t - {$command[1]}\n";
				}
				
				echo "\n";
			}
			else echo( $commands."\n" );
		}
		
		public function clear(): void {
			echo str_repeat( "\n", 200 );
		}
		
		public function reset_template(): void {
			$this->template = "";
		}
		
		public function template(): string {
			return $this->template;
		}
		
		public function input( string $input ): void {
			$this->template = $input;
		}
		
		public function check_function( string $type, null|string $class ): bool
		{
			if ( isset( $this->actions[ $type ] ) )
			{
				for ( $i = 0; $i < count( $this->actions[ $type ] ); $i++ )
				{
					$obj = $this->actions[ $type ][ $i ];
					
					if ( strtolower( $obj[ 'class' ] ) == strtolower( $class ) )
						return true;
				}
			}
			
			return false;
		}
		
		public function perform( array $config ): void
		{
			$path = getcwd(). '/app/config/artisan/'.$config[ 'type' ].'/' .strtolower( $config[ 'class' ] ).'.php';
			
			if ( file_exists( $path ) )
			{
				require_once $path;
				
				$class = "App\\Terminal\\{$config[ 'type' ]}\\{$config[ 'class' ]}";
				new $class( $this, $config[ 'args' ] );
			}
			else app::error( "File not exist given ($path)." );
		}
		
		public function run( array $args = [] ): void
		{
			if ( isset( $args[ 1 ] ) )
				$this->execute( $args );
			
			else
			{
				$this->clear();
				$this->startup();
				
				while ( true )
				{
					$input = readline( $this->template() );
					$this->execute( $input );
				}
			}
		}
		
		public function new_session( string $name, array $config ): void
		{
			if ( !array_key_exists( $name, self::$session ) ) {
				self::$session[ $name ] = $config;
			}
		}
		
		public function get_session_name(): string {
			
			if ( !$this->has_session() ) {
				die( "No session were registered." );
			}
			
			return key( self::$session );
		}
		
		public function has_session(): bool {
			return (bool) self::$session;
		}
		
		public function get_session_attr(): array {
			return self::$session[ $this->get_session_name() ];
		}
		
		public function kill_session(): void {
			self::$session = [];
		}
	}