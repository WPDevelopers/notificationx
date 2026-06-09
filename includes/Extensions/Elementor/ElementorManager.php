<?php
/**
 * NotificationX Elementor Manager
 *
 * Registers the "NotificationX" widget category, all NX Elementor widgets,
 * and the JS/CSS assets that power them (both on the public frontend and
 * inside the Elementor editor preview iframe).
 *
 * @package NotificationX\Extensions\Elementor
 */

namespace NotificationX\Extensions\Elementor;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static ElementorManager get_instance($args = null)
 */
class ElementorManager {

    use GetInstance;

    public function __construct() {
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
        add_action( 'elementor/widgets/register',               [ $this, 'register_widgets' ] );

        // Register scripts/styles so Elementor can enqueue them via
        // Widget_Base::get_script_depends() / get_style_depends().
        // `elementor/frontend/after_register_scripts` fires in both the
        // editor preview iframe and on the public frontend.
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
        add_action( 'elementor/frontend/after_register_styles',  [ $this, 'register_styles' ] );
    }

    /**
     * Register a dedicated "NotificationX" widget category.
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_category( $elements_manager ) {
        $elements_manager->add_category(
            'notificationx',
            [
                'title' => esc_html__( 'NotificationX', 'notificationx' ),
                'icon'  => 'fa fa-bell',
            ]
        );
    }

    /**
     * Register NX widgets with Elementor.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_widgets( $widgets_manager ) {
        $widgets_manager->register( new CountdownWidget() );
        $widgets_manager->register( new FormWidget() );
    }

    /**
     * Register JS assets.
     * Called on `elementor/frontend/after_register_scripts` — runs in
     * both editor preview and public frontend.
     */
    public function register_scripts() {
        wp_register_script(
            'nx-countdown',
            NOTIFICATIONX_PUBLIC_URL . 'js/nx-countdown.js',
            [ 'jquery' ],
            NOTIFICATIONX_VERSION,
            true  // in footer
        );

        wp_register_script(
            'nx-elementor-form',
            NOTIFICATIONX_PUBLIC_URL . 'js/nx-elementor-form.js',
            [],
            NOTIFICATIONX_VERSION,
            true
        );
    }

    /**
     * Register CSS assets.
     * Called on `elementor/frontend/after_register_styles`.
     */
    public function register_styles() {
        wp_register_style(
            'nx-countdown',
            NOTIFICATIONX_PUBLIC_URL . 'css/nx-countdown.css',
            [],
            NOTIFICATIONX_VERSION
        );

        wp_register_style(
            'nx-elementor-form',
            NOTIFICATIONX_PUBLIC_URL . 'css/nx-elementor-form.css',
            [],
            NOTIFICATIONX_VERSION
        );
    }
}
