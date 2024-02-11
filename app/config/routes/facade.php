<?php
	
	namespace Illuminate\Http;
	use Core\App;
	
	abstract class Facade
	{
		protected static object $accessor;
		
		public static function __callStatic( $method, $args )
		{
			$trace = debug_backtrace();
			$instance = static::getFacadeRoot();
			$instance->registerTrace( array_shift($trace ) );
			
			return $instance->$method( ...$args );
		}
		
		private static function getFacadeRoot()
		{
			$accessor = self::getRoot();
			
			if ( class_exists( $accessor ) ) {
				return self::$accessor = new $accessor();
			}
			
			return app::error( "The class '$accessor' is undefined" );
		}
		
		private static function getRoot(): string {
			return __NAMESPACE__."\\".static::getFacadeAccessor();
		}
	}