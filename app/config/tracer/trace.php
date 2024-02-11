<?php
	
	namespace Core;
	
	class trace
	{
		protected static array $recent = [];
		protected static array $trace = [];
		
		static function register( mixed $message = '', int $slice = 1, mixed $details = [], bool $important = false ): array {
			( new self )->setMessage( $message )->setSlice( $slice )->setDetails( $details )->setImportant( $important )->start();
			return self::info();
		}
		
		static function info(): array {
			return self::$trace[ count( self::$trace ) - 1 ];
		}
		
		static function tracks( $type ): array {
			return self::$$type;
		}
		
		static function recent( array $recent_trace ): void {
			self::$recent = $recent_trace;
		}
		
		protected array $info = [];
		protected array $debug;
		protected float $microseconds;
		protected mixed $message, $details;
		protected bool $important;
		protected int $slice;
		
		function __construct() {
			$this->microseconds = microtime( true );
			$this->debug = debug_backtrace();
		}
		
		private function setDetails( mixed $details ): self {
			$this->details = $details;
			return $this;
		}
		
		private function setMessage( mixed $message ): self {
			$this->message = $message;
			return $this;
		}
	
		private function setSlice( int $slice ): self {
			$this->slice = $slice;
			return $this;
		}
		
		private function setImportant( bool $important ): self {
			$this->important = $important;
			return $this;
		}
		
		private function debug(): object {
			if ( !$this->info ) {
				$this->info = array_slice( $this->debug, $this->slice )[0];
			}
			return (object) $this->info;
		}
		
		private function insert( array $trace ): void {
			self::$trace[] = $trace;
		}
		
		private function getRecentMicroseconds(): float {
			$recent = self::$trace[ count( self::$trace ) - 2 ][ 'microseconds' ] ?? 0;
			return $recent ?: START_TIME;
		}
		
		private function timeConsumed( float $end_time ): float {
			$start_time = $this->getRecentMicroseconds();
			return ( $end_time - $start_time );
		}
		
		private function start(): void
		{
			$microseconds = microtime( true );
			$this->insert([
				'info'			=>	$this->message,
				'file'			=>	$this->debug()->file,
				'line'			=>	$this->debug()->line,
				'details'		=>	$this->details,
				'microseconds'	=>	$microseconds,
				'important'		=>	$this->important,
				'duration'		=>	microsecondsToHMSM( $this->timeConsumed( $microseconds ) )
			]);
		}
		
		function __destruct() {
			// TODO: can't figure out why this isn't executing
		}
	}