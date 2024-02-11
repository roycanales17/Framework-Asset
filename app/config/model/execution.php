<?php
	
	namespace Illuminate\Database;
	use Closure;
	
	abstract class Execution
	{
		protected const DEFAULT_VALUE = 'default-null';
		
		protected int
			$duplicate = 0;
		
		protected bool
			$orderBy = false,
			$whereCondition = false,
			$selectColumn = false,
			$tableStatus = false,
			$offsetStatus = false,
			$limitStatus = false,
			$paginateStatus = false,
			$fragmentStatus = false;
		
		protected array
			$binds = [],
			$update_binds = [];
		
		protected string
			$table = '',
			$query = '',
			$temp_grouped = '',
			$fragment_name = '',
			$connectTable = '';
		
		function fetch(): array {
			return $this->execute( __FUNCTION__ );
		}
		
		function col(): array {
			return $this->execute( __FUNCTION__ );
		}
		
		function field(): mixed {
			return $this->execute( __FUNCTION__ );
		}
		
		function row(): array {
			return $this->execute( __FUNCTION__ );
		}
		
		function count(): int {
			return $this->execute( __FUNCTION__ );
		}
		
		function delete() {
			return $this->execute( __FUNCTION__ );
		}
		
		function update( array $binds )
		{
			foreach ( $binds as $key => $value ) {
				$this->register_binds( $key, $value, $temp_col );
				$this->update_binds[ $temp_col ] = $value;
			}
			
			return $this->execute( __FUNCTION__ );
		}
		
		protected function where_construct( string $type, $col, $operator_or_value, $value ): self
		{
			$this->append_table();
			if ( $col instanceof Closure && is_null( $operator_or_value ) ) {
				return $this->create_group( $col, $type );
			}
			
			$this->translate_operator( $operator_or_value, $value );
			$this->register_binds( $col, $value, $temp_col );
			$this->create_bridge( $and, $type );
			$this->construct_query( $and, $col, $operator_or_value, $temp_col );
			return $this;
		}
		
		protected function append_table(): void
		{
			if ( $this->connectTable )
			{
				$this->query .= $this->connectTable;
				$this->connectTable = "";
			}
		}
		
		protected function construct_query( string $bridge, string $column, string $operator_or_value, string $temp_column ): void
		{
			if ( $this->temp_grouped )
				$this->temp_grouped .= " $bridge `$column` ".( !$operator_or_value ? '=' : $operator_or_value )." :$temp_column";
			
			$this->query .= " $bridge `$column` ".( !$operator_or_value ? '=' : $operator_or_value )." :$temp_column";
		}
		
		protected function create_bridge( &$bridge, string $type ): void
		{
			if ( !$this->whereCondition ) {
				$bridge = "WHERE";
			} else {
				$bridge = $type;
			}
			
			$this->whereCondition = true;
		}
		
		protected function register_binds( string $column, mixed $value, &$temp_col ): void
		{
			$temp_col = $column;
			$temp_col = explode( '.', $temp_col );
			$temp_col = $temp_col[ count( $temp_col ) - 1 ];
			if ( isset( $this->binds[ $temp_col ] ) )
			{
				$this->duplicate++;
				$temp_col = "$temp_col{$this->duplicate}";
			}
			
			$this->binds[ $temp_col ] = $value;
		}
		
		protected function translate_operator( string &$operator, string &$value ): void
		{
			if ( $value === self::DEFAULT_VALUE ) {
				$value = $operator;
				$operator = '=';
			}
		}
		
		protected function create_group( Closure $callback, string $concat ): self
		{
			if ( !$this->whereCondition )
			{
				$concat = "";
				$this->query .= " WHERE ";
				$this->whereCondition = true;
			}
			
			if ( $this->whereCondition )
			{
				$this->query .= " $concat (";
				$this->temp_grouped .= " (";
			}
			
			$callback( $this );
			
			if ( $this->whereCondition )
			{
				$this->query .= " ) ";
				$this->temp_grouped .= " ) ";
			}
			
			return $this;
		}
		
		protected function build_query( string $action ): string
		{
			switch ( $action )
			{
				case 'update':
					$sql = "";
					$keys = array_keys( $this->update_binds );
					for ( $i = 0; $i < count( $keys ); $i++ ) {
						$key = $keys[ $i ];
						$sql .= ( !$i ? "" : ", " )."`$key` = :$key";
					}
					
					$this->query = str_replace( "FROM `{$this->table}` ", "UPDATE `{$this->table}` SET $sql ", $this->query );
					break;
				
				case 'delete':
					$this->query = str_replace( "FROM `{$this->table}` ", "DELETE FROM `{$this->table}` ", $this->query );
					break;
			
				default:
					if ( !$this->selectColumn )
						$this->query = "SELECT *{$this->query}";
					break;
			}
			
			$check = false;
			$sql = explode( ' ', preg_replace('/\s+/', ' ', trim( $this->query ) ) );
			for ( $i = 0; $i < count( $sql ); $i++ )
			{
				if ( $check )
				{
					if ( in_array( strtoupper( $sql[ $i ] ), [ 'OR', 'AND' ] ) ) {
						unset( $sql[ $i ] );
					}
					
					$check = false;
				}
				
				if ( $sql[ $i ] == '(' )
					$check = true;
			}
			
			return implode( ' ', array_values( $sql ) );
		}
		
		private function execute( string $action ): mixed
		{
			$result = true;
			$sql = $this->build_query( $action );
			switch ( $action )
			{
				case 'update':
				case 'delete':
					db::run( $sql, $this->binds );
					break;
				
				default:
					$result = db::run( $sql, $this->binds )->$action();
					break;
			}
			
			return $result;
		}
	}