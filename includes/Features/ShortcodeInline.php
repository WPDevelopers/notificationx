<?php
namespace NotificationX\Core;

use NotificationX\Core\PostType;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;

/**
 * Class Shortcode For NotificationX
 * @method static ShortcodeInline get_instance($args = null)
 *
 * @since 1.2.3
 */
class ShortcodeInline {
    /**
     * Instance of ShortcodeInline
     *
     * @var ShortcodeInline
     */
    use GetInstance;

    public $shortcode_nx_ids = [];

    /**
     * __construct__ is for revoke first time to get ready
     *
     * @return void
     */
    public function __construct() {
        add_shortcode( 'notificationx_inline', array( $this, 'shortcode_inline' ), 999 );
        add_filter('nx_inline_notifications_data',array( $this, 'nx_inlineshortcode' ),10,4);
    }

    public function nx_inlineshortcode( $return, $source, $id, $settings ){
        if( !empty( $settings['shortcodeinline'] ) ) {
            return FrontEnd::get_instance()->get_notifications_data(
                array(
                    'shortcode'        => [$settings['nx_id']],
                    'inline_shortcode' => true,
                )
            );
        }
        return $return;
    }

    /**
     * this method is responsible for output the shortcode.
     *
     * @param array $atts
     */
    public function shortcode_inline( $atts, $content = null ) {
        $atts  = shortcode_atts( array(
            'id'            => '',
            'product_id'    => '',
            'post_type'     => '',
            'show_link'     => true,
            ), $atts, 'notificationx_inline'
        );

        $nx_id = $atts['id'];

        if ( empty( $nx_id ) ) {
            if ( ! current_user_can( 'administrator' ) ) {
                return;
            }
            return '<p class="nx-shortcode-notice">' . __( 'Choose a Notification from the dropdown.', 'notificationx-pro' ) . '</p>';
        }

        if ( ! PostType::get_instance()->is_enabled( $nx_id ) ) {
            if ( ! current_user_can( 'administrator' ) ) {
                return;
            }
            return '<p class="nx-shortcode-notice">' . __( 'Make sure you have enabled the notification which ID you have given.', 'notificationx-pro' ) . '</p>';
        }

        do_action( 'nx_inline' );
        $settings = PostType::get_instance()->get_post($nx_id);
        if( $settings['type'] == 'inline' || $settings['source'] == 'woocommerce_sales_inline' ){
            $settings['shortcodeinline'] = true;
            /**
             * @var WooInline|EDDInline
             */
            $extension = \NotificationX\Extensions\ExtensionFactory::get_instance()->get($settings['source']);
            $output = $extension->show_inline_notification( $atts, $settings);
            if( !empty( $output ) ) {
                $output = "<div id='notificationx-shortcode-inline-{$atts['id']}' class='notificationx-shortcode-inline-wrapper nx-shortcode-notice'>$output</div>";
            }
            return $output;
        }

        $result = FrontEnd::get_instance()->get_notifications_data( [ 'shortcode' => [ $nx_id ] ] );

        $output = '';
        if ( ! empty( $result['shortcode'][ $nx_id ]['entries'] ) ) {
            $entries  = $result['shortcode'][ $nx_id ]['entries'];
            $entries  = array_values( $entries );
            $settings = $result['shortcode'][ $nx_id ]['post'];

            $logged_in       = is_user_logged_in();
            $show_on_display = isset($settings['show_on_display']) ? $settings['show_on_display'] : '';
            if ( ! ( ( $logged_in && 'logged_out_user' === $show_on_display ) || ( ! $logged_in && 'logged_in_user' === $show_on_display ) ) ) {

                $col = array_column( $entries, 'timestamp' );
                if ( count( $col ) == count( $entries ) ) {
                    array_multisort( $col, SORT_ASC, $entries );
                }
                $entry     = end( $entries );
                $template  = Inline::get_instance()->get_template( $settings );
                $_template = $template;

                foreach ( $entry as $key => $val ) {
                    if ( ! is_array( $val ) ) {
                        if ( 'rating' === $key ) {
                            $count = $val;
                            $val   = "<span style='white-space: nowrap'>";
                            for ( $i = 1; $i <= 5; $i++ ) {
                                $val .= $i <= $count
                                ? '<span style="color:#ffc107">★</span>'
                                : '<span style="color:#eeeeee">★</span>';
                            }
                            $val .= '</span>';
                        } elseif (
                            ! empty( $entry['link'] ) &&
                            in_array( $key, [ 'plugin_name', 'product_title', 'post_title', 'plugin_theme_name', 'course_title', 'title' ], true ) &&
                            'form' !== $settings['type'] &&
                            'email_subscription' !== $settings['type'] &&
                            'page_analytics' !== $settings['type']
                        ) {
                            $link   = $entry['link'];
                            $target = ! empty( $settings['link_open'] ) ? 'target="_blank"' : '';
                            if ( 'false' === $atts['show_link'] ) {
                                $target = '';
                                $link   = 'javascript:void(0)';
                            }
                            $val = "<a href='$link' $target>$val</a>";
                        }
                        $_template = str_replace( "{{{$key}}}", $val, $_template );
                    }
                    if ( ! empty( $entry['timestamp'] ) && strpos( $_template, '{{time}}' ) !== false ) {
                        $timestamp = $entry['timestamp'];
                        if ( $timestamp ) {
                            $diff_for_humans = sprintf(
                                /* translators: time */
                                _x( '%s ago', 'Inline Shortcode', 'notificationx-pro' ),
                                human_time_diff( $timestamp )
                            );
                            $_template = str_replace( '{{time}}', $diff_for_humans, $_template );
                        }
                    }
                }
                $output .= $_template;
                // $this->shortcode_nx_ids[] = $atts['id'];
                $output = "<div id='notificationx-shortcode-inline-{$atts['id']}' class='notificationx-shortcode-inline-wrapper nx-shortcode-notice'>$output</div>";
            }
        }

        return $output;
    }

}
