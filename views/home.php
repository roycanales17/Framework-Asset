<?php

	use App\Utilities\Component;

	return new class extends Component {

		public string $message = '';
		public array $logs = [];

		public function submit(): void
		{
			if ($this->message) {
				$this->logs[] = $this->message;
			}
		}

		public function render(): string
		{
			$hidden = empty(trim($this->message)) ? 'display-none' : '';

			return <<<HTML
			    <div class="flex flex-col gap-3 p-5 border border-gray-200">
			       <div class="flex flex-row gap-5 items-center"> 
			       		<input type="text" class="input" placeholder="Type here..." 
			       			wire:model="message" 
			       			wire:keydown.enter.clear.prevent="submit" 
			       			wire:loader.attr="disabled" />
			       		<input type="button" value="Click Me" class="btn" 
			       			wire:click.prevent="submit"  
			       			wire:loader.attr="disabled" />
			       		<label class='progress-info hidden' 
							wire:loader.classList.remove='hidden'>
							Loading...
						</label>
			        	<label class="$hidden"
			        		wire:loader.classList.add='hidden'>
			        		You Entered: <b>{$this->message}</b>
			        	</label>
			       </div>
			        <ul class="bg-base-100 rounded-sm shadow-md border border-gray-600"> 
			        	<li class="p-4 text-xs opacity-60 tracking-wide border-b border-gray-500">Submitted Logs:</li>
			        	{$this->print(function() {
							if ($this->logs) {
								
								echo '<li class="border-t border-gray-500 p-0"></li>';
								$lists = implode("</li><li class='px-4 py-3 text-sm'>", $this->logs);
								
								echo <<<HTML
								  <li class="px-4 py-3 text-sm">
									$lists
								  </li>
								HTML;
							}
						})} 
			        </ul>
			    <div/>
			HTML;
		}
	};