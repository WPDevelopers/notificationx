<?php
/**
 * Setup Wizard (Onboarding) Class File.
 *
 * Registers a standalone, full-screen onboarding wizard under its own
 * top-level "Setup Wizard" menu. The UI is rendered by the existing admin
 * React SPA via the `nx-setup-wizard` route.
 *
 * @package NotificationX\Core
 */

namespace NotificationX\Core;

use NotificationX\Admin\Admin;
use NotificationX\GetInstance;

/**
 * @method static SetupWizard get_instance($args = null)
 */
class SetupWizard {

    /**
     * Instance of SetupWizard
     *
     * @var SetupWizard
     */
    use GetInstance;

    /**
     * Page slug for the wizard.
     */
    const PAGE = 'nx-setup-wizard';

    /**
     * Option key that marks onboarding as finished/skipped.
     */
    const COMPLETED_OPTION = 'nx_onboarding_completed';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ], 35 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_preview_styles' ] );
        add_filter( 'admin_body_class', [ $this, 'body_class' ] );
        add_filter( 'nx_rest_miscellaneous', [ $this, 'handle_actions' ], 10, 2 );
        add_filter( 'nx_builder_configs', [ $this, 'add_status_to_context' ], 10, 1 );
    }

    /**
     * Register the "Setup Wizard" submenu under the NotificationX menu.
     *
     * Rendered by Admin::views() (the same React SPA root) so the
     * `nx-setup-wizard` route can take over the full screen.
     *
     * @return void
     */
    public function menu() {
        add_submenu_page(
            'nx-admin',
            __( 'Setup Wizard', 'notificationx' ),
            __( 'Setup Wizard', 'notificationx' ),
            'read_notificationx',
            self::PAGE,
            [ Admin::get_instance(), 'views' ],
            30
        );
    }

    /**
     * Load the real frontend notification stylesheet on the wizard page so the
     * Welcome-screen "Live Preview" renders the actual popup/bar/announcement
     * designs 1:1. The frontend CSS is fully scoped (no global resets), so it
     * is safe to load inside wp-admin.
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_preview_styles( $hook ) {
        if ( 'notificationx_page_' . self::PAGE !== $hook ) {
            return;
        }
        wp_enqueue_style(
            'notificationx-public',
            Helper::file( 'public/css/frontend.css', true ),
            [],
            apply_filters( 'nx_frontend_css_version', NOTIFICATIONX_VERSION ),
            'all'
        );
    }

    /**
     * Add a body class on the wizard page so the SPA can render a
     * full-screen layout (covering the WP sidebar / admin bar).
     *
     * @param string $classes
     * @return string
     */
    public function body_class( $classes ) {
        if ( $this->is_wizard_page() ) {
            $classes .= ' nx-setup-wizard-active';
        }
        return $classes;
    }

    /**
     * Whether the current admin request is the wizard page.
     *
     * @return bool
     */
    protected function is_wizard_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return is_admin() && isset( $_GET['page'] ) && self::PAGE === sanitize_key( wp_unslash( $_GET['page'] ) );
    }

    /**
     * Handle the `complete_setup_wizard` miscellaneous REST action.
     *
     * Returning a non-null value makes the /miscellaneous endpoint respond
     * with `{ success: true }`.
     *
     * @param mixed $result
     * @param array $params
     * @return mixed
     */
    public function handle_actions( $result, $params ) {
        if ( isset( $params['action'] ) ) {
            if ( 'complete_setup_wizard' === $params['action'] ) {
                return $this->complete( $params );
            }
            if ( 'setup_wizard_optin' === $params['action'] ) {
                return $this->optin_tracking();
            }
        }
        return $result;
    }

    /**
     * Opt the site into WP Insights usage tracking and send the data to the
     * insights API immediately. Triggered when the user proceeds past the
     * Welcome step — the in-card notice states that proceeding consents to
     * collecting the admin email to personalise the setup.
     *
     * @return bool
     */
    public function optin_tracking() {
        if ( ! current_user_can( 'read_notificationx' ) ) {
            return false;
        }
        if (
            class_exists( '\NotificationX\Admin\PluginInsights' )
            && method_exists( '\NotificationX\Admin\PluginInsights', 'optin' )
        ) {
            \NotificationX\Admin\PluginInsights::get_instance( NOTIFICATIONX_FILE )->optin( true );
        }
        return true;
    }

    /**
     * Mark onboarding as completed (also used when skipped) so the wizard
     * is not auto-launched again, and persist the collected choices.
     *
     * @param array $params
     * @return bool
     */
    public function complete( $params = [] ) {
        if ( ! current_user_can( 'read_notificationx' ) ) {
            return false;
        }

        $goals = [];
        if ( ! empty( $params['goals'] ) ) {
            $goals = array_filter( array_map( 'sanitize_key', explode( ',', $params['goals'] ) ) );
        }

        $data = [
            'business_type' => isset( $params['business_type'] ) ? sanitize_key( $params['business_type'] ) : '',
            'goals'         => array_values( $goals ),
            'completed_at'  => current_time( 'mysql' ),
        ];
        update_option( 'nx_onboarding_data', $data );

        return update_option( self::COMPLETED_OPTION, true );
    }

    /**
     * Whether onboarding has already been completed/skipped.
     *
     * @return bool
     */
    public static function is_completed() {
        return (bool) get_option( self::COMPLETED_OPTION, false );
    }

    /**
     * Expose onboarding status to the admin React app.
     *
     * @param array $data
     * @return array
     */
    public function add_status_to_context( $data ) {
        $data['onboarding_completed'] = self::is_completed();
        return $data;
    }
}
