<?php 
/**
 * NotificationX Advanced Style
 */
class NotificationX_Advanced_Style {
    public static function generate_css( $settings ) {
        $theme = NotificationX_Helper::get_theme( $settings );
        $type  = NotificationX_Helper::get_type( $settings );

        $wrapper_css = $background_css = [];
        $css_string = '';

        if( $settings->display_type !== 'press_bar' ) {
            if( $settings->conversion_size ) {
                $wrapper_css[] = ! empty( $settings->conversion_size ) ? 'max-width: ' . $settings->conversion_size .'px' : '';
            }
        }
        $advanced_edit_enabled = self::enabled_edit( $settings );
        if( $advanced_edit_enabled ) {
            if( $settings->display_type === 'press_bar' ) {
                $css_string = self::press_bar_edit( $settings, $theme );
            }
            if( $settings->display_type === 'conversions' ) {
                $css_string = self::conversions_edit( $settings, $theme );
            }
            if( $settings->display_type === 'comments' ) {
                $css_string = self::comments_edit( $settings, $theme );
            }
        }

        if( ! empty( $css_string ) ) {
            $css_string = '<style type="text/css">' . $css_string . '</style>';
        }

        do_action( 'nx_style_generation' );
		$css_string = apply_filters('nx_style_string', $css_string, $settings );

        return $css_string;
    }
    public static function comments_edit( $settings, $theme = 'theme-one' ){
        $css_object = [];
        $css_string = '';

        if( ! empty( $settings->comment_bg_color ) ) {
            $css_object[ 'wrapper' ][] = 'background-color:' . $settings->comment_bg_color;
            $css_object['shadow']['color'] = $settings->comment_bg_color;
        }
        if( ! empty( $settings->comment_text_color ) ) {
            $css_object[ 'wrapper' ][] = 'color:' . $settings->comment_text_color;
            $css_object[ 'color' ][] = 'color:' . $settings->comment_text_color;
        }
        if( $settings->comment_border ) {
            if( ! empty( $settings->comment_border_size ) ) {
                $css_object[ 'border' ][] = 'border-width:' . $settings->comment_border_size . 'px';
                if( ! empty( $settings->comment_border_style ) ) {
                    $css_object[ 'border' ][] = 'border-style:' . $settings->comment_border_style;
                }
                if( ! empty( $settings->comment_border_color ) ) {
                    $css_object[ 'border' ][] = 'border-color:' . $settings->comment_border_color;
                    $css_object[ 'shadow' ]['border-color'] = $settings->comment_border_color;
                }
            }
        }

        if( ! empty( $settings->comment_first_font_size ) ) {
            $css_object[ 'first_row' ][] = 'font-size:' . $settings->comment_first_font_size . 'px';
        }
        if( ! empty( $settings->comment_second_font_size ) ) {
            $css_object[ 'second_row' ][] = 'font-size:' . $settings->comment_second_font_size . 'px';
        }
        if( ! empty( $settings->comment_third_font_size ) ) {
            $css_object[ 'second_row' ][] = 'font-size:' . $settings->comment_third_font_size . 'px';
        }

        $custom_class = '.nx-notification.nx-' . $settings->display_type . ' .nx-customize-style-' . $settings->id;
        $inner_class = $custom_class . '.nx-notification-' . $theme . '.notificationx-inner';
        $content_class = $inner_class . ' .notificationx-content';
        $image_class = $inner_class . ' .notificationx-image';

        if( ! empty( $css_object['wrapper'] ) ) {
            $wrapper_css = $inner_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            // THEME SIX
            if( $theme === 'theme-six-free' ) {
                $wrapper_css = $content_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
                $wrapper_css .= $image_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
                $wrapper_css .= $image_class . ':after{border-right-color: '. $css_object['shadow']['color'] .' }';
            }
            $css_string .= $wrapper_css;
        }
        if( ! empty( $css_object['border'] ) ) {
            $border_css = $inner_class . '{' . implode( ';', $css_object['border'] ) . '}';
            if( $theme === 'theme-six-free' ) {
                $border_css = $content_class . '{' . implode( ';', $css_object['border'] ) . '}';
                $border_css .= $image_class . '{' . implode( ';', $css_object['border'] ) . '}';
            }
            $css_string .= $border_css;
        }
        if( ! empty( $css_object['first_row'] ) ) {
            $css_string .= $content_class . ' .nx-first-row{' . implode( ';', $css_object['first_row'] ) . '}';
        }
        if( ! empty( $css_object['second_row'] ) ) {
            $css_string .= $content_class . ' .nx-second-row{' . implode( ';', $css_object['second_row'] ) . '}';
        }
        if( ! empty( $css_object['third_row'] ) ) {
            $css_string .= $content_class . ' .nx-third-row{' . implode( ';', $css_object['third_row'] ) . '}';
        }
        if( ! empty( $css_object[ 'color' ] ) ) {
            $css_string .= $content_class . ' > div {' . implode( ';', $css_object['color'] ) . '}';
            $css_string .= $content_class . ' > div > span {' . implode( ';', $css_object['color'] ) . '}';
        }

        return $css_string;
    }
    public static function conversions_edit( $settings, $theme = 'theme-one' ){
        $css_object = [];
        $css_string = '';

        if( ! empty( $settings->bg_color ) ) {
            $css_object[ 'wrapper' ][] = 'background-color:' . $settings->bg_color;
            $css_object['shadow']['color'] = $settings->bg_color;
        }
        if( ! empty( $settings->text_color ) ) {
            $css_object[ 'wrapper' ][] = 'color:' . $settings->text_color;
            $css_object[ 'color' ][] = 'color:' . $settings->text_color;
        }
        if( $settings->border ) {
            if( ! empty( $settings->border_size ) ) {
                $css_object[ 'border' ][] = 'border-width:' . $settings->border_size . 'px';
                if( ! empty( $settings->border_style ) ) {
                    $css_object[ 'border' ][] = 'border-style:' . $settings->border_style;
                }
                if( ! empty( $settings->border_color ) ) {
                    $css_object[ 'border' ][] = 'border-color:' . $settings->border_color;
                    $css_object[ 'shadow' ]['border-color'] = $settings->border_color;
                }
            }
        }

        if( ! empty( $settings->first_font_size ) ) {
            $css_object[ 'first_row' ][] = 'font-size:' . $settings->first_font_size . 'px';
        }
        if( ! empty( $settings->second_font_size ) ) {
            $css_object[ 'second_row' ][] = 'font-size:' . $settings->second_font_size . 'px';
        }
        if( ! empty( $settings->third_font_size ) ) {
            $css_object[ 'second_row' ][] = 'font-size:' . $settings->third_font_size . 'px';
        }

        $custom_class = '.nx-notification.nx-' . $settings->display_type . ' .nx-customize-style-' . $settings->id;
        $inner_class = $custom_class . '.nx-notification-' . $theme . '.notificationx-inner';
        $content_class = $inner_class . ' .notificationx-content';
        $image_class = $inner_class . ' .notificationx-image';

        if( ! empty( $css_object['wrapper'] ) ) {
            $wrapper_css = $inner_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            if( $theme === 'theme-four' ) {
                $wrapper_css = $content_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
                if( ! empty( $css_object['shadow']['color'] ) ) {
                    $box_shadow = '0 0 0px 10px ' . $css_object['shadow']['color'];
                }
                if( ! empty( $css_object['shadow']['border-color'] ) ) {
                    $box_shadow .= ',0 0 0px 11px ' . $css_object['shadow']['border-color'];
                }
                $box_shadow .= ',-10px 0px 30px -20px #b3b3b3';

                $wrapper_css .= $image_class . '{box-shadow: '. $box_shadow .'}';
            }
            $css_string .= $wrapper_css;
        }
        if( ! empty( $css_object['border'] ) ) {
            $border_css = $inner_class . '{' . implode( ';', $css_object['border'] ) . '}';
            if( $theme === 'theme-four' ) {
                $border_css = $content_class . '{' . implode( ';', $css_object['border'] ) . '}';
            }
            $css_string .= $border_css;
        }
        if( ! empty( $css_object['first_row'] ) ) {
            $css_string .= $content_class . ' .nx-first-row{' . implode( ';', $css_object['first_row'] ) . '}';
        }
        if( ! empty( $css_object['second_row'] ) ) {
            $css_string .= $content_class . ' .nx-second-row{' . implode( ';', $css_object['second_row'] ) . '}';
        }
        if( ! empty( $css_object['third_row'] ) ) {
            $css_string .= $content_class . ' .nx-third-row{' . implode( ';', $css_object['third_row'] ) . '}';
        }
        if( ! empty( $css_object[ 'color' ] ) ) {
            $css_string .= $content_class . ' > div {' . implode( ';', $css_object['color'] ) . '}';
        }

        return $css_string;
    }
    /**
     * Press Bar Advance CSS
     *
     * @param stdClass $settings
     * @param string $theme
     * @return string
     */
    public static function press_bar_edit( $settings, $theme = 'theme-one' ){
        $css_object = [];
        $css_string = '';
        if( ! empty( $settings->bar_bg_color ) ) {
            $css_object[ 'wrapper' ][] = 'background-color:' . $settings->bar_bg_color;
        }
        if( ! empty( $settings->bar_text_color ) ) {
            $css_object[ 'wrapper' ][] = 'color:' . $settings->bar_text_color;
        }
        if( ! empty( $settings->bar_font_size ) ) {
            $css_object[ 'wrapper' ][] = 'font-size:' . $settings->bar_font_size . 'px';
        }
        if( ! empty( $settings->bar_btn_bg ) ) {
            $css_object[ 'button' ][] = 'background-color:' . $settings->bar_btn_bg;
        }
        if( ! empty( $settings->bar_btn_text_color ) ) {
            $css_object[ 'button' ][] = 'color:' . $settings->bar_btn_text_color;
        }
        if( ! empty( $settings->bar_counter_bg ) ) {
            $css_object[ 'counter' ][] = 'background-color:' . $settings->bar_counter_bg;
        }
        if( ! empty( $settings->bar_counter_text_color ) ) {
            $css_object[ 'counter' ][] = 'color:' . $settings->bar_counter_text_color;
        }

        if( ! empty( $settings->bar_close_color ) ) {
            $css_object[ 'close' ][] = 'fill:' . $settings->bar_close_color;
        }

        $custom_class = '.nx-bar.nx-customize-style-' . $settings->id;
        $css_string .= $custom_class . '.' . $theme . '{' . implode( ';', $css_object['wrapper'] ) . '}';

        if( ! empty( $css_object['button'] ) ) {
            $css_string .= '.nx-bar.nx-customize-style-' . $settings->id . ' .nx-bar-inner .nx-bar-content-wrap a.nx-bar-button {' . implode( ';', $css_object['button'] ) . '}';
        }
        if( ! empty( $css_object['counter'] ) ) {
            if( $theme === 'theme-three' ) {
                $css_string .= '.nx-bar.nx-customize-style-' . $settings->id . ' .nx-bar-inner .nx-bar-content-wrap .nx-countdown-wrapper .nx-countdown .nx-time-section {' . implode( ';', $css_object['counter'] ) . '}';
            } else {
                $css_string .= '.nx-bar.nx-customize-style-' . $settings->id . ' .nx-bar-inner .nx-bar-content-wrap .nx-countdown {' . implode( ';', $css_object['counter'] ) . '}';
            }
        }

        if( ! empty( $css_object['close'] ) ) {
            $css_string .= '.nx-bar.nx-customize-style-' . $settings->id . ' .nx-bar-inner .nx-close {' . implode( ';', $css_object['close'] ) . '}';
        }

        return $css_string;
    }

    public static function enabled_edit( $settings ) {
        switch( $settings->display_type ) {
            case 'conversions' : 
                return $settings->advance_edit;
                break;
            case 'comments' : 
                return $settings->comment_advance_edit;
                break;
            case 'reviews' : 
                return $settings->wporg_advance_edit;
                break;
            case 'download_stats' : 
                return $settings->wpstats_advance_edit;
                break;
            case 'press_bar' : 
                return $settings->bar_advance_edit;
                break;
        }
    }
    /**
     * Generate CSS
     *
     * @param stdClass $settings
     * @return string css
     */
	public static function generate_css_back( $settings ){
		if( empty( $settings ) ) return;
		$style = $bar_btn = $bar_counter = $image_style = $content_style = $first_row_font = $second_row_font = $third_row_font = $max_width = [];
		$css_string = $css = '';
		
		//TODO: Re-write this class to generate ADV CSS for notification
		//TODO: Not working for stats and reviews only

		switch( $settings->display_type ){
			case 'reviews' : 
				if( $settings->conversion_size ) {
					$max_width[] = ! empty( $settings->conversion_size ) ? 'max-width: ' . $settings->conversion_size .'px !important' : '';
				}
				if( $settings->wporg_advance_edit ) {
					$style[] = ! empty( $settings->wporg_bg_color ) ? 'background-color: ' . $settings->wporg_bg_color . '!important' : '';
					$style[] = ! empty( $settings->wporg_text_color ) ? 'color: ' . $settings->wporg_text_color : '';
					
					if( $settings->wporg_border ){
						$style[] = 'border-width: ' . $settings->wporg_border_size . 'px !important';
						$style[] = 'border-style: ' . $settings->wporg_border_style . '!important';
						$style[] = 'border-color: ' . $settings->wporg_border_color . '!important';
					}
					
					if( ! empty( $settings->wporg_first_font_size ) ) {
						$first_row_font[] = 'font-size: ' . $settings->wporg_first_font_size . 'px';
					}
					if( ! empty( $settings->wporg_second_font_size ) ) {
						$second_row_font[] = 'font-size: ' . $settings->wporg_second_font_size . 'px';
					}
					if( ! empty( $settings->wporg_third_font_size ) ) {
						$third_row_font[] = 'font-size: ' . $settings->wporg_third_font_size . 'px';
					}
				}
				break;
			case 'download_stats' : 
				if( $settings->conversion_size ) {
					$max_width[] = ! empty( $settings->conversion_size ) ? 'max-width: ' . $settings->conversion_size .'px !important' : '';
				}
				if( $settings->wpstats_advance_edit ) {
					$style[] = ! empty( $settings->wpstats_bg_color ) ? 'background-color: ' . $settings->wpstats_bg_color . '!important' : '';
					$style[] = ! empty( $settings->wpstats_text_color ) ? 'color: ' . $settings->wpstats_text_color : '';
					
					if( $settings->wpstats_border ){
						$style[] = 'border-width: ' . $settings->wpstats_border_size . 'px !important';
						$style[] = 'border-style: ' . $settings->wpstats_border_style . '!important';
						$style[] = 'border-color: ' . $settings->wpstats_border_color . '!important';
					}
					
					if( ! empty( $settings->wpstats_first_font_size ) ) {
						$first_row_font[] = 'font-size: ' . $settings->wpstats_first_font_size . 'px';
					}
					if( ! empty( $settings->wpstats_second_font_size ) ) {
						$second_row_font[] = 'font-size: ' . $settings->wpstats_second_font_size . 'px';
					}
					if( ! empty( $settings->wpstats_third_font_size ) ) {
						$third_row_font[] = 'font-size: ' . $settings->wpstats_third_font_size . 'px';
					}
				}
				break;
			case 'conversions' : 
				if( $settings->conversion_size ) {
					$max_width[] = ! empty( $settings->conversion_size ) ? 'max-width: ' . $settings->conversion_size .'px !important' : '';
				}
				if( $settings->advance_edit ) {
					$style[] = ! empty( $settings->bg_color ) ? 'background-color: ' . $settings->bg_color . '!important' : '';
					$style[] = ! empty( $settings->text_color ) ? 'color: ' . $settings->text_color : '';
					
					if( $settings->border ){
						$style[] = 'border-width: ' . $settings->border_size . 'px !important';
						$style[] = 'border-style: ' . $settings->border_style . '!important';
						$style[] = 'border-color: ' . $settings->border_color . '!important';
					}
					
					if( ! empty( $settings->first_font_size ) ) {
						$first_row_font[] = 'font-size: ' . $settings->first_font_size . 'px';
					}
					if( ! empty( $settings->second_font_size ) ) {
						$second_row_font[] = 'font-size: ' . $settings->second_font_size . 'px';
					}
					if( ! empty( $settings->third_font_size ) ) {
						$third_row_font[] = 'font-size: ' . $settings->third_font_size . 'px';
					}
					
					if( $settings->image_position == 'right' ) {
						$style[] = 'flex-direction: row-reverse';
					}
				}
				break;
			case 'comments' : 
				if( $settings->conversion_size ) {
					$max_width[] = ! empty( $settings->conversion_size ) ? 'max-width: ' . $settings->conversion_size .'px !important' : '';
				}
				if( $settings->comment_advance_edit ) {
					$style[] = ! empty( $settings->comment_bg_color ) ? 'background-color: ' . $settings->comment_bg_color : '';
					$style[] = ! empty( $settings->comment_text_color ) ? 'color: ' . $settings->comment_text_color : '';
					
					if( $settings->comment_border ){
						$style[] = 'border-width: ' . $settings->comment_border_size . 'px !important';
						$style[] = 'border-style: ' . $settings->comment_border_style . ' !important';
						$style[] = 'border-color: ' . $settings->comment_border_color . '!important';
					}
					
					if( ! empty( $settings->comment_first_font_size ) ) {
						$first_row_font[] = 'font-size: ' . $settings->comment_first_font_size . 'px';
					}
					if( ! empty( $settings->comment_second_font_size ) ) {
						$second_row_font[] = 'font-size: ' . $settings->comment_second_font_size . 'px';
					}
					if( ! empty( $settings->comment_third_font_size ) ) {
						$third_row_font[] = 'font-size: ' . $settings->comment_third_font_size . 'px';
					}
					
					if( $settings->comment_image_position == 'right' ) {
						$style[] = 'flex-direction: row-reverse';
					}
				}
				break;
			case 'press_bar' : 
				if( $settings->bar_advance_edit ) {
					$style[] = ! empty( $settings->bar_bg_color ) ? 'background-color: ' . $settings->bar_bg_color . ' !important' : '';
					$style[] = ! empty( $settings->bar_text_color ) ? 'color: ' . $settings->bar_text_color . ' !important': '';
					$style[] = ! empty( $settings->bar_font_size ) ? 'font-size: ' . $settings->bar_font_size . 'px' . ' !important': '';
					$bar_btn[] = ! empty( $settings->bar_btn_bg ) ? 'background-color: ' . $settings->bar_btn_bg  . ' !important': '';
					$bar_btn[] = ! empty( $settings->bar_btn_text_color ) ? 'color: ' . $settings->bar_btn_text_color  . ' !important': '';
					$bar_btn[] = ! empty( $settings->bar_btn_font_size ) ? 'font-size: ' . $settings->bar_btn_font_size . 'px' . ' !important': '';
					$bar_counter[] = ! empty( $settings->bar_counter_bg ) ? 'background-color: ' . $settings->bar_counter_bg  . ' !important': '';
					$bar_counter[] = ! empty( $settings->bar_counter_text_color ) ? 'color: ' . $settings->bar_counter_text_color  . ' !important': '';
				}
				break;
		}
		
		$style = apply_filters('nx_style', $style, $settings );
		
		if( ! empty( $max_width ) ) {
			$css_string .= '.nx-notification .notificationx-inner {' . implode( ';', $max_width ) . '}';
		}
		
		if( ! empty( $style ) ) {
			if( $settings->display_type == 'press_bar' ) {
				$css_string .= '.nx-bar.nx-customize-style-' . $settings->id . '{' . implode( ';', $style ) . '}';
				if( ! empty( $bar_btn ) ) {
					$css_string .= '.nx-bar.nx-customize-style-' . $settings->id . ' a.nx-bar-button {' . implode( ';', $bar_btn ) . '}';
				}
				if( ! empty( $bar_counter ) ) {
					$css_string .= '.nx-bar.nx-customize-style-' . $settings->id . ' .nx-time-section {' . implode( ';', $bar_counter ) . '}';
				}
			} else {
				$css_string .= '.nx-notification .notificationx-inner.nx-customize-style-' . $settings->id . '{' . implode( ';', $style ) . '}';
			}
		}
		
		if( ! empty( $content_style ) ) {
			$css_string .= '.nx-notification .notificationx-inner.nx-customize-style-' . $settings->id . ' .notificationx-content {' . implode( ';', $content_style ) . '}';
		}
		
		if( ! empty( $first_row_font ) ) {
			$css_string .= '.nx-notification .notificationx-inner.nx-customize-style-' . $settings->id . ' .notificationx-content .nx-first-row {' . implode( ';', $first_row_font ) . '}';
		}
		if( ! empty( $second_row_font ) ) {
			$css_string .= '.nx-notification .notificationx-inner.nx-customize-style-' . $settings->id . ' .notificationx-content .nx-second-row {' . implode( ';', $second_row_font ) . '}';
		}
		if( ! empty( $third_row_font ) ) {
			$css_string .= '.nx-notification .notificationx-inner.nx-customize-style-' . $settings->id . ' .notificationx-content .nx-third-row {' . implode( ';', $third_row_font ) . '}';
		}
		
		if( ! empty( $css_string ) ) {
			$css .= '<style type="text/css">';
			$css .= $css_string;
			$css .= '</style>';
		}

		dump( $css_string, false, true );

		do_action( 'nx_style_generation' );
		$css = apply_filters('nx_style_string', $css, $settings );
		return ! empty( $css ) ? $css : '';
	}
}
