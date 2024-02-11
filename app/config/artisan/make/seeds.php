<?php
	
	namespace App\Terminal\Make;
	
	class Seeds {
		
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
			$class_name = 	"Table".ucfirst( $exploded[ count( $exploded ) - 1 ] )."Seeders";
			$filename 	= 	"$class_name.php";
			
			$content 	 =	'<?php'.PHP_EOL.PHP_EOL;
			$content	.=	"\tuse Illuminate\Database\Facades\Seeder;".PHP_EOL.PHP_EOL;
			$content 	.=	"\tclass $class_name extends Seeder".PHP_EOL;
			$content 	.=	"\t{".PHP_EOL;
			$content 	.=	"\t\tpublic function run()".PHP_EOL;
			$content 	.=	"\t\t{".PHP_EOL;
			$content 	.=	"\t\t\t# Add here the model class".PHP_EOL;
			$content 	.=	"\t\t}".PHP_EOL;
			$content 	.=	"\t\t".PHP_EOL;
			$content 	.=	"\t}";
			
			$directory = getcwd()."/app/database/seeds/";
			$directory = str_replace( '\\', '/', $directory );
			
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
					$this->cmd->info( "Seeds file already exist ($directory/$filename)." );
					return false;
				}
				
				$file = fopen( "$directory/$filename", 'w' ) or die( 'Cannot open file: '.$directory.$filename );
				fwrite( $file, $content );
				fclose( $file );
			}
			
			$this->cmd->title( 'SUCCESS', 32 );
			$this->cmd->info( "Successfully created new seeds file, given path ($directory/$filename)." );
			return true;
		}
	}