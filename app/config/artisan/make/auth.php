<?php
	
	namespace App\Terminal\Make;
	
	use PhpSchool\CliMenu\CliMenu;
	use PhpSchool\CliMenu\Builder\CliMenuBuilder;
	
	class Auth
	{
		protected mixed $cmd;
		protected array $features = [
			'Captcha',		# User requires to solve the problem before submitting the form
			'Passkey',		# You can sign in to your Account with your fingerprint, face scan, or device screen lock, like a PIN
			'2FA',			# 2-Factor Authentication
		];
		
		function __construct( $artisan, $input )
		{
			$this->cmd = $artisan;
			
			if ( !$this->cmd->has_session() )
			{
				$this->cmd->new_session( 'auth-startup', [
					'type'	=>	'make',
					'class'	=>	'auth'
				]);
			}
			
			switch ( $this->cmd->get_session_name() )
			{
				case 'auth-startup':
					
					$this->cmd->title( "CONFIRMATION", 30 );
					$this->cmd->print( "Are you sure you want to install authentication layer?" );
					$this->cmd->input( "[Y/N] " );
					
					$this->cmd->kill_session();
					$this->cmd->new_session( 'auth-confirmation', [
						'type'	=>	'make',
						'class'	=>	'auth'
					]);
					
					break;
				
				case 'auth-confirmation':
					
					if ( !in_array( strtolower( $input ), [ 'y', 'n' ]) )
					{
						$this->cmd->title( "ERROR", 31 );
						$this->cmd->print( "Invalid input keywords, we only accept [Y/N]." );
					}
					else
					{
						$this->cmd->reset_template();
						
						if ( strtolower( $input ) === 'y' ) {
							$this->features_installation();
						}
						else
						{
							$this->cmd->kill_session();
							$this->cmd->startup();
						}
					}
					
					break;
			}
		}
		
		/**
		 * Documentation: https://github.com/php-school/cli-menu/blob/master/README.md
		 */
		function features_installation(): void
		{
			$selected = [];
			$menu = new CliMenuBuilder();
			
			$menu->setTitle( "Please select below which features you want to install?" );
			$menu->disableDefaultItems();
			$menu->setTitleSeparator(' ');
			$menu->setForegroundColour( 'green' );
			$menu->setBackgroundColour( 'black' );
			
			$callable = function ( CliMenu $menu ) use ( &$selected )
			{
				$text = $menu->getSelectedItem()->getText();
				
				$index = array_search( $text, $selected );
				if ( $index !== false )
					unset( $selected[ $index ] );
				else
					$selected[] = $text;
			};
			
			$options = $this->features;
			foreach ( $options as $key ) {
				$menu->addCheckboxItem( "$key", $callable );
			}
			
			$menu->addLineBreak( ' ' );
			$menu->addItem('Install Now', function ( CliMenu $menu ) use ( &$selected )
			{
				$done = false;
				if ( empty( $selected ) )
				{
					$result = $menu->askText()
						->setPromptText( "Are you sure you don't want to include one of these features?" )
						->setPlaceholderText('[Y/N]')
						->setValidationFailedText( "Invalid keywords, we only accept [Y/N]" )
						->setValidator( function ( $input ) {
							return in_array( strtoupper( $input ), [ 'Y','N' ] );
						})
						->ask();
					
					if ( strtoupper( $result->fetch() ) === "Y" )
						$done = true;
				}
				else $done = true;
				
				if ( $done )
				{
					$menu->close();
					$this->start_installation( $selected );
				}
			});
			$menu->addItem( "Exit", function ( CliMenu $menu ) use ( &$selected )
			{
				$selected = [];
				$menu->close();
				$this->cmd->clear();
				$this->cmd->startup();
			});
			
			$menuBuilder = $menu->build();
			$menuBuilder->open();
		}
		
		function start_installation( array $selected = [] ): void
		{
			$this->cmd->title( '[ PROCESSING ]', 37 );

			# Create Tables
			$this->create_auth_table();
			$this->create_users_table();

			# Create Modal
			$this->create_auth_modal();
			$this->create_users_modal();

			# Create Rules
			$this->create_login_rules();
			$this->create_register_rules();

			# Create Controller
			$this->create_auth_controller();

			# Create Routes
			$this->create_auth_route();
			$this->create_admin_route();

			# Create User Interface
			$this->create_navbar();
			$this->create_template();
			$this->create_login_page();
			$this->create_register_page();
			$this->create_profile_page();
		}

		function create_auth_table(): bool 
		{	
			$content = "
<?php

	use Illuminate\Database\Facades\Blueprint;
	use Illuminate\Database\Facades\Migration;
	use Illuminate\Database\Facades\Schema;

	class CreateAuthTable extends Migration {

		protected string \$table_name = 'auth';
		
		public function up()
		{
			Schema::create( \$this->table_name, function( Blueprint \$table )
			{
				\$table->int( 'id' )->autoIncrement()->comment( 'Account identifier' );
				\$table->text( 'hash' )->collation( 'utf8mb4_unicode_ci' )->comment( 'User password' );
				\$table->timestamp( 'date_created' )->current_date()->comment( 'Account created date' );
				\$table->timestamp( 'date_updated' )->nullable()->on_update()->comment( 'Last account updated' );
				\$table->timestamp( 'date_accessed' )->nullable()->comment( 'Last account password accessed' );
			});
		}
	
		public function down()
		{
			Schema::dropIfExists( \$this->table_name );
		}
	}
";
			
			$file_name = "CreateAuthTable.php";
			$directory = getcwd()."\\app\\database\\migration";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_users_table(): bool 
		{	
			$content = "
<?php

	use Illuminate\Database\Facades\Blueprint;
	use Illuminate\Database\Facades\Migration;
	use Illuminate\Database\Facades\Schema;

	class CreateUsersTable extends Migration {

		protected string \$table_name = 'users';

		public function up()
		{
			Schema::create( \$this->table_name, function( Blueprint \$table )
			{
				\$table->int( 'user_id' )->autoIncrement()->comment( 'Account identifier' );
				\$table->string( 'email', 35 )->unique()->comment( 'User email address' );
				\$table->string( 'name' )->comment( 'Account name' );
				\$table->enum( 'email_verified', [ 1, 0 ] )->default( '0' )->comment( 'Email is verified' );
			});
		}

		public function down()
		{
			Schema::dropIfExists( \$this->table_name );
		}	
	}
";
			
			$file_name = "CreateUsersTable.php";
			$directory = getcwd()."\\app\\database\\migration";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_auth_modal(): bool 
		{
			$content = "
<?php

	namespace App\Model;
	use Illuminate\Database\Model;

	class Auth extends Model
	{
		protected string \$table = 'auth';
		protected array \$fillable = [
			'hash', 'date_accessed'
		];
	}
";
			
			$file_name = "Auth.php";
			$directory = getcwd()."\\app\\model";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_users_modal(): bool 
		{
			$content = "
<?php

	namespace App\Model;

	use Illuminate\Database\Model;

	class Users extends Model
	{
		protected string \$table = 'users';
		protected array \$fillable = [
			'user_id',
			'name',
			'email',
			'email_verified'
		];
		
		static function get_id_by_email( string \$email ) {
			return self::select( 'user_id' )->where( 'email', \$email )->field();
		}
		
		static function is_exist_by_id( int \$user_id ): int {
			return self::where( 'user_id', \$user_id )->count();
		}
		
		static function get_info( int \$user_id ): array
		{
			if ( !self::is_exist_by_id( \$user_id ) )
				return [];
			
			return [
				'email'	=>	self::select( 'email' )->where( 'user_id', \$user_id )->field(),
				'name'	=>	self::select( 'name' )->where( 'user_id', \$user_id )->field(),
				'date_created' => Auth::select( 'date_created' )->where( 'id', \$user_id )->field()
			];
		}
	}
";
			
			$file_name = "Users.php";
			$directory = getcwd()."\\app\\model";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_login_rules(): bool 
		{
			$content = "
<?php
	namespace App\Rules;

	use Core\Session;
	use Illuminate\Http\Request;

	class LoginRules extends Request
	{
		private static bool \$brute_force_protection = true;
		private static int \$maximum_attempt = 3;
		private static int \$suspend_duration = 60;
		
		public function rules(): array
		{
			return [
				'email'		=>	'required|email',
				'password'	=>	'required'
			];
		}
		
		public function authorize(): bool
		{
			\$grant = true;
			if ( self::\$brute_force_protection )
			{
				if ( !Session::has( 'auth' ) )
				{
					Session::put( 'auth', [
						'attempt'	=>	0,
						'microtime'	=>	microtime( true ),
					]);
				}
				
				\$auth = Session::get( 'auth' );
				if ( \$auth[ 'attempt' ] >= self::\$maximum_attempt )
				{
					\$current = microtime( true );
					\$duration = \$auth[ 'microtime' ] + self::\$suspend_duration;
					
					if ( \$current >= \$auth[ 'microtime' ] && \$current <= \$duration )
					{
						\$grant = false;
						\$this->add_error_message( 'default', 'Please try again after '.floor( \$duration - \$current ).' second/s.' );
					}
				}
			}
			
			if ( !\$grant )
				trace( 'Login submit form is blocked, due to brute force attack prevention.' );
			
			return \$grant;
		}
		
		public function increment_attempt(): void
		{
			if ( self::\$brute_force_protection )
			{
				\$auth = Session::get( 'auth' );
				if ( \$auth[ 'attempt' ] >= self::\$maximum_attempt )
				{
					\$current = microtime( true );
					\$duration = \$auth[ 'microtime' ] + self::\$suspend_duration;
					
					if ( !( \$current >= \$auth[ 'microtime' ] && \$current <= \$duration ) )
					{
						Session::put( 'auth', [
							'attempt'	=>	1,
							'microtime'	=>	microtime( true )
						]);
					}
				}
				else
				{
					Session::put( 'auth', [
						'attempt'	=>	\$auth[ 'attempt' ] + 1,
						'microtime'	=>	microtime( true )
					]);
				}
			}
			
			else trace( 'Brute force prevention is currently disabled.' );
		}
	}
";
			
			$file_name = "LoginRules.php";
			$directory = getcwd()."\\app\\requests";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_register_rules(): bool 
		{
			$content = "
<?php
	namespace App\Rules;

	use Illuminate\Http\Request;
	
	class RegisterRules extends Request
	{
		public function rules(): array
		{
			return [
				'name'		=>	'required|string',
				'email'		=>	'required|email|users:email| email {Invalid email address given [email].}',
				'password'	=>	'required|string|password|confirmed',
			];
		}
		
		public function authorize(): bool {
			
			return true;
		}
	}
";
			
			$file_name = "RegisterRules.php";
			$directory = getcwd()."\\app\\requests";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_auth_controller(): bool
		{
			$content = "
<?php

	namespace App\Controller;
	
	use Core\App;
	use Core\Session;
	
	use App\Model\{ Users, Auth as AuthModel };
	use App\Rules\{ RegisterRules, LoginRules };
	
	class Auth
	{
		protected static string \$account_prefix = 'account';
		
		/**
		 * Login Page
		 *
		 * @return void
		 */
		function login_page(): void
		{
			view( 'auth/login', [
				'errors' =>	session::get( 'errors', true )
			]);
		}
		
		/**
		 * Register Page
		 *
		 * @return void
		 */
		function register_page(): void
		{
			view( 'auth/register', [
				'errors' => session::get( 'errors', true )
			]);
		}
		
		/**
		 * Login Form Post
		 *
		 * @param LoginRules \$request
		 * @return void
		 */
		function login_post( LoginRules \$request ): void
		{
			\$result = \$request->response();
			
			if ( \$request->isSuccess() )
			{
				\$request->increment_attempt();
				\$email = \$request->input( 'email' );
				\$password = \$request->input( 'password' );
				
				if ( self::verify_credentials( \$email, \$password ) )
				{
					if ( self::put_online( Users::get_id_by_email( \$email ) ) )
					{
						Session::forget( 'auth' );
						
						if ( !session_regenerate_id( true ) )
							app::error( 'Failed to regenerate session ID' );
						
						redirect( 'dashboard' );
					}
				}
				else \$result[ 'default' ] = 'Wrong Email and Password Combination.';
			}
			
			redirect( 'login' )->with( 'errors', \$result );
		}
		
		/**
		 * Register Form Post
		 *
		 * @param RegisterRules \$request
		 * @return void
		 */
		function register_post( RegisterRules \$request ): void
		{
			if ( \$request->isSuccess() )
			{
				\$name = \$request->input( 'name' );
				\$email = \$request->input( 'email' );
				\$password = \$request->input( 'password' );
				
				\$id = AuthModel::create([ 'hash' => password_hash( \$password, PASSWORD_DEFAULT ) ]);
				
				Users::create([
					'user_id' => \$id,
					'email'	=> \$email,
					'name' => \$name,
					'email_verified' => 1
				]);
				
				if ( self::put_online( Users::get_id_by_email( \$email ) ) )
					redirect( 'dashboard' );
			}
			
			redirect( 'register' )->with( 'errors', \$request->response() );
		}
		
		/**
		 * Check username/email and password combination.
		 *
		 * @param string \$email
		 * @param string \$password
		 * @return bool
		 */
		static function verify_credentials( string \$email, string \$password ): bool
		{
			if ( \$user_id = Users::get_id_by_email( \$email ) )
			{
				\$hash = AuthModel::select( 'hash' )->where( 'id', \$user_id )->field();
				
				if ( password_verify( \$password, \$hash ) )
				{
					if ( password_needs_rehash( \$hash, PASSWORD_DEFAULT, \$options = [ 'cost' => 12 ] ) )
					{
						AuthModel::where( 'id', \$user_id )->update([
							'hash' => password_hash( \$password, PASSWORD_DEFAULT, \$options )
						]);
					}
					
					AuthModel::where( 'id', \$user_id )->update([
						'date_accessed'	=> date( 'Y-m-d H:i:s' )
					]);
					
					trace( 'Email and password combination is successfully matched.' )->store([
						'email'	=> \$email,
						'password' => str_repeat( '*', strlen( \$password ) )
					]);
					return true;
				}
			}
			
			trace( 'Email and password combination is failed.' )->store([
				'email'	=> \$email,
				'password' => str_repeat( '*', strlen( \$password ) )
			]);
			return false;
		}
		
		/**
		 * Register into session.
		 *
		 * @param int \$id
		 * @return bool
		 */
		static function put_online( int \$id ): bool
		{
			if ( Users::is_exist_by_id( \$id ) )
			{
				\$user_info = Users::get_info( \$id );
				Session::put( self::\$account_prefix, \$user_info );
				trace( 'Account is successfully registered in session.' )->store( \$user_info );
				return true;
			}
			
			return false;
		}
		
		/**
		 * Check if account is active in session.
		 *
		 * @param bool \$redirect
		 * @return bool
		 */
		static function is_active( bool \$redirect = true ): bool {
			
			if ( Session::has( self::\$account_prefix ) )
				return true;
			
			if ( \$redirect )
				redirect( 'login' );
			
			return false;
		}
		
		/**
		 * Check if account is not active in session.
		 *
		 * @param bool \$redirect
		 * @return bool
		 */
		static function is_not_active( bool \$redirect = true ): bool {
			
			if ( !Session::has( self::\$account_prefix ) )
				return true;
			
			if ( \$redirect )
				redirect( 'dashboard' );
			
			return false;
		}
		
		/**
		 * Logout the account
		 *
		 * @return void
		 */
		static function logout(): void
		{
			if ( self::is_active( false ) )
			{
				Session::forget( self::\$account_prefix );
				redirect( 'login' );
			}
		}
		
		/**
		 * Get active account info.
		 *
		 * @return array
		 */
		static function get_account(): array {
			
			if ( self::is_active( false ) )
				return Session::get( self::\$account_prefix );
			
			return [];
		}
	}
";
			
			$file_name = "Auth.php";
			$directory = getcwd()."\\app\\controllers";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_auth_route(): bool 
		{
			$content = "
<?php
	
	use Illuminate\Http\{Route};
	use App\Controller\Auth;
	
	Route::controller( auth::class )->group( function ()
	{
		Route::middleware( 'is_not_active' )->group( function()
		{
			Route::get( 'login', 'login_page' );
			Route::get( 'register', 'register_page' );
			
			Route::prefix( 'auth' )->group( function()
			{
				Route::post( 'login', 'login_post' );
				Route::post( 'register', 'register_post' );
			});
		});
	});
	
	Route::get( 'logout', [ Auth::class, 'logout' ])->middleware([ Auth::class, 'is_active' ]);
";
			
			$file_name = "auth.php";
			$directory = getcwd()."\\app\\routes";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_admin_route(): bool 
		{
			$content = "
<?php

	use Illuminate\Http\Route;
	use App\Controller\auth;

	Route::middleware([ auth::class, 'is_active' ])->group( function()
	{
		Route::view( 'dashboard', 'auth/profile', [ 'account' => auth::get_account() ]);
	});
";
			
			$file_name = "admin.php";
			$directory = getcwd()."\\app\\routes";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_navbar(): bool 
		{
			$content = "

	<nav class='navbar navbar-expand-lg navbar-light bg-white'>
	<div class='container'>
		<a class='navbar-brand' href='/'>Your Logo</a>
		<button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarNav' aria-controls='navbarNav' aria-expanded='false' aria-label='Toggle navigation'>
			<span class='navbar-toggler-icon'></span>
		</button>
		<div class='collapse navbar-collapse' id='navbarNav'>
			<ul class='navbar-nav ms-auto'>
				@auth(false);
					<li class='nav-item'>
						<a class='nav-link' href='/login'>Login</a>
					</li>
					<li class='nav-item'>
						<a class='nav-link' href='/register'>Register</a>
					</li>
				@end_auth;
				
				@auth(true);
					<li class='nav-item'>
						<a class='nav-link' href='/logout'>Logout</a>
					</li>
				@end_auth;
			</ul>
		</div>
	</div>
</nav>
";
			
			$file_name = "navbar.blade.php";
			$directory = getcwd()."\\app\\views\\auth\\includes";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_template(): bool 
		{
			$content = "
<!doctype html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
	<title>@config('APP_NAME');</title>
	<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet' crossorigin='anonymous'>
	<style>
		body {
			background-color: #f8f9fa;
		}
		.login-container, .register-container {
			max-width: 400px;
			margin: 50px auto auto;
		}
		.login-form, .register-form {
			background-color: #fff;
			padding: 20px;
			border-radius: 5px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
		}
	</style>
</head>
<body>
	@import('auth/includes/navbar');
	@container;
	<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js' crossorigin='anonymous'></script>
</body>
</html>
";
			
			$file_name = "template.blade.php";
			$directory = getcwd()."\\app\\views\\auth\\includes";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_login_page(): bool 
		{
			$content = "

@template('auth/includes/template');

<form method='post' action='auth/login'>
	
	@token(true);
	
	<div class='login-container'>
		<div class='login-form'>
			<h2 class='mb-4'>Login</h2>
			<div class='mb-3'>
				<label for='email' class='form-label'>Email Address</label>
				<input type='email' class='form-control' id='email' name='email' autocomplete='username' value='@input('email')'>
				<span class='text-danger'>
					<small>{{ \$errors[ 'email' ] ?? ''  }}</small>
				</span>
			</div>
			<div class='mb-3'>
				<label for='password' class='form-label'>Password</label>
				<input type='password' class='form-control' id='password' name='password' autocomplete='current-password' value='@input('password')'>
				<span class='text-danger'>
					<small>{{ \$errors[ 'password' ] ?? ''  }}</small>
				</span>
			</div>
			<button type='submit' class='btn btn-primary'>Sign In</button>
		</div>
		<br/>
		<center>
			<span class='text-danger'>{{ \$errors[ 'default' ] ?? '' }}</span>
		</center>
	</div>
	
</form>
";
			
			$file_name = "login.blade.php";
			$directory = getcwd()."\\app\\views\\auth";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_register_page(): bool 
		{
			$content = "

@template('auth/includes/template');

<form method='post' action='auth/register'>
	
	@token(true);
	
	<div class='register-container'>
		<div class='register-form'>
			<h2 class='mb-4'>Register</h2>
			<form>
				<div class='mb-3'>
					<label for='name' class='form-label'>Full Name</label>
					<input type='text' class='form-control' id='name' name='name' autocomplete='name' value='@input('name')'>
					<span class='text-danger'>
						<small>{{ \$errors[ 'name' ] ?? ''  }}</small>
					</span>
				</div>
				<div class='mb-3'>
					<label for='email' class='form-label'>Email Address</label>
					<input type='email' class='form-control' id='email' name='email' autocomplete='email' value='@input('email')'>
					<span class='text-danger'>
						<small>{{ \$errors[ 'email' ] ?? ''  }}</small>
					</span>
				</div>
				<div class='mb-3'>
					<label for='password' class='form-label'>Password</label>
					<input type='password' class='form-control' id='password' name='password' autocomplete='current-password' value='@input('password')'>
					<span class='text-danger'>
						<small>{{ \$errors[ 'password' ] ?? ''  }}</small>
					</span>
				</div>
				<div class='mb-3'>
					<label for='confirm_password' class='form-label'>Password</label>
					<input type='password' class='form-control' id='confirm_password' name='confirm_password' autocomplete='current-password' value='@input('confirm_password')'>
					<span class='text-danger'>
						<small>{{ \$errors[ 'confirm_password' ] ?? ''  }}</small>
					</span>
				</div>
				<button type='submit' class='btn btn-primary'>Sign Up</button>
			</form>
		</div>
		<br/>
		<center>
			<span class='text-danger'>{{ \$errors[ 'default' ] ?? '' }}</span>
		</center>
	</div>

</form>	
";
			
			$file_name = "register.blade.php";
			$directory = getcwd()."\\app\\views\\auth";
			$directory = str_replace( '\\', '/', $directory );
			
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$file_name" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Already exist with the given path ($directory/$file_name)." );
					return false;
				}
				
				$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
				fwrite( $file, trim( $content ) );
				fclose( $file );
			}
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}

		function create_profile_page(): bool 
		{
			$content = "
@template('auth/includes/template');

<h1 class='text-center pt-5'>Welcome {{ \$account[ 'name' ] }}</h1>
";
			
			$file_name = "profile.blade.php";
			$directory = getcwd()."\\app\\views\\auth";
			$directory = str_replace( '\\', '/', $directory );
			
			$file = fopen( "$directory/$file_name", 'w' ) or die( 'Cannot open file: ' . $file_name );
			fwrite( $file, trim( $content ) );
			fclose( $file );
			
			$this->cmd->info( "Successfully created, given path ($directory/$file_name)." );
			return true;
		}
	}