<?php

	use App\Content\Blade;

	function dump(mixed $data, bool $exit = false): void
	{
		$printed = print_r($data, true);
		echo <<<HTML
			<pre> 
				$printed
			</pre>
		HTML;

		if ($exit) exit;
	}

	function view(string $path, array $data = []): string
	{
		ob_start();

		$root = rtrim(config('APP_ROOT'), '/') . '/views/';
		$normalizedPath = trim($path, '/');
		$normalizedPath = preg_replace('/\.php$/', '', $normalizedPath);
		$fullPath = $root . $normalizedPath . '.php';

		if (file_exists($temp = str_replace('.php', '.blade.php', $fullPath)))
			$fullPath = $temp;

		if (file_exists($fullPath)) {

			$compiled = Blade::compile(file_get_contents($fullPath));
			Blade::eval($compiled, $data);
		}

		return ob_get_clean();
	}
