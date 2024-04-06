<?php
	
	use Core\{Config, App, Session, temp, trace};
	use Illuminate\Http\{Request, Route, Compose};
	use JetBrains\PhpStorm\NoReturn;
	use Illuminate\Database\db;
	
	function decrypt( $string ): bool|string
	{
		$result = @gzuncompress( $string );
		if ( $result === false )
			return false;
		
		return $result;
	}
	
	function encrypt( $string ): string {
		return gzcompress( $string );
	}
	
	function esc( string $string, bool $trim = true ): array|string|null
	{
		$string = str_replace( [ "\\", "\x00", "\n", "\r", "'", '"', "\x1a" ], [ "\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z" ], $string );
		
		if ( $trim )
			$string = trim( $string );
		
		return $string;
	}
	
	function view( string $content, mixed $data = false, $status = 200, $headers = [] ): \Illuminate\Http\Compiler {
		return Route::view( Request::url()->full[ 'path' ], $content, $data, $status, $headers );
	}
	
	function redirect( string $to = "", int $code = 302 ): \Illuminate\Http\Compiler2 {
		return Route::redirect( Request::url()->full[ 'path' ], $to, $code );
	}
	
	function compose( string $key ): Compose {
		return compose::fragment( $key );
	}
	
	function query( string $sql, array|\Closure $closure = [] ): \Illuminate\Database\db {
		return db::run( $sql, $closure );
	}
	
	function config( string $name ): bool|string|array|null|int {
		return Config::get( $name );
	}
	
	function session( string $name ): mixed {
		return Session::get( $name );
	}
	
	function token( bool $html = false ): string
	{
		$token = Session::get( '$_csrf-token' );
		
		if ( $html )
			return '<input type="hidden" name="token" value="'.$token.'">';
		
		return $token;
	}
	
	function json( $data, $options = JSON_PRETTY_PRINT, $depth = 512, $return = false ): string
	{
		if ( !is_int( $options ) ) app::error( "JSON encoding options are invalid." );
		if ( !is_int( $depth ) ) app::error( "JSON encoding depth is invalid." );
		
		if ( $return === true )
		{
			$json = json_encode( $data, $options, $depth );
			
			if ( json_last_error() !== JSON_ERROR_NONE )
				app::error( "Something went wrong from converting data into json." );
			
			return $json;
		}
		
		ob_start();
		print( json_encode( $data, $options, $depth ) );
		$json = ob_get_contents();
		ob_end_clean();
		
		if ( json_last_error() !== JSON_ERROR_NONE )
			app::error( "Something went wrong from converting data into json." );
		
		print( $json );
		Request::exit();
	}
	
	function dump( mixed ...$print ): void
	{
		if ( config( 'ARTISAN' ) ) {
			foreach ( $print as $n ) {
				print_r( $n );
			}
		}
		else
		{
			if ( config( 'DEVELOPMENT' ) ) printInfo();
			
			default_style();
			print "<div class='trace-container'><pre>";
			foreach ( $print as $n )
			{
				switch ( true )
				{
					case is_bool( $n ):
					case is_int( $n ):
					case is_null( $n ):
						var_dump( $n );
						break;
					
					case is_string( $n ):
						echo htmlspecialchars( "$n" );
						print( "<br/>" );
						break;
					
					case is_object( $n ):
						$reflection = new ReflectionFunction( $n );
						$code = file($reflection->getFileName());
						$startLine = $reflection->getStartLine();
						$endLine = $reflection->getEndLine();
						$codeSnippet = implode("", array_slice( $code, $startLine - 1, $endLine - $startLine + 1 ) );
						print( "<pre>".trim( $codeSnippet )."</pre>" );
						print( "<br/>" );
						break;
					
					case is_array( $n ):
						$prettyOutput = nl2br( print_r( $n, true ) );
						print( "$prettyOutput" );
						print( "<br/>" );
						break;
				}
			}
			print "</pre></div>";
		}
	}
	
	function generateRandomString( int $length = 10 ): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen( $characters );
		$randomString = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$randomString .= $characters[ random_int( 0, $charactersLength - 1 ) ];
		}
		return $randomString;
	}
	
	function microsecondsToHMSM( float $microtime ): string
	{
		$seconds = floor( $microtime );
		$microseconds = ( $microtime - $seconds ) * 1000;
		
		$minutes = floor($seconds / 60);
		$seconds %= 60;
		
		$milliseconds = round( $microseconds );
		
		return sprintf("%02d:%02d.%04d", $minutes, $seconds, $milliseconds);
	}
	
	function closureMethod( \Closure $closure ): mixed
	{
		$reflection = new ReflectionFunction( $closure );
		$args = classInstanceParams( $reflection->getParameters() );
		
		return $closure( ...$args );
	}
	
	function classMethod( object|string $class, string $classMethod )
	{
		switch ( true )
		{
			case !is_object( $class ) && !class_exists( $class ):
				app::error( "`$class` className is not exist." );
				break;
			
			case !is_object( $class ) && !method_exists( $class , $classMethod ):
				app::error( "Function `$classMethod` method is not exist from the class ($class)." );
				break;
		}
		
		$obj = new stdClass();
		$obj->class = is_object( $class ) ? $class : new $class();
		$obj->reflection = new ReflectionMethod( $class, $classMethod );
		$args = classInstanceParams( $obj->reflection->getParameters() );

        foreach ( $args as $params_attr ) {
            if ( is_object( $params_attr ) ) {
                $className = get_class( $params_attr );
                if ( str_starts_with( $className, 'App\Rules\\' ) ) {
                    $req = new $className();
                    $req->validate_instance();
                }
            }
        }

		return call_user_func([ $obj->class, $classMethod ], ...$args );
	}
	
	function classInstanceParams( array $parameters = [] ): array
	{
		$params = $args = [];
		foreach ( $parameters as $parameter )
		{
			$type = $parameter->getType();
			if ( $type !== null ) {
				$params[] = [
					'class'		=>	$type->getName(),
					'variable'	=>	$parameter->getName()
				];
			}
		}
		
		for ( $i = 0; $i < count( $params ); $i++ )
		{
			$obj = $params[ $i ];
			if ( class_exists( $obj[ 'class' ] ) )
				$args[] = new $obj[ 'class' ]();
		}
		
		return $args;
	}
	
	function getChildClass( string $abstract_class ): array
	{
		$child_class = [];
		foreach ( get_declared_classes() as $className )
		{
			$class = new ReflectionClass( $className );
			if ( $class->isSubclassOf( $abstract_class ) ) {
				$child_class[] = $className;
			}
		}
		
		return $child_class;
	}
	
	function trace( $message, $slice = 2 ): temp {
		return new temp( $message, $slice );
	}
	
	function default_style(): void {
		print( "<link rel='stylesheet' href='".config( "APP_URL" )."/resources/".config( "STYLE" )."'>" );
	}

	function exitTrace(): void
	{
		ob_start();
		$tracks = trace::tracks( 'trace' );
		$recent = trace::tracks( 'recent' );
		echo "\n";
		default_style();
		?>
		<!-- TRACK SECTION -->
		<table class="tracks shadow" cellpadding="15">
			<thead>
			<tr>
				<th>Info</th>
				<th>File</th>
				<th>Line</th>
				<th>Duration</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody id="trace-debug-table">
			<?php
				$microseconds = 0;
				for ( $i = 0; $i < count( $tracks ); $i++ )
				{
					$obj = $tracks[ $i ];
					$microseconds += $obj[ 'microseconds' ];
					?>
					<tr>
						<td><?=$obj[ 'info' ]?></td>
						<td><?=$obj[ 'file' ]?></td>
						<td class="text-center"><?=$obj[ 'line' ]?></td>
						<td class="text-center"><?=$obj[ 'duration' ]?></td>
						<td>
							<center>
								<button
									type="button"
									class="default-button"
									onclick='show( `debug-info`, `#track-id-<?=$i?>` ).setBackDrop().setPosition( `center-top` ).setCSS([ `tracks-alert` ])'
									<?= $obj[ 'details' ] ? '' : 'disabled' ?> >
									Details
								</button>
							</center>
							<div class="display-none" id="track-id-<?=$i?>">
								<pre style="white-space: pre-wrap;"><?= print_r( $obj[ 'details' ], true ) ?></pre>
								<input
									type="button"
									class="default-button w-100"
									value="Close"
									onclick="hide( 'debug-info' )"
								/>
							</div>
						</td>
					</tr>
					<?php
				}
			?>
			</tbody>
			<tbody id="recent-debug-table" class="display-none">
			<?php
				if ( $recent )
				{
					$microseconds = 0;
					for ( $i = 0; $i < count( $recent ); $i++ )
					{
						$obj = $recent[ $i ];
						$microseconds += $obj[ 'microseconds' ];
						?>
						<tr>
							<td><?=$obj[ 'info' ]?></td>
							<td><?=$obj[ 'file' ]?></td>
							<td class="text-center"><?=$obj[ 'line' ]?></td>
							<td class="text-center"><?=$obj[ 'duration' ]?></td>
							<td>
								<center>
									<button
										type="button"
										class="default-button"
										onclick='show( `debug-info`, `#track-id-<?=( $i + count( $tracks ) )?>` ).setBackDrop().setPosition( `center-top` ).setCSS([ `tracks-alert` ])'
										<?= $obj[ 'details' ] ? '' : 'disabled' ?> >
										Details
									</button>
								</center>
								<div class="display-none" id="track-id-<?=( $i + count( $tracks ) )?>">
									<pre style="white-space: pre-wrap;"><?= print_r( $obj[ 'details' ], true ) ?></pre>
									<input
										type="button"
										class="default-button w-100"
										value="Close"
										onclick="hide( 'debug-info' )"
									/>
								</div>
							</td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
						<td colspan="5">No recent tracks</td>
					</tr>
					<?php
				}
			?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="4">
					Time Scaled: &nbsp;&nbsp;<b><?= microsecondsToHMSM( microtime( true ) - START_TIME  ) ?></b> <span>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
					Memory Usage: <b><?= ( round(memory_get_usage( true ) / 1024 / 1024, 2) ) ?> MB</b><span>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
					PHP Version: <b><?= phpversion() ?></b>
				</td>
				<td>
					<center>
						<select onchange="update_trace(this)">
							<option value="trace" selected>Current</option>
							<option value="recent">Recent</option>
						</select>
					</center>
				</td>
			</tr>
			</tfoot>
		</table>
		<script>
			function update_trace( e )
			{
				let current = document.getElementById( `trace-debug-table` ),
					recent = document.getElementById( `recent-debug-table` );
				
				if ( e.value === 'trace' ) {
					recent.classList.add( 'display-none' );
					current.classList.remove( 'display-none' );
				} else {
					current.classList.add( 'display-none' );
					recent.classList.remove( 'display-none' );
				}
			}
		</script>
		<?php
		
		$content = ob_get_contents();
		ob_end_clean();
		
		print $content;
	}
	
	function printInfo( float $font_size = 11, float $padding = 1.5 ): void
	{
		ob_start();
		$trace = trace::register( 'dump', 3 );
		$format = explode( ':', $trace[ 'duration' ] );
		?>
		<table class="dump" cellpadding="<?=$padding?>px" style="font-size: <?=$font_size?>px;">
			<tr>
				<td class="title" width="7%">Location</td>
				<td class="definition"><?=$trace[ 'file' ]?></td>
			</tr>
			<tr>
				<td class="title" width="7%">Line</td>
				<td class="line"><?=$trace[ 'line' ]?></td>
			</tr>
			<tr>
				<td class="title" width="7%">Time Scale</td>
				<td class="time"><?=$format[0]?> : <?=$format[1]?> : <?=$format[2]?> &nbsp;&nbsp;( minutes : seconds : milliseconds )</td>
			</tr>
		</table>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		print $content;
	}
	
	function create_cache( $content ): array
	{
		$cacheFile 		=	generateRandomString();
		$contentPath 	=	config( "CACHE_PATH" )."/$cacheFile.php";
		
		# Initialize the resources
		$contentResources = fopen( $contentPath  , "w" );
		
		# Append the unique identifier
		$namespace = "<?php namespace App\Cache; ?>";
		fwrite( $contentResources, $namespace );
		
		# Write the content
		fwrite( $contentResources, $content );
		
		# Close
		fclose( $contentResources );
		
		# Change permissions
		chmod( $contentPath, 0666 );
		
		return [
			'filename' => $cacheFile,
			'path' => $contentPath
		];
	}
	
	function serialize_uri( ?string $uri ): string
	{
		if ( $uri === null || $uri === '' ) {
			return '';
		}
		
		if ( $uri[0] !== '/' ) {
			$uri = '/' . $uri;
		}
		
		if ( $uri[-1] !== '/' ) {
			$uri .= '/';
		}
		
		if ( str_ends_with( $uri, '/' ) ) {
			$uri = substr( $uri, 0, -1 );
		}
		
		return $uri;
	}
	
	function remove_file( string $path, int $permissions = 0777 ): bool
	{
		if ( file_exists( $path ) && is_file( $path ) ) {
			
			chmod( $path, $permissions );
			unlink( $path );
			
			return true;
		}
		
		return false;
	}
	
	function load_content( string $full_path, bool $remove = false, $trace_path = '' ): array
	{
		$content = "";
		if ( file_exists( $full_path ) )
		{
			$cache = create_cache( file_get_contents( $full_path ) );
		
			if ( $remove )
				remove_file( $full_path );
			
			try
			{
				ob_start();
				
				$variables = compose( 'global_variables' )->all();
				
				if ( $variables )
					extract( $variables );
				
				require_once $cache[ 'path' ];
				$content = ob_get_contents();
				
				ob_end_clean();
			}
			catch ( \Throwable | \Exception | \Error $e )
			{
				remove_file( $cache[ 'path' ] );
				dump([
					'path'		=>	$trace_path ?: $full_path,
					'message'	=>	$e->getMessage(),
					'line'		=>	$e->getLine(),
					'trace'		=>	$e->getTrace()
				]);
				stop();
			}
			finally
			{
				remove_file( $cache[ 'path' ] );
				if ( $remove_paths = Session::get( '$_remove_paths' ) )
				{
					foreach ( $remove_paths as $path ) {
						if ( file_exists( $path ) )
							remove_file( $path );
					}
				}
				Session::forget( '$_remove_paths' );
			}
		}
		else app::error( "Cannot load the content since the file is not exist given ($full_path)." );
		
		$cache[ 'content' ] = $content;
		return $cache;
	}
	
	function is_authenticated( bool $opt ): bool
	{
		$status = \App\Controller\Auth::is_active( false );
		
		if ( !$opt )
			return !$status;
		
		return $status;
	}
	
	#[NoReturn] function stop( mixed $message = 'End Script!' ): void {
		Request::exit( $message );
	}