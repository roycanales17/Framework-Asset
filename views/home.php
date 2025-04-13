<?php

	use App\Utilities\Component;

	return new class extends Component {

		public string $message = '';
		public array $logs = [];

		public function submit($message): void
		{
			$this->message = $message;
			$this->logs[] = $message;
		}

		public function render(): string
		{
			$label = '';
			if ($this->message)
				$label = "<label>You Entered: <b>{$this->message}</b></label>";

			return <<<HTML
			    <div class="flex flex-col gap-3 p-5 border border-gray-200">
			        $label
			        <input type="text" placeholder="Type here..." wire:keydown.enter="submit(event.target.value)" class="input">
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