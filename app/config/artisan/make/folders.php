<?php
	
	namespace App\Terminal\Make;
	
	class Folders {
		
		protected mixed $cmd;
		
		function __construct( $artisan, $args )
		{
			$this->cmd = $artisan;
			$this->create();
		}
	
		function create() 
		{
			$directories = [
				"app/controllers",
				"app/database/migration",
				"app/database/seeds",
				"app/model",
				"app/requests",
				"app/routes",
				"app/views",

				"app/config/cache",
				"app/config/logs"
			];

			$root = getcwd();

			$this->cmd->title( '[ PROCESSING ]', 37 );

			foreach( $directories as $path ) 
			{	
				if ( !file_exists( $folder_path = "$root/$path" ) ) 
				{
					mkdir( $folder_path , 0755, true );
					$this->cmd->info( "Directory ($folder_path) is successfully created." );
				}
			}

			$this->cmd->title( 'SUCCESS', 32 );
			$this->cmd->info( "Creating folders is successfully done." );

			return true;
		}
	}