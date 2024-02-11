<?php
	namespace App\Terminal\db;
	
	use PhpSchool\CliMenu\Builder\CliMenuBuilder;
	use PhpSchool\CliMenu\CliMenu;
	
	class Migrate {
		
		protected mixed $cmd;
		
		function __construct( $artisan, $args )
		{
			$this->cmd = $artisan;
			$this->run( $args );
		}
		
		function run( null|bool $force = false )
		{
			$tables = getChildClass( 'Illuminate\Database\Facades\Migration' );
			
			if ( count( $tables ) )
			{
				if ( $force )
				{
					$this->cmd->title( 'PROCESSING', 37 );
					
					foreach ( $tables as $className )
					{
						$this->cmd->print( 'Migrating `'.$className.'` table...' );
						$obj = new $className();
						$obj->down();
						$obj->up();
					}
					
					$this->cmd->title( 'SUCCESS', 32 );
					$this->cmd->info( 'Migrate is successfully done.' );
				}
				else
				{
					$selected = [];
					$menu = new CliMenuBuilder();
					
					$menu->setTitle( "Please select below which table to migrate:" );
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
					
					foreach ( $tables as $table ) {
						$menu->addCheckboxItem( "$table", $callable );
					}
					
					$menu->addLineBreak( ' ' );
					$menu->addItem('Migrate', function ( CliMenu $menu ) use ( &$selected )
					{
						$menu->close();
						
						$this->cmd->title( 'PROCESSING', 37 );
						
						foreach ( $selected as $className )
						{
							$this->cmd->print( 'Migrating `'.$className.'` table...' );
							$obj = new $className();
							$obj->down();
							$obj->up();
						}
						
						$selected = [];
						$this->cmd->title( 'SUCCESS', 32 );
						$this->cmd->info( 'Migrate is successfully done.' );
						$this->cmd->print([
							[ "command", "View the list of commands." ],
							[ "help", "Not available for the meantime." ],
							[ "exit", "Terminate the session." ]
						]);
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
			}
			else
			{
				$this->cmd->title( 'SUCCESS', 32 );
				$this->cmd->info( 'No tables found during migration process.' );
			}
		}
	}