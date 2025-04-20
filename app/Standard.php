<?php

	use App\Content\Blade;
	use App\Headers\Request;
	use App\Utilities\Session;

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

	function validate_token(): void
	{
		if (!in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS']) && request()->header('X-CSRF-TOKEN') !== Session::get('csrf_token'))
			exit(response(['message' => 'Bad Request'], 400)->json());
	}