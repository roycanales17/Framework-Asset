<?php

	use App\Content\Blade;

	function dump(mixed $data, bool $exit = false): void
	{
		if (config('DEVELOPMENT')) {
			$printed = print_r($data, true);
			echo <<<HTML
				<pre> 
					$printed
				</pre>
			HTML;
		}

		if ($exit) exit;
	}

	function view(string $path, array $data = []): string
	{
		ob_start();

		$root = rtrim(config('APP_ROOT'), '/');
		$normalizedPath = preg_replace('/\.php$/', '', trim($path, '/'));
		$mainPath = "/views/{$normalizedPath}.php";
		$bladePath = str_replace('.php', '.blade.php', $mainPath);

		if (file_exists($root . $bladePath)) {
			$mainPath = $bladePath;
		}

		Blade::render($mainPath, extract: $data);

		return ob_get_clean();
	}