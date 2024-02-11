<?php

	namespace Illuminate\Database;
	
	interface Layer
	{
		public function fetch(): array;
		public function col(): array;
		public function field(): mixed;
		public function row(): array;
		public function lastID(): int|null;
		public function count(): int;
	}