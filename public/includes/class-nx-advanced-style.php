<?php
/**
 * NotificationX Advanced Style
 * @since 1.3.1
 */
class NotificationX_Advanced_Style {
    /**
     * CSS Generator
     */
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
            if( in_array( $settings->display_type, array( 'conversions', 'elearning', 'donation', 'form' ) ) ) {
                $css_string = self::conversions_edit( $settings, $theme );
            }
            if( $settings->display_type === 'comments' ) {
                $css_string = self::comments_edit( $settings, $theme );
            }
            if( $settings->display_type === 'reviews' ) {
                $css_string = self::reviews_edit( $settings, $theme );
            }
            if( $settings->display_type === 'download_stats' ) {
                $css_string = self::stats_edit( $settings, $theme );
            }
        }

        if( ! empty( $settings->conversion_size ) ) {
            $css_string .= '.nx-notification {' . 'max-width: ' . $settings->conversion_size .'px' . '; }';
        }

        if( ! empty( $css_string ) ) {
            $css_string = '<style type="text/css">' . $css_string . '</style>';
        }

        do_action( 'nx_style_generation' );
		$css_string = apply_filters('nx_style_string', $css_string, $settings );

        return $css_string;
    }
    /**
     * Stats CSS Generator
     *
     * @param stdClass $settings
     * @param string $theme
     * @return string
     */
    public static function stats_edit( $settings, $theme = 'theme-one' ){
        $css_object = [];
        $css_string = '';

        if( ! empty( $settings->wpstats_bg_color ) ) {
            $css_object[ 'wrapper' ][] = 'background-color:' . $settings->wpstats_bg_color;
            $css_object['shadow']['color'] = $settings->wpstats_bg_color;
        }
        if( ! empty( $settings->wpstats_text_color ) ) {
            $css_object[ 'wrapper' ][] = 'color:' . $settings->wpstats_text_color;
            $css_object[ 'color' ][] = 'color:' . $settings->wpstats_text_color;
        }
        if( $settings->wpstats_border ) {
            if( ! empty( $settings->wpstats_border_size ) ) {
                $css_object[ 'border' ][] = 'border-width:' . $settings->wpstats_border_size . 'px';
                if( ! empty( $settings->wpstats_border_style ) ) {
                    $css_object[ 'border' ][] = 'border-style:' . $settings->wpstats_border_style;
                }
                if( ! empty( $settings->wpstats_border_color ) ) {
                    $css_object[ 'border' ][] = 'border-color:' . $settings->wpstats_border_color;
                    $css_object[ 'shadow' ]['border-color'] = $settings->wpstats_border_color;
                }
            }
        }

        $css_object[ 'first_row' ][]  = 'font-size:' . $settings->wpstats_first_font_size . 'px';
        $css_object[ 'second_row' ][] = 'font-size:' . $settings->wpstats_second_font_size . 'px';
        $css_object[ 'third_row' ][]  = 'font-size:' . $settings->wpstats_third_font_size . 'px';

        $custom_class = '.nx-notification.nx-' . $settings->display_type . ' .nx-customize-style-' . $settings->id;
        $inner_class = $custom_class . '.nx-notification-' . $theme . '.notificationx-inner';
        $content_class = $inner_class . ' .notificationx-content';
        $image_class = $inner_class . ' .notificationx-image';

        if( ! empty( $css_object['wrapper'] ) ) {
            $wrapper_css = $inner_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            $wrapper_css .= $image_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            $css_string .= $wrapper_css;
        }
        if( ! empty( $css_object['border'] ) ) {
            $border_css = $inner_class . '{' . implode( ';', $css_object['border'] ) . '}';
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
        if( ! empty( $settings->wpstats_text_color ) ) {
            $css_string .= $content_class . ' .nx-branding > a > svg { fill: ' . $settings->wpstats_text_color . '}';
        }
        return $css_string;
    }
    /**
     * Reviews CSS Generator
     */
    public static function reviews_edit( $settings, $theme = 'theme-one' ){
        $css_object = [];
        $css_string = '';

        if( ! empty( $settings->wporg_bg_color ) ) {
            $css_object[ 'wrapper' ][] = 'background-color:' . $settings->wporg_bg_color;
            $css_object['shadow']['color'] = $settings->wporg_bg_color;
        }
        if( ! empty( $settings->wporg_text_color ) ) {
            $css_object[ 'wrapper' ][] = 'color:' . $settings->wporg_text_color;
            $css_object[ 'color' ][] = 'color:' . $settings->wporg_text_color;
        }
        if( $settings->wporg_border ) {
            if( ! empty( $settings->wporg_border_size ) ) {
                $css_object[ 'border' ][] = 'border-width:' . $settings->wporg_border_size . 'px';
                if( ! empty( $settings->wporg_border_style ) ) {
                    $css_object[ 'border' ][] = 'border-style:' . $settings->wporg_border_style;
                }
                if( ! empty( $settings->wporg_border_color ) ) {
                    $css_object[ 'border' ][] = 'border-color:' . $settings->wporg_border_color;
                    $css_object[ 'shadow' ]['border-color'] = $settings->wporg_border_color;
                }
            }
        }

        $css_object[ 'first_row' ][]  = 'font-size:' . $settings->wporg_first_font_size . 'px';
        $css_object[ 'second_row' ][] = 'font-size:' . $settings->wporg_second_font_size . 'px';
        $css_object[ 'third_row' ][]  = 'font-size:' . $settings->wporg_third_font_size . 'px';

        $custom_class = '.nx-notification.nx-' . $settings->display_type . ' .nx-customize-style-' . $settings->id;
        $inner_class = $custom_class . '.nx-notification-' . $theme . '.notificationx-inner';
        $content_class = $inner_class . ' .notificationx-content';
        $image_class = $inner_class . ' .notificationx-image';

        if( ! empty( $css_object['wrapper'] ) ) {
            $wrapper_css = $inner_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            $wrapper_css .= $image_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            // THEME SIX
            if( $theme === 'review-comment' ) {
                $wrapper_css = $content_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
                $wrapper_css .= $image_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
                $wrapper_css .= $image_class . ':after{border-right-color: '. $css_object['shadow']['color'] .' }';
            }
            $css_string .= $wrapper_css;
        }
        if( ! empty( $css_object['border'] ) ) {
            $border_css = $inner_class . '{' . implode( ';', $css_object['border'] ) . '}';
            if( $theme === 'review-comment' ) {
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
        if( ! empty( $settings->wporg_text_color ) ) {
            $css_string .= $content_class . ' .nx-branding > a > svg { fill: ' . $settings->wporg_text_color . '}';
        }

        return $css_string;
    }
    /**
     * Comments CSS Generator
     *
     * @param stdClass $settings
     * @param string $theme
     * @return string
     */
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

        $css_object[ 'first_row' ][]  = 'font-size:' . $settings->comment_first_font_size . 'px';
        $css_object[ 'second_row' ][] = 'font-size:' . $settings->comment_second_font_size . 'px';
        $css_object[ 'third_row' ][]  = 'font-size:' . $settings->comment_third_font_size . 'px';

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
        if( ! empty( $settings->comment_text_color ) ) {
            $css_string .= $content_class . ' .nx-branding > a > svg { fill: ' . $settings->comment_text_color . '}';
        }

        return $css_string;
    }
    /**
     * Conversions CSS Generator
     *
     * @param stdClass  $settings
     * @param string $theme
     * @return string
     */
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

        $css_object[ 'first_row' ][]  = 'font-size:' . $settings->first_font_size . 'px';
        $css_object[ 'second_row' ][] = 'font-size:' . $settings->second_font_size . 'px';
        $css_object[ 'third_row' ][]  = 'font-size:' . $settings->third_font_size . 'px';

        $custom_class = '.nx-notification.nx-' . $settings->display_type . ' .nx-customize-style-' . $settings->id;
        $inner_class = $custom_class . '.nx-notification-' . $theme . '.notificationx-inner';
        $content_class = $inner_class . ' .notificationx-content';
        $image_class = $inner_class . ' .notificationx-image';

        if( ! empty( $css_object['wrapper'] ) ) {
            $wrapper_css = $inner_class . '{' . implode( ';', $css_object['wrapper'] ) . '}';
            $css_string .= $wrapper_css;
        }
        if( ! empty( $css_object['border'] ) ) {
            $border_css = $inner_class . '{' . implode( ';', $css_object['border'] ) . '}';
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
        if( ! empty( $settings->text_color ) ) {
            $css_string .= $content_class . ' .nx-branding > a > svg { fill: ' . $settings->text_color . '}';
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
        if( isset( $css_object['wrapper'] ) ) {
            $css_string .= $custom_class . '.' . $theme . '{' . implode( ';', $css_object['wrapper'] ) . '}';
        }

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
    /**
     * to check Advanced Edit is enabled or not
     *
     * @param stdClass $settings
     * @return void
     */
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
            case 'elearning' :
                return $settings->elearning_advance_edit;
                break;
            case 'donation' :
                return $settings->donation_advance_edit;
            case 'form' :
                return $settings->form_advance_edit;
                break;
        }
    }
}
