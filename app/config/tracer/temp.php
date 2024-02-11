<?php
	
	namespace Core;
	
	class temp {
		
		protected mixed $info, $details = [];
		protected bool $important = false;
		protected int $sliced;
		
		function __construct( $message, $slice ) {
			$this->info = $message;
			$this->sliced = $slice;
		}
		
		public function store( mixed $info ): self {
			$this->details = $info;
			return $this;
		}
		
		public function important(): self {
			$this->important = true;
			return $this;
		}
		
		function __destruct() {
			trace::register( $this->info, $this->sliced, $this->details, $this->important );
		}
	}