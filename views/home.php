<?php

	use App\Utilities\Component;

	return new class extends Component {

		public string $welcomeMessage = 'Hello World!';

		public function render(): string
		{
			return <<<HTML
			    <>
			        <h2>{$this->welcomeMessage}</h2>
			        <button wire:click="render">Click me</button>
			        <input type="text" wire:model="welcomeMessage">
			    </>
			HTML;
		}
	};