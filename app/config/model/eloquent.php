<?php
	
	namespace Illuminate\Database;
	use Closure;
	use Illuminate\Http\Compose;
	
	class Eloquent extends Execution
	{
		private string $class_name;
		
		function __construct( string $class_name = '' ) {
			$this->class_name = $class_name;
		}
		
		function table( string $table ): self
		{
			if ( !$this->tableStatus )
				$this->query .= " FROM `$table`";
			
			else
				$this->query .= ", `$table`";
			
			if ( !$this->selectColumn )
			{
				$this->connectTable = $this->query;
				$this->query = '';
			}
			
			$this->table = $table;
			$this->tableStatus = true;
			return $this;
		}
		
		function select( string $column ): self
		{
			$col = preg_split("/\s+(?:as|AS)\s+/", $column );
			$name = trim( $col[0] );
			$alias = trim( $col[1] ?? false );
			
			$column = $name.( $alias ? " AS $alias" : "" );
			if ( $this->whereCondition )
			{
				if ( !$this->selectColumn ) {
					$this->query = "SELECT $column ".$this->query;
				}
				else
				{
					$pos = strpos( $this->query, "FROM" );
					if ( $pos !== false )
					{
						$substring = trim( substr( $this->query, 0, $pos ) );
						$substring .= ", $column";
						
						$this->query = $substring." ".substr( $this->query, $pos );
					}
				}
			}
			
			else $this->query .= ( !$this->selectColumn ? "SELECT $column" : ", $column" );
			
			$this->selectColumn = true;
			return $this;
		}
		
		function where( string|Closure $col, mixed $operator_or_value = null, mixed $value = self::DEFAULT_VALUE ): self {
			return $this->where_construct( "AND", $col, $operator_or_value, $value );
		}
		
		function orWhere( string|Closure $col, mixed $operator_or_value = '', mixed $value = self::DEFAULT_VALUE ): self {
			return $this->where_construct( "OR", $col, $operator_or_value, $value );
		}
		
		function orderBy( string $column, string $sort ): self
		{
			if ( !$this->orderBy ) {
				$this->query .= " ORDER BY $column ".strtoupper( $sort );
			}
			else {
				$this->query .= ", $column ".strtoupper( $sort );
			}
			
			if ( $this->orderBy === false ) {
				$this->orderBy = true;
			}
			return $this;
		}
		
		function paginate( int $per_page = 10, string $page_name = '' ): array
		{
			$page_name = strtolower( $page_name ?: $this->class_name );
			switch ( true )
			{
				case isset( $_GET[ $page_name ] ) && intval( $_GET[ $page_name ] ):
					$page_number = intval( $_GET[ $page_name ] );
					break;
				
				case !$page_name && ( isset( $_GET[ 'page' ] ) && intval( $_GET[ 'page' ] ) ):
					$page_number = intval( $_GET[ 'page' ] );
					break;
				
				default:
					$page_number = 1;
					break;
			}
			
			$dynamicLimit = $per_page;
			$this->paginateStatus = true;
			$dynamicOffset = ( $page_number - 1 ) * $per_page;
			
			$total = $this->count();
			$this->limit( $dynamicLimit );
			$this->offset( $dynamicOffset );
			
			$result = [
				'total'	=>	$total,
				'page'	=>	$page_number,
				'limit'	=>	$dynamicLimit,
				'data'	=>	$this->fetch()
			];
			
			if ( $this->fragmentStatus ) {
				Compose::set_static( $this->fragment_name, $result );
			}
			
			return $result;
		}
		
		function fragment( string $global_name ): self
		{
			$this->fragment_name = $global_name;
			$this->fragmentStatus = true;
			return $this;
		}
		
		function offset( int $offset ): self
		{
			$this->query .= " OFFSET $offset";
			$this->offsetStatus = true;
			return $this;
		}
		
		function limit( int $limit ): self
		{
			$this->query .= " LIMIT $limit";
			$this->limitStatus = true;
			return $this;
		}
		
		function get_sql( string $action = 'fetch' ): string {
			return $this->build_query( $action );
		}
	}