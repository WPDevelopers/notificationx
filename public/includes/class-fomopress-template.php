<?php

class FomoPress_Template {
    public static function get_template_ready( $template, $tags ){
        $html = $template;
		/**
		 * If template is in array format, lets break it down and make HTML markup.
		 */
		if ( is_array( $template ) ) {
			$html = '';
			for ( $i = 0; $i < count( $template ); $i++ ) {
				if ( $i == 0 ) {
					$html .= '<span class="fp-first-row">' . $template[$i] . '</span>';
				}
				if ( $i == 1 ) {
					$html .= '<span class="fp-second-row">' . $template[$i] . '</span>';	
				}
				if ( $i == 2 ) {
					$html .= '<span class="fp-third-row"><small>' . $template[$i] . '</small></span>';	
				}
			}
		}
		/**
		 * Get all merge tags from the template html.
		 */
		preg_match_all( '/{{([^}]*)}}/', $html, $tags_in_html, PREG_PATTERN_ORDER );
		/**
		 * Holds the original tags without formatting parameteres.
		 */
		$actual_tags = array();
		/**
		 * Holds the tags with formatting parameteres.
		 */
		$formatted_tags = array();

		if ( ! empty( $tags_in_html ) ) {
			for ( $i = 0; $i < count( $tags_in_html[1] ); $i++ ) {
				$x = explode( '|', $tags_in_html[1][$i] );
				$tag_in_template = '{{' . trim( $tags_in_html[1][$i] ) . '}}';
				if ( is_array( $x ) ) {
					$actual_tag = '{{' . trim( $x[0] ) . '}}';
					if ( ! isset( $x[1] ) ) {
						$x[1] = ' ';
					}
					$actual_tags[ $actual_tag ] = trim( $x[1] );
					$formatted_tags[ $actual_tag ] = $tag_in_template;
				} else {
					$actual_tags[ $tag_in_template ] = '';
					$formatted_tags[ $tag_in_template ] = $tag_in_template;
				}
			}
		}
		/**
		 * Loop through tags and convert the values in their relevant HTML.
		 */
        foreach ( $tags as $tag => $value ) {
			
			if ( isset( $actual_tags[ $tag ] ) ) {
				$variable = explode( ':', $actual_tags[ $tag ] );
				$formatted_value = $value;
				
				switch ( trim( $variable[0] ) ) {
					case 'bold':
						$formatted_value = '<strong>' . $value . '</strong>';
						break;
					case 'italic':
						$formatted_value = '<em>' . $value . '</em>';
						break;
					case 'color':
						$formatted_value = '<span style="color: ' . trim( $variable[1] ) . ';">' . $value . '</span>';
						break;
					case 'bold+color':
						$formatted_value = '<strong style="color: ' . trim( $variable[1] ) . ';">' . $value . '</strong>';
						break;
					case 'italic+color':
						$formatted_value = '<em style="color: ' . trim( $variable[1] ) . ';">' . $value . '</em>';
						break;
					case 'propercase':
						$formatted_value = '<span style="text-transform: capitalize;">' . $value . '</span>';
						break;
					case 'upcase':
						$formatted_value = '<span style="text-transform: uppercase;">' . $value . '</span>';
						break;
					case 'downcase':
						$formatted_value = '<span style="text-transform: lowercase;">' . $value . '</span>';
						break;
					case 'fallback':
						$tmp_val = trim( $variable[1] );
						$tmp_val = str_replace( '[', '', $tmp_val );
						$tmp_val = str_replace( ']', '', $tmp_val );
						$formatted_value = empty( $value ) ? $tmp_val : $value;
						break;
					default:
						break;
				}
				$html = str_replace( $formatted_tags[ $tag ], $formatted_value, $html );
			} else {
				if ( ! is_array( $html ) && ! is_array( $value ) ) {
					$html = str_replace( $tag, $value, $html );
				}
			}
        }

        $html = str_replace( '\\', '', $html );

        return $html;
    }
}