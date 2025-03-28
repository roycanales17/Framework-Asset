<?php

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