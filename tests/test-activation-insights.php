<?php
/**
 * Activation flow + insights data-collection tests for NotificationX.
 *
 * Covers the two invariants that the onboarding/insights refactor relies on:
 *
 *  1. The Setup Wizard is only auto-launched on a genuine first-ever
 *     activation — an abandoned wizard still counts as "already activated".
 *  2. WP Insights data collection is enabled from the backend and no longer
 *     depends on the consent notice or the Setup Wizard. The deactivation
 *     feedback form follows that same gate, so it is available from
 *     activation onwards; only the programmatic opt-out turns both off.
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
	 * Collection is active regardless of the consent state: never asked,
	 * accepted, or explicitly rejected.
	 */
	public function test_data_collection_is_independent_of_consent() {
		$insights = $this->fresh_insights();

		// Consent never given.
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );
		$this->assertFalse( $insights->has_user_consented() );

		// Consent accepted.
		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );
		$this->assertTrue( $insights->has_user_consented() );

		// Consent rejected ("No Thanks" removes the plugin from the array).
		update_option( 'wpins_allow_tracking', [ 'anotherplugin' => 'anotherplugin' ] );
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );
		$this->assertFalse( $insights->has_user_consented() );
	}

	/**
	 * Collection is active whatever the Setup Wizard state is.
	 */
	public function test_data_collection_is_independent_of_the_setup_wizard() {
		$insights = $this->fresh_insights();

		// Wizard never opened.
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );

		// Wizard abandoned (activated, never completed).
		update_option( 'nx_first_activation', time() );
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );

		// Wizard completed.
		update_option( SetupWizard::COMPLETED_OPTION, true );
		$this->assertTrue( $this->is_tracking_allowed( $insights ) );
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
	 * The deactivation feedback form follows collection, so it is available as
	 * soon as the plugin is activated — no opt-in notice, no Setup Wizard.
	 */
	public function test_deactivation_feedback_form_is_available_without_consent() {
		$insights = $this->fresh_insights();
		$links    = [ 'deactivate' => '<a href="#">Deactivate</a>' ];

		delete_option( 'wpins_allow_tracking' );
		$this->assertFalse( $insights->has_user_consented(), 'No consent has been recorded.' );

		$without_consent = $insights->deactivate_action_links( $links );
		$this->assertStringContainsString( 'wpinsights-goodbye-form', $without_consent['deactivate'] );

		update_option( 'wpins_allow_tracking', [ 'notificationx' => 'notificationx' ] );
		$with_consent = $insights->deactivate_action_links( $links );
		$this->assertStringContainsString( 'wpinsights-goodbye-form', $with_consent['deactivate'] );
	}

	/**
	 * The programmatic opt-out turns collection off entirely, and the feedback
	 * form goes with it.
	 */
	public function test_programmatic_opt_out_hides_the_deactivation_feedback_form() {
		$insights = $this->fresh_insights( [ 'options' => [ 'nx_test_privacy' ] ] );
		$links    = [ 'deactivate' => '<a href="#">Deactivate</a>' ];

		update_option( 'nx_test_privacy', [ 'wpins_opt_out' => true ] );

		$opted_out = $insights->deactivate_action_links( $links );
		$this->assertStringNotContainsString( 'wpinsights-goodbye-form', $opted_out['deactivate'] );

		delete_option( 'nx_test_privacy' );
	}

	/**
	 * Activation schedules the tracking event without consent, and does not
	 * register a duplicate when it runs again.
	 */
	public function test_activation_schedules_tracking_without_consent() {
		wp_clear_scheduled_hook( 'put_do_weekly_action' );

		$insights = $this->fresh_insights();
		$insights->activate_this_plugin();

		$scheduled = wp_next_scheduled( 'put_do_weekly_action' );
		$this->assertNotFalse( $scheduled, 'Tracking is scheduled on activation without any opt-in.' );

		$insights->activate_this_plugin();
		$this->assertSame( $scheduled, wp_next_scheduled( 'put_do_weekly_action' ), 'No duplicate schedule is created.' );

		wp_clear_scheduled_hook( 'put_do_weekly_action' );
	}
}
