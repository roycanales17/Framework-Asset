<li class="px-4 py-3 text-sm flex flex-col gap-2 border-b">
	<div class="flex justify-between items-center">
		<span class="text-gray-800 dark:text-gray-200 font-bold">{{ $message }}</span>
		<button
			class="btn btn-error btn-xs"
			wire:target="home-01"
			wire:click="remove({{ $index }})" >
			Remove
		</button>
	</div>
	<input
		type="text"
		class="input input-sm w-full"
		placeholder="Edit here..."
		wire:model="message"
		wire:target="home-01"
		wire:keydown.enter="edit({{ $index }}, event.target.value)" />
</li>