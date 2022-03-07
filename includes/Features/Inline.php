<?php

namespace NotificationX\Core;

use NotificationX\Core\PostType;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;

/**
 * Class Shortcode For NotificationX Pro
 *
 * @since 1.2.3
 */
class Inline {
    /**
     * Instance of Shortcode
     *
     * @var Shortcode
     */
    use GetInstance;

    /**
     * __construct__ is for revoke first time to get ready
     *
     * @return void
     */
    public function __construct() {
        // InlineWooCommerce::get_instance();
        // InlineEDD::get_instance();
    }


    public function get_notifications_data( $source ) {
        if ( empty( $this->notifications_data ) ) {
            $this->notifications_data = array( 'shortcode' => array() );
            $notifications            = PostType::get_instance()->get_posts(
                array(
                    'source'    => $source,
                    'enabled'   => true,
                    'is_inline' => true,
                )
            );

            do_action( 'nx_inline' );

            if ( ! empty( $notifications ) ) {
                $this->notifications_data = FrontEnd::get_instance()->get_notifications_data(
                    array(
                        'shortcode'        => array_column( $notifications, 'nx_id' ),
                        'inline_shortcode' => true,
                    )
                );
            }
        }

        return $this->notifications_data;
    }


    public function get_template( $settings ) {
        if ( ! empty( $settings['template_adv'] ) && ! empty( $settings['advanced_template'] ) ) {
            return $settings['advanced_template'];
        }

        $template = '';
        $defaults = array(
            'first_param'         => '',
            'second_param'        => '',
            'third_param'         => '',
            'fourth_param'        => '',
            'fifth_param'         => '',
            'sixth_param'         => '',
            'map_fourth_param'    => '',
            'ga_fourth_param'     => '',
            'ga_fifth_param'      => '',
            'review_fourth_param' => '',
        );

        $theme_name = $settings['themes'];
        $theme_name = str_replace( $settings['source'] . '_', '', $theme_name );
        $theme_name = str_replace( $settings['type'] . '_', '', $theme_name );
        if ( ! empty( $settings['custom_type'] ) ) {
            $theme_name = str_replace( $settings['custom_type'] . '_', '', $theme_name );
        }
        $params = wp_parse_args( $settings['notification-template'], $defaults );

        foreach ( $params as $param => $element ) {
            if ( $element == 'tag_custom' && isset( $params[ 'custom_' . $param ] ) ) {
                // getting value of custom params.
                $element = $params[ 'custom_' . $param ];
            }
            if ( $element == 'tag_siteview' || $element == 'tag_realtime_siteview' ) {
                $params[ $param ] = '{{views}}';
            } elseif ( $element == 'ga_title' ) {
                $params[ $param ] = '{{title}}';
            } elseif ( strpos( $element, 'tag_product_' ) === 0 ) {
                $params[ $param ] = '{{' . str_replace( 'tag_product_', '', $element ) . '}}';
            } elseif ( strpos( $element, 'tag_' ) === 0 ) {
                $params[ $param ] = '{{' . str_replace( 'tag_', '', $element ) . '}}';
            } elseif ( strpos( $element, 'product_' ) === 0 ) {
                $params[ $param ] = '{{' . str_replace( 'product_', '', $element ) . '}}';
            } else {
                $params[ $param ] = $element;
            }
        }

        switch ( $settings['themes'] ) {
            case 'donation_theme-one':
            case 'donation_theme-two':
            case 'donation_theme-three':
            case 'donation_theme-four':
            case 'donation_theme-five':
                return "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']} {$params['fifth_param']}";
            case 'donation_conv-theme-seven':
            case 'donation_conv-theme-eight':
            case 'donation_conv-theme-nine':
                return "{$params['first_param']} {$params['second_param']} in {$params['third_param']} {$params['fourth_param']}";
        }

        switch ( $theme_name ) {
            case 'theme-one':
            case 'theme-two':
            case 'theme-three':
            case 'theme-four':
            case 'theme-five':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
                // conversion start
            case 'conv-theme-six':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['map_fourth_param']} {$params['fourth_param']} {$params['fifth_param']}";
                break;
            case 'conv-theme-seven':
            case 'conv-theme-eight':
            case 'conv-theme-nine':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
                // conversion end

                // comments theme start.
            case 'theme-six-free':
            case 'theme-seven-free':
            case 'theme-eight-free':
                // review themes
            case 'review-comment':
            case 'review-comment-2':
            case 'review-comment-3':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
                // comments theme end.

                // reviews theme start.
            case 'total-rated':
            case 'reviewed':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
            case 'review_saying':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['review_fourth_param']} {$params['fifth_param']} {$params['sixth_param']}";
                break;
                // reviews theme end.
                // start download stats
            case 'today-download':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
            case '7day-download':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
            case 'actively_using':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']}";
                break;
            case 'total-download':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
                // end download stats

            case 'maps_theme':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['map_fourth_param']} {$params['fourth_param']} {$params['fifth_param']}";
                break;
                // PA
            case 'pa-theme-one':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['ga_fourth_param']} {$params['ga_fifth_param']} {$params['sixth_param']}";
                break;
            case 'pa-theme-two':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['ga_fourth_param']} {$params['ga_fifth_param']} {$params['sixth_param']}";
                break;
            case 'pa-theme-three':
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['ga_fourth_param']}";
                break;
            case 'stock-theme-one':
            case 'stock-theme-two':
                $template = "{$params['second_param']} {$params['third_param']} {$params['fourth_param']} {$params['fifth_param']}";
                break;
            default:
                $template = "{$params['first_param']} {$params['second_param']} {$params['third_param']} {$params['fourth_param']}";
                break;
        }
        return $template;
    }
}
