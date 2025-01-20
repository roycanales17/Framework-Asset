<?php

	use App\Content\Blade;
	use App\Headers\Response;

	function views(string $path, array $data = []): string {
		ob_start();

		# Compile the template
		Blade::render("views/". ltrim( $path, '/' ) .".blade.php", extract: $data);

		# Capture the content
		return ob_get_clean();
	}

	function response(mixed $content = '', int $status = 200, array $headers = []): Response
	{
		return new Response($content, $status, $headers);
	}

	function redirect(string $url, int $status = 302): void
	{
		response()->redirect($url, $status);
	}