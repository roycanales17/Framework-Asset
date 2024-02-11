<?php
	
	namespace App\Terminal\Make;
	
	class Table {
		
		protected mixed $cmd;
		
		function __construct( $artisan, $args )
		{
			$this->cmd = $artisan;
			$this->create( $args );
		}
		
		function create( string $name ): bool
		{
			$name 		= 	str_replace( '\\', '/', $name );
			$exploded 	= 	explode( '/', $name );
			$class_name = 	"Create".ucfirst( $exploded[ count( $exploded ) - 1 ] )."Table";
			$filename 	= 	"$class_name.php";
			
			$content 	 =	'<?php'.PHP_EOL.PHP_EOL;
			$content	.=	"\tuse Illuminate\Database\Facades\Blueprint;".PHP_EOL;
			$content	.=	"\tuse Illuminate\Database\Facades\Migration;".PHP_EOL;
			$content	.=	"\tuse Illuminate\Database\Facades\Schema;".PHP_EOL.PHP_EOL;
			$content 	.=	"\tclass $class_name extends Migration".PHP_EOL;
			$content 	.=	"\t{".PHP_EOL;
			$content 	.=	"\t\t".'protected string $table_name = "'. strtolower( $exploded[ count( $exploded ) - 1 ] ) .'";'.PHP_EOL.PHP_EOL;
			$content 	.=	"\t\tpublic function up()".PHP_EOL;
			$content 	.=	"\t\t{".PHP_EOL;
			$content 	.=	"\t\t\t".'# Schema::create( $this->table_name, function( Blueprint $table ) {'.PHP_EOL;
			$content 	.=	"\t\t\t".'# });'.PHP_EOL;
			$content 	.=	"\t\t}".PHP_EOL;
			$content 	.=	"\t\t".PHP_EOL;
			$content 	.=	"\t\tpublic function down()".PHP_EOL;
			$content 	.=	"\t\t{".PHP_EOL;
			$content 	.=	"\t\t\t".'# Schema::dropIfExists( $this->table_name );'.PHP_EOL;
			$content 	.=	"\t\t}".PHP_EOL;
			$content 	.=	"\t}";
			
			$directory = getcwd()."/app/database/migration/";
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
					$this->cmd->info( "Migrate file already exist ($directory/$filename)." );
					return false;
				}
				
				$file = fopen( "$directory/$filename", 'w' ) or die( 'Cannot open file: '.$directory.$filename );
				fwrite( $file, $content );
				fclose( $file );
			}
			
			$this->cmd->title( 'SUCCESS', 32 );
			$this->cmd->info( "Successfully created new migrate file, given path ($directory/$filename)." );
			return true;
		}
	}