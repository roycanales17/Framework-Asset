<labe>Registered Email: {{ $email }}</labe>
<input type="text"
       class="input"
       placeholder="Enter your email to subscribe"
       wire:model="email"
       wire:keydown.enter.clear="render"
/>