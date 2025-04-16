<?php

	namespace Components;
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
			return view('templates/log', [
				'index' => $this->index,
				'message' => $this->message
			]);
		}
	}