<?php
	
	namespace Illuminate\Http;
	
	use Core\{Session, trace};
	
	class Compiler
	{
		protected bool $compile;
		protected object $dispatch;
		private bool $middleware = false;
		
		function __construct( bool $dispatch, array $config )
		{
			$this->compile = $dispatch;
			$this->dispatch = new Dispatch( $config );
		}
		
		public function name( string $name ): self
		{
			$this->dispatch->name = $name;
			return $this;
		}
		
		public function middleware( string|array $actions ): self
		{
			$this->dispatch->middleware[] = $actions;
			$this->middleware = true;
			return $this;
		}
		
		public function where( array $statement ): self
		{
			$key = array_keys( $statement )[0];
			$this->dispatch->where[ $key ] = $statement[ $key ];
			return $this;
		}
		
		function __destruct()
		{
			if ( $this->compile )
			{
				if ( $recent_traces = Session::get( 'recent_traces' ) ) {
					Session::forget( 'recent_traces' );
					trace::recent( $recent_traces );
				}
				
//				if ( $this->middleware ) {
//					Buffer::reset( 'middleware' );
//					$this->middleware = false;
//				}
				
				$this->dispatch->start();
			}
		}
	}
	
	class Compiler2
	{
		protected bool $compile;
		protected object $dispatch;
		protected string $method;
		protected mixed $actions;
		
		private bool
			$prefix = false,
			$middleware = false;
		
		function __construct( bool $dispatch, array $config )
		{
			$this->compile = $dispatch;
			$this->method = $config[ 'method' ];
			$this->actions = $config[ 'action' ];
			$this->dispatch = new Dispatch( $config );
		}
		
		public function prefix( string $name ): self
		{
			Buffer::register( 'prefix', $name );
			$this->prefix = true;
			return $this;
		}
		
		public function group( \Closure $callback ): self
		{
			closureMethod( $callback );
			return $this;
		}
		
		public function middleware( string|array $actions ): self
		{
			Buffer::register( 'middleware', $actions );
			$this->dispatch->middleware[] = $actions;
			$this->middleware = true;
			return $this;
		}
		
		public function with( string $key, $value ): self
		{
			Session::put( $key, $value );
			return $this;
		}
		
		private function restart(): void
		{
			if ( $this->prefix )
			{
				$this->prefix = false;
				Buffer::reset( 'prefix' );
			}
			
			if ( $this->middleware )
			{
				$this->middleware = false;
				Buffer::reset( 'middleware' );
			}
			
			Buffer::reset( $this->dispatch->method );
		}
		
		function __destruct()
		{
			$this->restart();
			if ( $this->compile ) {
				$this->dispatch->start();
			}
		}
	}