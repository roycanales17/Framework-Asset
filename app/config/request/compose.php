<?php
	
	namespace Illuminate\Http;
	
	class Compose
	{
		protected string $name;
		protected static array $data = [];
		
		static function set_static( string $key, mixed $data = null ): void {
			self::$data[ $key ] = $data;
		}
		
		static function fragment( string $name ): self {
			return new self( $name );
		}
		
		function __construct( string $name ) {
			$this->name = $name;
		}
		
		function all(): mixed {
			return self::$data[ $this->name ];
		}
		
		function total(): int {
			return self::$data[ $this->name ][ 'total' ] ?? 0;
		}
		
		function rows(): array {
			return self::$data[ $this->name ][ 'data' ] ?? [];
		}
		
		function links(): string
		{
			$html = "";
			$attr = self::$data[ $this->name ];
			
			if ( !$attr ) {
				return $html;
			}
			
			$current_page = intval( $attr[ 'page' ] );
			$total_pages = ceil(intval( $attr[ 'total' ] ) / intval( $attr[ 'limit' ] ) );
			
			$html .= '<ul class="pagination">';
			
			if ( $current_page > 1 ) {
				$html .= '<li><a href="?page=' . ( $current_page - 1 ) . '">&#x3c;</a></li>';
			}
			
			$numLinksBetweenEllipsis = 6;
			$start = max( 3, $current_page - floor( $numLinksBetweenEllipsis / 2 ) );
			$end = min( $start + $numLinksBetweenEllipsis - 1, $total_pages );
			
			if ( $end != 1 )
			{
				for ( $i = 1; $i <= min( 2, $total_pages ); $i++ )
				{
					$html .= '<li><a href="?page=' . $i . '"';
					if ( $i == $current_page ) {
						$html .= ' class="active"';
					}
					$html .= '>' . $i . '</a></li>';
				}
				
				if ( $total_pages > 2 && $current_page > 3 ) {
					$html .= '<li><a href="javascript:void(0)">...</a></li>';
				}
			}
			
			for ( $i = $start; $i <= $end; $i++ ) {
				$html .= '<li><a href="?page=' . $i . '"';
				if ( $i == $current_page ) {
					$html .= ' class="active"';
				}
				$html .= '>' . $i . '</a></li>';
			}
			
			if ( $end < max( $total_pages - 1, 3 ) )
			{
				if ( $current_page < $total_pages - 3 ) {
					$html .= '<li><a href="javascript:void(0)">...</a></li>';
				}
				
				for ( $i = max( $total_pages - 1, 3 ); $i <= $total_pages; $i++ )
				{
					$html .= '<li><a href="?page=' . $i . '"';
					if ( $i == $current_page ) {
						$html .= ' class="active"';
					}
					$html .= '>' . $i . '</a></li>';
				}
			}
			
			if ( $current_page < $total_pages ) {
				$html .= '<li><a href="?page=' . ( $current_page + 1 ) . '">&#x3e;</a></li>';
			}
			
			$html .= '</ul>';
			return $html;
		}
	}