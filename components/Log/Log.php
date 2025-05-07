<?php

	namespace components\Log;

	use App\Utilities\Component;

	class Log extends Component
	{
		public string $message = '';
		public int $index = 0;

		public function init($index, $message): void
		{
			$this->index = $index;
			$this->message = $message;
		}

		public function render(): string
		{
			return $this->compile([
				'index' => $this->index,
				'message' => $this->message
			]);
		}
	}