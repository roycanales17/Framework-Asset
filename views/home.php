<?php

	use App\Utilities\Component;

	return new class extends Component {

		public string $welcomeMessage = 'Hello World!';

		public function render(): string
		{
			return <<<HTML
			    <>
			        <h2 class="text-3xl">{$this->welcomeMessage}</h2>
			        <button wire:click="render">Click me</button>
			        <input type="text" placeholder="Type here..." wire:model="welcomeMessage">
			    </>
			HTML;
		}
	};