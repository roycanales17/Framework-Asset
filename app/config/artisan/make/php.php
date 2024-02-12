<?php
	
	namespace App\Terminal\Make;
	
	class Php {
		
		protected mixed $cmd;
		
		function __construct( $artisan, $args )
		{
			$this->cmd = $artisan;
            $this->install_HOMEBREW();
			$this->install_PHP();
		}
	
        function install_HOMEBREW()
        {
            exec( 'brew --version', $output, $exitCode );

            if ( $exitCode === 0 )
            {
                $this->cmd->title( 'SUCCESS', 32 );
                $this->cmd->info( "HOMEBREW is already installed." );
            }
            else 
            {
                $output = [];
                exec( '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"', $output, $exitCode );

                if ( $exitCode === 0 )  
                {
                    $this->cmd->title( 'ERROR', 31 );
                    $this->cmd->info( 'Failed to install Homebrew.' );

                    foreach ( $output as $line )
                        $this->cmd->info( $line );
                }
                else 
                {
                    $this->cmd->title( 'SUCCESS', 32 );
                    $this->cmd->info( "Homebrew installed successfully." );
                }
            }
        }

		function install_PHP() 
		{
			$this->cmd->title( 'PROCESSING', 37 );
            $os = $this->detect_OS();

            switch ( $os ) 
            {
                case 'macos':

                    $output = [];
                    $exitCode = 0;
                    exec( 'php -v | grep "^PHP 8"', $output, $exitCode);

                    if ( $exitCode === 0 && count( $output ) > 0 )
                    {
                        $this->cmd->title( 'SUCCESS', 32 );
                        $this->cmd->info( "PHP 8 is already installed." );
                    }
                    else 
                    {
                        // Install PHP using Homebrew
                        $installCommand = 'brew install php@8.0';
                        
                        // Execute the command
                        $output = [];
                        $exitCode = 0;
                        exec( $installCommand, $output, $exitCode );

                        // Check if installation was successful
                        if ( $exitCode === 0 )
                        {
                            $this->cmd->title( 'SUCCESS', 32 );
                            $this->cmd->info( "PHP 8 installed successfully." );
                        } 
                        else 
                        {
                            $this->cmd->title( 'ERROR', 31 );
                            $this->cmd->info( 'Failed to install PHP 8.' );

                            // Output any error messages
                            foreach ( $output as $line )
                                $this->cmd->info( $line );
                        }
                    }

                    break;

                case 'linux':    
                case 'windows':
                    $this->cmd->title( 'INFO', 31 );
                    $this->cmd->info( 'Currently not available.' );
                    break;

                default:
                    $this->cmd->title( 'ERROR', 31 );
                    $this->cmd->info( 'Unsupported operating system.' );
                    break;
            }

			return true;
		}

        function detect_OS() 
        {
            $os = strtolower( PHP_OS );

            if ( strpos( $os, 'linux') !== false )
                return 'linux';

            elseif ( strpos($os, 'darwin') !== false )
                return 'macos';

            elseif ( strpos($os, 'win') !== false )
                return 'windows';

            else 
                return 'unknown';
        }
	}