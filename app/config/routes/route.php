<?php
	
	namespace Illuminate\Http;
	
	/**
	 * @method static \Illuminate\Http\Compiler get(string $uri, array|string|callable|null $action = null)
	 * @method static \Illuminate\Http\Compiler post(string $uri, array|string|callable|null $action = null)
	 * @method static \Illuminate\Http\Compiler put(string $uri, array|string|callable|null $action = null)
	 * @method static \Illuminate\Http\Compiler patch(string $uri, array|string|callable|null $action = null)
	 * @method static \Illuminate\Http\Compiler delete(string $uri, array|string|callable|null $action = null)
	 * @method static \Illuminate\Http\Compiler any(string $uri, array|string|callable|null $action = null)
	 * @method static \Illuminate\Http\Compiler view(string $uri, string $view, array $data = [], int|array $status = 200, array $headers = [])
	 * @method static \Illuminate\Http\Compiler match(array|string $methods, string $uri, array|string|callable|null $action = null)
	 *
	 * @method static \Illuminate\Http\Compiler2 redirect(string $uri, string $destination, int $status = 302)
	 * @method static \Illuminate\Http\Compiler2 fallback(array|string|callable $action)
	 * @method static \Illuminate\Http\Compiler2 controller(string $controller)
	 * @method static \Illuminate\Http\Compiler2 middleware(array|string $middleware)
	 * @method static \Illuminate\Http\Compiler2 prefix(string $uri_prefix)
	 * TODO: \Illuminate\Http\Compiler2 resources(array $methods)
	 * TODO: \Illuminate\Http\Compiler2 domain(string $value)
 	*/
	
	class Route extends Facade {
		
		/**
		 * Get the registered name of the component.
		 *
		 * @return string
		 */
		protected static function getFacadeAccessor(): string
		{
			return 'Router';
		}
	}
	