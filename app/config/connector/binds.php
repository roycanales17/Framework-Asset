<?php
	
	namespace Illuminate\Database;
	
	class Binds
	{
		public function integer( string $key, int $value ): self {
			_pdo::register( $key, $value, __FUNCTION__ );
			return $this;
		}
		
		public function double( string $key, float $value ): self {
			_pdo::register( $key, $value, __FUNCTION__ );
			return $this;
		}
		
		public function string( string $key, string $value ): self {
			_pdo::register( $key, $value, __FUNCTION__ );
			return $this;
		}
		
		public function boolean( string $key, bool $value ): self {
			_pdo::register( $key, $value, __FUNCTION__ );
			return $this;
		}
		
		public function null( string $key, $value ): self {
			_pdo::register( $key, $value, __FUNCTION__ );
			return $this;
		}
		
	}