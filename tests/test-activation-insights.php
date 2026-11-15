<?php
/**
 * Activation flow + insights data-collection tests for NotificationX.
 *
 * Covers the two invariants that the onboarding/insights refactor relies on:
 *
 *  1. The Setup Wizard is only auto-launched on a genuine first-ever
 *     activation — an abandoned wizard still counts as "already activated".
 *  2. WP Insights collects and sends nothing until the user opts in (the
 *     opt-in notice's "Allow" or the Setup Wizard's "Get My
 *     Recommendations"; WordPress.org Guideline 7, issue #184). Activation
 *     does not schedule tracking for a site that has not opted in, and the
 *     deactivation feedback form follows the same gate. The programmatic
 *     opt-out wins over consent.
 *
 * @package Notificationx
 */

use NotificationX\Admin\PluginInsights;
use NotificationX\Core\SetupWizard;
use NotificationX\Core\Upgrader;
use NotificationX\NotificationX;

/**
 * @group activation
 * @group insights
 */
class Test_Activation_Insights extends WP_UnitTestCase {

	/**
	 * Options touched by these tests, reset around every case.
	 *
	 * @var string[]
	 */
	protected $options = [
		'nx_first_activation',
		'nx_onboarding_completed',
		'wpins_allow_tracking',
		'wpins_block_notice',
	];

	public function setUp(): void {
		parent::setUp();
		$this->reset_state();
	}

	public function tearDown(): void {
		$this->reset_state();
		parent::tearDown();
	}

	protected function reset_state() {
		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
		delete_transient( 'nx_activated' );
	}

	/**
	 * Capture the page slug NotificationX::maybe_redirect() redirects to,
	 * without letting the redirect actually happen.
	 *
	 * @return string|null
	 */
	protected function capture_redirect_page() {
		$captured = null;
		$capture  = function ( $location ) use ( &$captured ) {
			$captured = $location;
			return false; // Prevent the redirect from being issued.
		};

		add_filter( 'wp_redirect', $capture );
		NotificationX::get_instance()->maybe_redirect();
		remove_filter( 'wp_redirect', $capture );

		if ( null === $captured ) {
			return null;
		}

		parse_str( (string) wp_parse_url( $captured, PHP_URL_QUERY ), $query );
		return isset( $query['page'] ) ? $query['page'] : null;
	}

	/**
	 * Reset the PluginInsights singleton so a case can build one with its own args.
	 *
	 * @param array $args
	 * @return PluginInsights
	 */
	protected function fresh_insights( $args = [] ) {
		$property = new ReflectionProperty( PluginInsights::class, '_instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );

		return PluginInsights::get_instance(
			NOTIFICATIONX_FILE,
			wp_parse_args( $args, [
				'opt_in'       => true,
				'goodbye_form' => true,
				'item_id'      => '6ba8d30bc0beaddb2540',
			] )
		);
	}

	/**
	 * is_tracking_allowed() is private — it is the backend collection gate.
	 *
	 * @param PluginInsights $insights
	 * @return bool
	 */
	protected function is_tracking_allowed( $insights ) {
		$method = new ReflectionMethod( $insights, 'is_tracking_allowed' );
		$method->setAccessible( true );
		return $method->invoke( $insights );
	}

	/* --------------------------------------------------------------- *
	 * Setup Wizard activation flow
	 * --------------------------------------------------------------- */

	/**
	 * A first-ever activation records the first-run marker and sends the user
	 * into the Setup Wizard.
	 */
	public function test_first_activation_launches_the_setup_wizard() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertFalse( NotificationX::has_activated_before(), 'A clean install has no first-run marker.' );

		NotificationX::get_instance()->activator();

		$this->assertTrue( NotificationX::has_activated_before(), 'Activation records the first-run marker.' );
		$this->assertSame( 'first', get_transient( 'nx_activated' ) );
		$this->assertSame( 'nx-setup-wizard', $this->capture_redirect_page() );
	}

	/**
	 * Re-activating after abandoning the wizard must not launch it again.
	 */
	public function test_reactivation_after_abandoned_wizard_does_not_launch_it() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		NotificationX::get_instance()->activator(); // First activation.
		$stamp = get_option( 'nx_first_activation' );
		delete_transient( 'nx_activated' );         // Wizard abandoned, plugin deactivated.

		NotificationX::get_instance()->activator(); // Second activation.

		$this->assertFalse( SetupWizard::is_completed(), 'The wizard was never completed.' );
		$this->assertSame( 'again', get_transient( 'nx_activated' ) );
		$this->assertSame( $stamp, get_option( 'nx_first_activation' ), 'The first-run stamp is written once.' );
		$this->assertSame( 'nx-dashboard', $this->capture_redirect_page() );
	}

	/**
	 * Once the wizard is completed, activation keeps landing on the dashboard.
	 */
	public function test_activation_after_completed_wizard_lands_on_dashboard() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		update_option( 'nx_first_activation', time() );
		update_option( SetupWizard::COMPLETED_OPTION, true );

		NotificationX::get_instance()->activator();

		$this->assertSame( 'nx-dashboard', $this->capture_redirect_page() );
	}

	/**
	 * Installs that pre-date the first-run marker are seeded by the Upgrader,
	 * so re-activating them never forces the wizard.
	 */
	public function test_upgrader_seeds_first_run_marker_for_existing_installs() {
		delete_option( 'nx_first_activation' );
		update_option( 'nx_free_version', '3.2.10' );

		// The Upgrader singleton already ran during bootstrap; run a fresh
		// constructor to simulate the first page load after the update.
		$reflection = new ReflectionClass( Upgrader::class );
		$upgrader   = $reflection->newInstanceWithoutConstructor();
		$reflection->getConstructor()->invoke( $upgrader );

		$this->assertTrue( NotificationX::has_activated_before(), 'Existing installs are marked as already activated.' );
	}

	/**
	 * A transient written by an older build holds `true`; it must be treated as
	 * a returning activation rather than a first run.
	 */
	public function test_legacy_activation_transient_does_not_launch_the_wizard() {
		set_transient( 'nx_activated', true, 30 );

		$this->assertSame( 'nx-dashboard', $this->capture_redirect_page() );
		$this->assertFalse( get_transient( 'nx_activated' ), 'The transient is consumed.' );
	}

	/* --------------------------------------------------------------- *
	 * WP Insights data collection
	 * --------------------------------------------------------------- */

	/**
	 * Collection follows the consent state: never asked, accepted, or rejected.
	 */
	public function test_data_collection_requires_consent() {
		$insights = $this->fresh_insights();

		// Consent never given.
		$this->assertFalse( $this->is_tracking_allowed( $insights ) );
		$this->assertFalse( $insights->has_user_consented() );

		// Consent accepted.
		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );
		$this->assertTrue( $insights->has_user_consented() );

		// Consent rejected ("No Thanks" removes the plugin from the array);
		// another plugin's consent does not count.
		update_option( 'wpins_allow_tracking', [ 'anotherplugin' => 'anotherplugin' ] );
		$this->assertFalse( $this->is_tracking_allowed( $insights ) );
		$this->assertFalse( $insights->has_user_consented() );
	}

	/**
	 * Opening, abandoning or finishing the Setup Wizard is not consent.
	 */
	public function test_setup_wizard_state_is_not_consent() {
		$insights = $this->fresh_insights();

		update_option( 'nx_first_activation', time() );
		$this->assertFalse( $this->is_tracking_allowed( $insights ) );

		update_option( SetupWizard::COMPLETED_OPTION, true );
		$this->assertFalse( $this->is_tracking_allowed( $insights ) );
	}

	/**
	 * The wizard's "Get My Recommendations" (SetupWizard::optin_tracking())
	 * records consent.
	 */
	public function test_wizard_opt_in_records_consent() {
		$insights = $this->fresh_insights();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		add_filter( 'pre_http_request', '__return_empty_array' );

		$insights->optin( false );

		remove_filter( 'pre_http_request', '__return_empty_array' );
		$this->assertTrue( $insights->has_user_consented() );
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );
		wp_clear_scheduled_hook( 'put_do_weekly_action' );
	}

	/**
	 * Without consent do_tracking() sends nothing, even when forced (the daily
	 * Maintenance routine forces it).
	 */
	public function test_do_tracking_sends_nothing_without_consent() {
		$insights = $this->fresh_insights();
		$requests = 0;
		$count    = function ( $pre ) use ( &$requests ) {
			$requests++;
			return [ 'response' => [ 'code' => 200 ], 'body' => '{}' ];
		};
		add_filter( 'pre_http_request', $count );

		$insights->do_tracking( true );

		remove_filter( 'pre_http_request', $count );
		$this->assertSame( 0, $requests );
	}

	/**
	 * The payload carries no server path and no list of inactive plugins,
	 * only their number.
	 */
	public function test_payload_has_no_file_path_or_inactive_plugin_list() {
		$body = $this->fresh_insights()->get_data();

		$this->assertArrayNotHasKey( 'file_location', $body );
		$this->assertArrayNotHasKey( 'inactive_plugins', $body );
		$this->assertArrayHasKey( 'inactive_plugins_count', $body );
		$this->assertIsInt( $body['inactive_plugins_count'] );
	}

	/**
	 * The admin's IP is not sent to a geolocation service.
	 */
	public function test_tracker_does_no_ip_geolocation() {
		$source = file_get_contents( NOTIFICATIONX_PATH . 'includes/Admin/PluginInsights.php' );
		$this->assertStringNotContainsString( 'ip-api.com', $source );
	}

	/**
	 * The programmatic opt-out (the `options` constructor argument) is still a
	 * hard stop and revokes any stored consent.
	 */
	public function test_programmatic_opt_out_still_disables_collection() {
		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		update_option( 'nx_test_privacy', [ 'wpins_opt_out' => true ] );

		$insights = $this->fresh_insights( [ 'options' => [ 'nx_test_privacy' ] ] );

		$this->assertFalse( $this->is_tracking_allowed( $insights ) );
		$this->assertFalse( $insights->has_user_consented() );

		$allow_tracking = get_option( 'wpins_allow_tracking' );
		$this->assertArrayNotHasKey( 'notificationx', (array) $allow_tracking );

		delete_option( 'nx_test_privacy' );
	}

	/**
	 * The deactivation feedback form is sent with the insights data, so only
	 * a site that opted in sees it.
	 */
	public function test_deactivation_feedback_form_requires_consent() {
		$insights = $this->fresh_insights();
		$links    = [ 'deactivate' => '<a href="#">Deactivate</a>' ];

		delete_option( 'wpins_allow_tracking' );
		$without_consent = $insights->deactivate_action_links( $links );
		$this->assertStringNotContainsString( 'wpinsights-goodbye-form', $without_consent['deactivate'] );

		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		$with_consent = $insights->deactivate_action_links( $links );
		$this->assertStringContainsString( 'wpinsights-goodbye-form', $with_consent['deactivate'] );
	}

	/**
	 * The programmatic opt-out hides the feedback form even after consent.
	 */
	public function test_programmatic_opt_out_hides_the_deactivation_feedback_form() {
		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		$insights = $this->fresh_insights( [ 'options' => [ 'nx_test_privacy' ] ] );
		$links    = [ 'deactivate' => '<a href="#">Deactivate</a>' ];

		update_option( 'nx_test_privacy', [ 'wpins_opt_out' => true ] );

		$opted_out = $insights->deactivate_action_links( $links );
		$this->assertStringNotContainsString( 'wpinsights-goodbye-form', $opted_out['deactivate'] );

		delete_option( 'nx_test_privacy' );
	}

	/**
	 * Activation schedules tracking only for a site that opted in, and does
	 * not register a duplicate when it runs again.
	 */
	public function test_activation_schedules_tracking_only_with_consent() {
		wp_clear_scheduled_hook( 'put_do_weekly_action' );

		$insights = $this->fresh_insights();
		// The WP test bootstrap defines DISABLE_WP_CRON, which the constructor
		// caches; with it set nothing is ever scheduled and this test proves
		// nothing. Clear the cached flag.
		$cron_off = new ReflectionProperty( $insights, 'disabled_wp_cron' );
		$cron_off->setAccessible( true );
		$cron_off->setValue( $insights, false );

		$insights->activate_this_plugin();
		$this->assertFalse( wp_next_scheduled( 'put_do_weekly_action' ), 'Nothing is scheduled without an opt-in.' );

		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		$insights->activate_this_plugin();
		$scheduled = wp_next_scheduled( 'put_do_weekly_action' );
		$this->assertNotFalse( $scheduled, 'A re-activation of an opted-in site schedules tracking.' );

		$insights->activate_this_plugin();
		$this->assertSame( $scheduled, wp_next_scheduled( 'put_do_weekly_action' ), 'No duplicate schedule is created.' );

		wp_clear_scheduled_hook( 'put_do_weekly_action' );
	}
}
