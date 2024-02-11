<?php
	
	namespace Illuminate\Database;
	
	class Reader
	{
		private array $matched_string = [];
		protected array $groupings = [ 'SELECT', 'FROM', 'WHERE', 'LIMIT', 'OFFSET' ];
		protected array $where_bridge = [ 'AND', 'OR', 'IN' ];
		protected array $excluded_strings = [ ',' ];
		
		private array $sql_status = [];
		private array $active_queries = [];
		private array $sub_queries = [];
		private array $queries = [];
		private array $query = [
			'active' => '',
			'type' => '',
			'index' => 0
		];
		
		private array $where_grouped = [];
		private array $where_column = [];
		
		function __construct( string $query )
		{
			# Cleaning
			$query = $this->clean( $query );
			$query = $this->format( $query );
			
			# Explode all strings
			preg_match_all('/\S+/', $query, $exploded );
			
			# Start the progress
			$this->analyzeNow( $exploded[ 0 ] );
			
			# For testing
			dump( $query );
		}
		
		protected function analyzeNow( array $array_string ): void
		{
			for ( $i = 0; $i < count( $array_string ); $i++ )
			{
				if ( in_array( $string = trim( $array_string[ $i ] ), $this->excluded_strings ) )
					continue;
				
				if ( $this->getActiveAction() != 'WHERE' && in_array( $string, [ '(', ')' ] ) )
					continue;
				
				if ( in_array( strtoupper( $string ), $this->groupings ) )
				{
					switch ( strtoupper( $string ) )
					{
						case 'SELECT':
							if ( !$this->countParentQueries() )
							{
								$this->createNewQuery( 'PARENT' );
								$this->setActiveQuery([
									'active' => 'SELECT',
									'type' => 'PARENT',
									'index' => 0
								]);
								$this->updateStatus( 'processing' );
							}
							else
							{
								switch ( $this->getActiveAction() )
								{
									case 'SELECT':
										if ( !$this->checkIfDone( 'SELECT' ) )
										{
											$this->insertString( 'subquery_'.$this->countChildrenQueries() );
											$this->createNewQuery( 'CHILDREN' );
											$this->setActiveQuery([
												'active' => 'SELECT',
												'type' => 'CHILDREN',
												'index' => $this->countChildrenQueries() - 1
											]);
											$this->updateStatus( 'processing' );
										}
										break;
									
									case 'WHERE':
										$this->insertString( 'subquery_'.$this->countChildrenQueries(), '', true );
										$this->createNewQuery( 'CHILDREN' );
										$this->setActiveQuery([
											'active' => 'SELECT',
											'type' => 'CHILDREN',
											'index' => $this->countChildrenQueries() - 1
										]);
										$this->updateStatus( 'processing' );
										break;
								}
							}
							break;
							
						case 'FROM':
							$this->updateStatus( 'done' );
							if ( $this->getActiveAction() == 'WHERE' )
							{
								$recent = $this->getRecentQuery();
								
								# Mark as done the current active query.
								$this->setQueryFinished();
								
								# Mark as done the recent `SELECT` action.
								$this->setActiveQuery([
									'active' => 'SELECT',
									'type' => $recent[ 'type' ],
									'index' => $recent[ 'index' ]
								]);
								$this->updateStatus( 'done' );
								
								# Set the new active query to process.
								$this->setActiveQuery([
									'active' => 'FROM',
									'type' => $recent[ 'type' ],
									'index' => $recent[ 'index' ]
								]);
							}
							else
							{
								$this->setActiveQuery([
									'active' => 'FROM',
									'type' => $this->getActiveType(),
									'index' => $this->getActiveIndex()
								]);
							}
							$this->updateStatus( 'processing' );
							break;
						
						case 'WHERE':
							$this->updateStatus( 'done' );
							$this->setActiveQuery([
								'active' => 'WHERE',
								'type' => $this->getActiveType(),
								'index' => $this->getActiveIndex()
							]);
							$this->updateStatus( 'processing' );
							break;
					}
				}
				else
				{
					switch ( $this->getActiveAction() )
					{
						case 'WHERE':
							if ( in_array( $string, [ '(', ')' ] ) )
								$this->createWhereGrouped( $string );
							
							else
							{
								if ( !$this->countSQLAction( 'WHERE' ) )
								{
									$this->where_column = [
										'index' => 0,
										'column' => $string,
										'bridge' => 'OFFSET',
										'grouped' => $this->getActiveWhereGrouped()
									];
									
									$this->insertString( $string, []);
								}
								else
								{
									if ( isset( $this->where_column[ 'index' ] ) )
									{
										$this->where_column[ 'operator' ] = htmlentities( $string );
										$this->updateWhereCondition( $this->where_column );
										$this->where_column = [];
									}
									else
									{
										if ( in_array( strtoupper( $string ), $this->where_bridge ) )
											$this->where_column[ 'bridge' ] = $string;
										
										else
										{
											$this->where_column[ 'column' ] = $string;
											$this->where_column[ 'index' ] = $this->countSQLAction( 'WHERE' );
											$this->where_column[ 'grouped' ] = $this->getActiveWhereGrouped();
											$this->insertString( $string, []);
										}
									}
								}
							}
							break;
							
						default:
							$this->insertString( $string );
							break;
					}
				}
			}
			
			# Mark as done the current active query.
			# $this->setQueryFinished();
			
			# DEBUG
			dump(
				[ 'query' => $this->query ],
				[ 'status' => $this->sql_status ],
				[ 'queries_sequence' => $this->active_queries ],
				[ 'queries' => $this->queries ],
				[ 'sub-queries' => $this->sub_queries ],
				$array_string
			);
		}
		
		protected function checkIfDone( string $action ): bool
		{
			$done = [ 'done', 'excluded' ];
			$query = $this->getQueryType( $this->getActiveType() );
			
			foreach ( $this->$query[ $this->getActiveIndex() ] as $name => $status ) {
				if ( strtoupper( $action ) == $name ) {
					if ( in_array( $status, $done ) )
						return true;
				}
			}
			return false;
		}
		
		protected function insertString( string $string, $value = '', bool $sub_query = false ): void
		{
			$index = $this->getActiveIndex();
			$action = $this->getActiveAction();
			$query = $this->getSQL( $index );
			
			switch ( $action )
			{
				case 'WHERE':
					if ( $sub_query )
						$query[ $action ][] = [
							'attr' => $value,
							'grouped' => $this->getActiveWhereGrouped(),
							'column' => $string
						];
					else
						$query[ $action ][] = $value;
					break;
					
				default:
					$query[ $action ][ $string ] = $value;
					break;
			}
			
			$this->updateSQL( $query );
		}
		
		protected function updateWhereCondition( array $values ): void
		{
			$index = $values[ 'index' ];
			$query = $this->getQueryType( $this->getActiveType() );
			
			unset( $values[ 'index' ] );
			$this->$query[ $this->getActiveIndex() ][ 'WHERE' ][ $index ] = $values;
		}
		
		protected function updateStatus( string $status ): void
		{
			$array = [
				'index' => $this->getActiveIndex(),
				'type' => $this->getActiveType(),
				'action' => $this->getActiveAction()
			];
			
			$this->sql_status[ $this->getQueryType( $array[ 'type' ] ) ][ $array[ 'index' ] ][ $array[ 'action' ] ] = $status;
		}
		
		protected function updateSQL( array $query ): void
		{
			switch ( $this->getActiveType() )
			{
				case 'PARENT':
					$this->queries[ $this->getActiveIndex() ] = $query;
					break;
					
				case 'CHILDREN':
					$this->sub_queries[ $this->getActiveIndex() ] = $query;
					break;
			}
		}
		
		protected function countSQLAction( string $action ): int
		{
			$query = $this->getQueryType( $this->getActiveType() );
			return count( $this->$query[ $this->getActiveIndex() ][ $action ] );
		}
		
		protected function countParentQueries(): int {
			return count( $this->queries );
		}
		
		protected function countChildrenQueries(): int {
			return count( $this->sub_queries );
		}
		
		protected function getActiveWhereGrouped(): array
		{
			$grouped = [];
			for ( $i = 0; $i < count( $this->where_grouped ); $i++ )
			{
				$obj = $this->where_grouped[ $i ];
				if ( $obj[ 'close' ] == 0 ) {
					$grouped[] = $i;
				}
			}
			
			return $grouped;
		}
		
		protected function getQueryType( string $type ): string
		{
			if ( strtoupper( $type ) == 'PARENT' )
				return 'queries';
			
			if ( strtoupper( $type ) == 'CHILDREN' )
				return 'sub_queries';
			
			return '';
		}
		
		protected function getSQL( int $index ): array {
			return ( $this->getActiveType() == 'PARENT' ) ? $this->queries[ $index ] : $this->sub_queries[ $index ];
		}
		
		protected function getActiveAction(): string {
			return $this->query[ 'active' ];
		}
		
		protected function getActiveType(): string {
			return $this->query[ 'type' ];
		}
		
		protected function getActiveIndex(): int {
			return $this->query[ 'index' ];
		}
		
		protected function getRecentQuery(): array
		{
			$recent = [];
			for ( $i = 0; $i < count( $this->active_queries ); $i++ )
			{
				$obj = $this->active_queries[ $i ];
				if ( strtoupper( $obj[ 'type' ] ) == $this->getActiveType() && $obj[ 'index' ] == $this->getActiveIndex() )
				{
					$recent = $this->active_queries[ ( $i == 0 ) ? $i : $i - 1 ];
					break;
				}
			}
			
			return $recent;
		}
		
		protected function createWhereGrouped( string $string ): void
		{
			switch ( $string )
			{
				case '(':
					$this->where_grouped[] = [
						'open' => 1,
						'close' => 0
					];
					break;
				
				case ')':
					for ( $j = count( $this->where_grouped ) - 1; $j >= 0; $j-- )
					{
						$obj = $this->where_grouped[ $j ];
						if ( $obj[ 'close' ] == 0 ) {
							$this->where_grouped[ $j ][ 'close' ] = 1;
							break;
						}
					}
					break;
			}
		}
		
		protected function createNewQuery( string $type ): void
		{
			$array = [ 'SELECT', 'FROM', 'WHERE', 'LIMIT', 'OFFSET' ];
			
			switch ( strtoupper( $type ) )
			{
				case 'PARENT':
					$this->queries[] = array_fill_keys( $array, [] );
					$index = count( $this->queries ) - 1;
					
					$this->sql_status[ 'queries' ][ $index ] = array_fill_keys( $array, 'pending' );
					$this->active_queries[] = [
						'index' => $index,
						'type' => 'PARENT'
					];
					break;
					
				case 'CHILDREN':
					$this->sub_queries[] = array_fill_keys( $array, [] );
					$index = count( $this->sub_queries ) - 1;
					
					$this->sql_status[ 'sub_queries' ][ $index ] = array_fill_keys( $array, 'pending' );
					$this->active_queries[] = [
						'index' => $index,
						'type' => 'CHILDREN'
					];
					break;
			}
		}
		
		protected function setQueryFinished(): void
		{
			$query = $this->getQueryType( $this->getActiveType() );
			foreach ( $this->sql_status[ $query ][ $this->getActiveIndex() ] as $action => $status )
			{
				if ( $status == 'pending' )
					$this->sql_status[ $query ][ $this->getActiveIndex() ][ $action ] = 'excluded';
				
				elseif ( $status == 'processing' )
					$this->sql_status[ $query ][ $this->getActiveIndex() ][ $action ] = 'done';
			}
			
			for ( $i = 0; $i < count( $this->active_queries ); $i++ )
			{
				$obj = $this->active_queries[ $i ];
				if ( strtoupper( $obj[ 'type' ] ) == $this->getActiveType() && $obj[ 'index' ] == $this->getActiveIndex() )
				{
					# Remove now since it's done
					unset( $this->active_queries[ $i ] );
					
					# Re-index
					$this->active_queries = array_values( $this->active_queries );
					break;
				}
			}
		}
		
		protected function setActiveQuery( array $setup ): void
		{
			foreach ( $setup as $key => $value )
			{
				if ( isset( $this->query[ $key ] ) ) {
					$this->query[ $key ] = $value;
				}
			}
		}
		
		protected function format( string $query ): string
		{
			$query = str_replace( ',', ' , ', $query );
			$operators = [ '=', '>', '<', '&', '|', '^', '~' ];
			
			$length = strlen( $query );
			$formatted = "";
			
			for ( $i = 0; $i < $length; $i++ )
			{
				$char = $query[ $i ];
				$formatted .= $char;
				
				if ( in_array( $char, $operators ) )
				{
					$leftChar = $query[ $i - 1 ] ?? "";
					$rightChar = $query[ $i + 1 ] ?? "";
					
					if ( !in_array( $leftChar, $operators ) && $leftChar != ' ' ) {
						$formatted .= ' ';
					}
					
					if ( !in_array( $rightChar, $operators ) && $rightChar != ' ' ) {
						$formatted .= ' ';
					}
				}
			}
			
			return preg_replace('/\s{2,}/', ' ', $formatted );
		}
		
		protected function clean( string $query ): string
		{
			return trim( preg_replace_callback(
				"/('(?:[^'\\\\]|\\\\.)*')|(\"(?:[^\"]|\\\\.)*\")|\s*\b(\d+)\b\s*/",
				function ( $matches )
				{
					if ( !empty( $matches[ 3 ] ) )
					{
						$this->matched_string[] = $matches[3];
						return " '' ";
					}
					else
					{
						$matchedString = $matches[1] ?: $matches[2];
						$this->matched_string[] = $matchedString;
						return " '' ";
					}
				},
				$query
			));
		}
		
		public function result(): array {
			return $this->matched_string;
		}
		
		static function run( string $query ): array {
			$obj = new self( $query );
			return $obj->result();
		}
	}