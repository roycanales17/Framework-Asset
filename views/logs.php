<?php

	use App\Utilities\Component;

	return new class extends Component {

		public string $message = '';
		public int $index = 0;

		public function init($index, $message): void
		{
			$this->index = $index;
			$this->message = $message;
		}

		public function render(): string
		{
			return <<<HTML
				<li class="px-4 py-3 text-sm flex flex-col gap-2 border-b">
					<div class="flex justify-between items-center">
						<span class="text-gray-800 dark:text-gray-200 font-bold">
							{$this->message}
						</span>
						<button 
							class="btn btn-error btn-xs"
							wire:target="home-01"
							wire:click="remove({$this->index})" >
							Remove
						</button>
					</div>
					<input 
						type="text" 
						class="input input-sm w-full"
						placeholder="Edit here..." 
						wire:model="message" 
						wire:target="home-01" 
						wire:keydown.enter="edit({$this->index}, event.target.value)" />
				</li>
			HTML;
		}
	};