<?php
	
	namespace App\Terminal\Clear;
	
	class Logs {
		
		protected mixed $cmd;
		protected int $total_removed = 0;
		
		function __construct( $artisan )
		{
			$this->cmd = $artisan;
			$this->remove( getcwd()."/app/config/logs" );
		}
		
		function remove( string $path ): void
		{
			$this->cmd->title( '[ PROCESSING ]', 37 );
			
			if ( $this->remove_files( $path ) )
			{
				if ( !$this->total_removed )
				{
					$this->cmd->title( 'ERROR', 31 );
					$this->cmd->info( "No files were removed, the directory is empty." );
				}
				else
				{
					$this->cmd->title( 'SUCCESS', 32 );
					$this->cmd->info( "A total of ".$this->total_removed." files is successfully removed!" );
				}
			}
			
			$this->total_removed = 0;
		}
		
		private function remove_files( string $dir = '' ): bool
		{
			$tab = "  ";
			if ( !is_dir( $dir ) )
			{
				$this->cmd->title( 'ERROR', 31 );
				$this->cmd->info( 'Directory does not exist.' );
				return false;
			}
			
			$files = array_diff( scandir( $dir ), ['.', '..'] );
			
			foreach ( $files as $file )
			{
				$path = $dir . '/' . $file;
				if ( is_dir( $path ) ) {
					$this->remove_files( $path );
				}
				else
				{
					$this->total_removed = $this->total_removed + 1;
					echo "$tab- Deleted: Size " . filesize( $path ) . " bytes | $path\n";
					unlink( $path );
				}
			}
			
			return true;
		}
	}