<?php

	namespace Components;
	use App\Utilities\Component;

	class Subscribe extends Component
	{
		public string $email = '';

		public function render(): string
		{
			return view('templates/subscribe', [
				'email' => $this->email
			]);
		}
	}