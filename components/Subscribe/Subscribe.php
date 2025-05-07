<?php

	namespace components\Subscribe;

	use App\Utilities\Component;

	class Subscribe extends Component
	{
		public string $email = '';

		public function render(): string
		{
			return $this->compile([
				'email' => $this->email
			]);
		}
	}