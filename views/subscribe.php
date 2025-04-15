<?php

	use App\Utilities\Component;

	return new class extends Component {

		public string $email = '';

		public function render(): string
		{
			return <<<HTML
				<labe>Registered Email: {$this->email}</labe>
				<input type="text" class="input" placeholder="Enter your email to subscribe" 
						wire:model="email" 
						wire:keydown.enter.clear="render" />
			HTML;
		}
	};