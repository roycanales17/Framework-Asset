<?php
	
	/**
	 *
	 * <script>
	 * 		@config( 'ENV_NAME' );
	 * 		@template( 'TEMPLATE_PATH' );
	 * 		@container;
	 * 		@import( 'INCLUDE_FILE' );
	 * 		@token( true|false );
	 * 		@input( 'GLOBAL_INPUT' )
	 * </script>
	 *
	 */
	namespace Core;
	
	use DOMDocument;
	use DOMImplementation;
	use Illuminate\Http\Compose;
	
	Class Blades
	{
		const extension = ".blade.php";
		
		protected mixed $data;
		protected string $full_path;
		protected string $content = '';
		
		public function set_data( mixed $data ): void {
			$this->data = $data;
			compose::set_static( 'global_variables', $data );
		}
	
		public function set_filepath( string $path, bool $view = true ): void
		{
			$path = $view ? $this->get_filename( $path ) : $path;
			
			if ( !file_exists( $path ) )
				app::error( "File not found inside of the views directory given ($path)" );
			
			$this->full_path = $path;
		}
	
		public function render(): bool|string
		{
			$this->get_content( $this->full_path );
			$dom = $this->get_html( $this->content );
			$head = $this->create_head_tag( $dom );
			$this->create_title_page( $dom, $head );
			$this->create_links( $dom, $head );
			$this->create_scripts( $dom, $head );
			
			return $dom->saveHTML();
		}
		
		private function check_content( string $path ): bool
		{
			$cache = create_cache( file_get_contents( $path ) );
			$cache_content = file_get_contents( $cache[ 'path' ] );
			$this->refresh_content( $cache_content );
			file_put_contents( $cache[ 'path' ], $cache_content );
			
			Session::insert( '$_remove_paths', $cache[ 'path' ] );
			if ( load_content( $cache[ 'path' ], true, $path ) )
				remove_file( $cache[ 'path' ] );
			
			return true;
		}
		
		private function refresh_content( string &$content ): void
		{
			$this->look_for_token( $content );
			$this->look_for_braces( $content );
			$this->look_for_config( $content );
			$this->look_for_input( $content );
			$this->look_for_auth( $content );
		}
		
		private function get_content( string $path ): void
		{
			if ( $this->check_content( $path ) )
			{
				$content = file_get_contents( $path );
				
				$this->look_for_template( $content );
				$this->look_for_import( $content );
				$this->refresh_content( $content );
				
				$cache = create_cache( $content );
				$attr = load_content( $cache[ 'path' ], true );
				$this->content = $attr[ 'content' ];
			}
		}
		
		private function create_head_tag( &$dom )
		{
			$head = $dom->getElementsByTagName( 'head' );
			
			if ( $head->length === 0 )
			{
				$htmlTag = $dom->getElementsByTagName( 'html' )->item( 0 );
				$headTag = $dom->createElement( 'head' );
				
				if ( $htmlTag )
				{
					if ( $htmlTag->hasChildNodes() )
						$htmlTag->insertBefore( $headTag, $htmlTag->firstChild );
					
					else
						$htmlTag->appendChild( $headTag );
				}
			}
			
			return $head;
		}
		
		private function create_title_page( &$dom, &$head ): void
		{
			$title = $dom->getElementsByTagName( 'title' );
			
			if ( $title->length === 0 && $head->length > 0 )
			{
				$titleTag = $dom->createElement('title', config( "APP_NAME" ) );
				$head[ 0 ]->appendChild( $titleTag );
			}
		}
		
		private function create_links( &$dom, &$head ): void
		{
			$create = false;
			$css_path = config( 'APP_URL' ).'/resources/'.config( 'STYLE' );
			
			if ( !isset( $head[ 0 ] ) )
				return;
			
			$links = $head[ 0 ]->getElementsByTagName( 'link' );
			
			if ( !$links->length ) {
				$create = true;
			}
			else
			{
				foreach ( $links as $link )
				{
					if ( $link->getAttribute( 'href' ) !== $css_path ) {
						$create = true;
						break;
					}
				}
			}
			
			if ( $create )
			{
				$linkTag = $dom->createElement( 'link' );
				$linkTag->setAttribute( 'rel', 'stylesheet' );
				$linkTag->setAttribute( 'href', $css_path );
				
				$head[ 0 ]->appendChild( $linkTag );
			}
		}
		
		private function create_scripts( &$dom, &$head ): void
		{
			if ( !isset( $head[ 0 ] ) )
				return;
			
			$scripts_tag = $head[ 0 ]->getElementsByTagName( 'script' );
			$scripts = [
				'module'	=>	config( "APP_URL" )."/resources/".config( "MODULE_JS" ),
				'main'		=>	config( "APP_URL" )."/resources/".config( "JAVASCRIPT" )
			];
			
			foreach ( $scripts as $path )
			{
				$create = false;
				if ( $scripts_tag->length )
				{
					foreach ( $scripts_tag as $script_tag )
					{
						if ( $script_tag->getAttribute( 'src' ) !== $path )
						{
							$create = true;
							break;
						}
					}
				}
				else $create = true;
				
				if ( $create )
				{
					$new_tag = $dom->createElement( 'script' );
					$new_tag->setAttribute( 'type', 'text/javascript' );
					$new_tag->setAttribute('src', $path );
					$head[0]->appendChild( $new_tag );
				}
			}
		}
		
		private function get_html( $content ): DOMDocument
		{
			$dom = new DOMDocument( '1.0', 'UTF-8' );
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			
			if ( !empty( trim( $content ) ) ) {
				@$dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			}
			else
			{
				$impl = new DOMImplementation();
				$doctype = $impl->createDocumentType( 'html', '', '' );
				$html = $dom->createElement( 'html' );
				
				$dom->appendChild( $doctype );
				$dom->appendChild( $html );
			}
			
			return $dom;
		}
		
		private function get_filename( string $name ): string {
			return config( 'VIEWS' )."/$name".self::extension;
		}
		
		protected function look_for_auth( string &$content ): void
		{
			$pattern = '/@auth\(\s*(true|false)\s*\);/i';
			$content = preg_replace( $pattern, '<?php if ( is_authenticated( $1 ) ): ?>', $content );
			
			$pattern = '/@end_auth;/i';
			$content = preg_replace( $pattern, '<?php endif; ?>', $content );
		}
		
		protected function look_for_template( string &$content ): void
		{
			$pattern = '/@template\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*;/i';
			
			$continue = true;
			while ( $continue )
			{
				if ( preg_match( $pattern, $content, $matches ) )
				{
					$content = preg_replace( $pattern, '', $content );
					$path = $this->get_filename( $matches[1] );
					
					if ( file_exists( $path ) )
					{
						if ( load_content( $path ) )
						{
							$body = file_get_contents( $path );
							$content = preg_replace( '/@container;/i', $content, $body );
						}
					}
					else trace( "Template body cannot be found" )->store( $path );
				}
				
				else $continue = false;
			}
		}
		
		protected function look_for_import( string &$content ): void
		{
			$pattern = '/@import\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*;/i';
			
			$continue = true;
			while ( $continue )
			{
				if ( preg_match_all( $pattern, $content, $matches ) )
				{
					$path_r = $matches[1];
					foreach ( $path_r as $index => $path )
					{
						$path = $this->get_filename( $path );
						if ( file_exists( $path ) )
						{
							if ( load_content( $path ) )
							{
								$body = file_get_contents( $path );
								$content = str_replace( $matches[0][$index], $body, $content );
							}
						}
					}
				}
				
				else $continue = false;
			}
		}
		
		protected function look_for_token( string &$content ): void
		{
			$pattern = '/@token\(\s*(true|false)\s*\);/i';
			$content = preg_replace( $pattern, '<?= token($1) ?>', $content );
		}
		
		protected function look_for_braces( string &$content ): void
		{
			$pattern = '/{{\s*([^}]+)\s*}}/';
			$replacement = '<?= $1 ?>';
			$content = preg_replace( $pattern, $replacement, $content );
		}
		
		protected function look_for_config( string &$content ): void
		{
			$pattern = '/@config\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*;/i';
			$replacement = '<?= config( "$1" ) ?>';
			$content = preg_replace( $pattern, $replacement, $content );
		}
		
		protected function look_for_input( string &$content ): void
		{
			$pattern = '/@input\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*/i';
			if ( preg_match_all( $pattern, $content, $matches ) )
			{
				$global = session::get( '$_GLOBAL_INPUTS' );
				foreach ( $matches[1] as $index => $key )
				{
					$replacement =  isset( $global[ $key ] ) && $global[ $key ] ? '<?= "'.$global[ $key ].'" ?>' : null;
					$content = str_replace( $matches[0][ $index ], $replacement, $content );
				}
			}
		}
	}
	