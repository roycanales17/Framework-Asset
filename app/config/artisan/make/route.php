<?php
	
	namespace App\Terminal\Make;
	
	class Route {
		
		protected mixed $cmd;
		
		function __construct( $artisan, $args )
		{
			$this->cmd = $artisan;
			$this->create( $args );
		}
		
		function create( string $name ): bool
		{
			$name = str_replace( '\\', '/', $name );
			$exploded = explode( '/', $name );
			$filename = $exploded[ count( $exploded ) - 1 ].".php";
			
			array_pop( $exploded );
			
			$content 	 =	'<?php'.PHP_EOL.PHP_EOL;
			$content	.=	"\tuse Illuminate\Http\Route;";
			
			$directory = getcwd()."/app/routes/".implode( "/", $exploded );
			if ( !file_exists( $directory ) )
			{
				mkdir( $directory, 0755, true );
				$file = fopen( "$directory/$filename", 'w' ) or die( 'Cannot open file: '.$directory.$filename );
				fwrite( $file, $content );
				fclose( $file );
			}
			else
			{
				if ( file_exists( "$directory/$filename" ) )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "Route file already exist ($directory/$filename)." );
					return false;
				}
				
				$file = fopen( "$directory/$filename", 'w' ) or die( 'Cannot open file: '.$directory.$filename );
				fwrite( $file, $content );
				fclose( $file );
			}
			
			$this->cmd->title( 'SUCCESS', 32 );
			$this->cmd->info( "Successfully created new route file, given path ($directory/$filename)." );
			return true;
		}
	}