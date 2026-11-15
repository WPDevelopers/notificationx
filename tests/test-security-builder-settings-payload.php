<?php
/**
 * What the admin app receives about global settings, per capability.
 *
 * `/builder` resolves `read_notificationx`, the lowest NotificationX capability,
 * and carries `Settings::get_form_data()`. A denylist redaction let through
 * nested OAuth tokens (`yt_settings.token_info`), keys nobody registered
 * (`freemius_dev_sk`, leftovers of older versions) and credentials placed in
 * field defaults (the integration API key is the default of Pro's Zapier and
 * Brevo webhook fields). Users without `edit_notificationx_settings` now get no
 * settings form and only an allowlist of values.
 *
 * @package Notificationx
 */

use NotificationX\Core\REST;
use NotificationX\Core\Database;
use NotificationX\Admin\Settings;
use NotificationX\Abilities\Read\GetSettings;

class Test_Security_Builder_Settings_Payload extends WP_UnitTestCase {

	protected $server;
	protected $viewer_id;
	protected $creator_id;
	protected $settings_admin_id;
	protected $subscriber_id;

	/** Stored values that must not reach a user without settings authority. */
	const HIDDEN = array(
		'yt access token'     => 'SECRET-YT-ACCESS',
		'yt refresh token'    => 'SECRET-YT-REFRESH',
		'yt auth code'        => 'SECRET-YT-AUTHCODE',
		'freemius secret key' => 'SECRET-FREEMIUS-SK',
		'brevo webhook url'   => 'SECRET-WEBHOOK-KEY',
		'unregistered key'    => 'SECRET-UNREGISTERED',
		'report email'        => 'reports@example.invalid',
		'field default'       => 'SECRET-FIELD-DEFAULT',
	);

	public function setUp(): void {
		parent::setUp();

		Database::get_instance()->Create_DB();

		REST::get_instance();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		// "View Notification" delegated to authors.
		$this->viewer_id = self::factory()->user->create( array( 'role' => 'author' ) );
		( new WP_User( $this->viewer_id ) )->add_cap( 'read_notificationx' );

		// "Create Notification" delegated to editors.
		$this->creator_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$creator          = new WP_User( $this->creator_id );
		$creator->add_cap( 'read_notificationx' );
		$creator->add_cap( 'edit_notificationx' );

		$this->settings_admin_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$settings_admin          = new WP_User( $this->settings_admin_id );
		$settings_admin->add_cap( 'read_notificationx' );
		$settings_admin->add_cap( 'edit_notificationx' );
		$settings_admin->add_cap( 'edit_notificationx_settings' );

		$this->subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		// A key-bearing field default, the way Pro's Zapier field carries the
		// integration API key. Pro adds the API Integrations tab, so use a tab
		// free always has.
		add_filter( 'nx_settings_tab_miscellaneous', array( $this, 'add_key_bearing_field' ) );

		$this->seed_settings();
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		wp_set_current_user( 0 );
		remove_filter( 'nx_settings_tab_miscellaneous', array( $this, 'add_key_bearing_field' ) );
		parent::tearDown();
	}

	public function add_key_bearing_field( $tab ) {
		$tab['fields']['nx_test_webhook_section'] = array(
			'type'   => 'section',
			'name'   => 'nx_test_webhook_section',
			'label'  => 'Test webhook',
			'fields' => array(
				'nx_test_webhook_key' => array(
					'name'     => 'nx_test_webhook_key',
					'type'     => 'text',
					'label'    => 'API Key',
					'default'  => 'SECRET-FIELD-DEFAULT',
					'readOnly' => true,
				),
			),
		);
		return $tab;
	}

	private function seed_settings() {
		Settings::get_instance()->set( 'settings', array(
			// Real shape: Pro stores the YouTube OAuth token nested.
			'yt_settings'                 => array(
				'token_info'  => array(
					'access_token'  => 'SECRET-YT-ACCESS',
					'refresh_token' => 'SECRET-YT-REFRESH',
				),
				'auth_code'   => 'SECRET-YT-AUTHCODE',
				'yt_app_type' => 'nx_app',
				'channels'    => array( 'UC1' => 'Channel' ),
			),
			'freemius_dev_id'             => '1234',
			'freemius_dev_sk'             => 'SECRET-FREEMIUS-SK',
			'brevo_webhook_url'           => 'https://example.test/wp-json/notificationx/v1/brevo?api_key=SECRET-WEBHOOK-KEY',
			// Left behind by an older version: no current code knows this key.
			'facebook_reviews_app_secret' => 'SECRET-UNREGISTERED',
			'reporting_email'             => 'reports@example.invalid',
			'openai_model'                => 'gpt-test-model',
			'openai_access_token'         => 'SECRET-OPENAI',
			'modules_woocommerce'         => true,
			'settings_roles'              => array( 'administrator' ),
			'notification_roles'          => array( 'administrator' ),
		) );
	}

	private function dispatch( $method, $route, $params = array() ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $this->server->dispatch( $request );
	}

	private function assertHidden( $blob, $message ) {
		$encoded = wp_json_encode( $blob );
		foreach ( self::HIDDEN as $label => $value ) {
			$this->assertStringNotContainsString( $value, $encoded, "{$message} (leaked {$label})" );
		}
		$this->assertStringNotContainsString( 'SECRET-OPENAI', $encoded, "{$message} (leaked openai token)" );
	}

	/* =====================================================================
	 * Users without settings authority
	 * ================================================================== */

	public function test_viewer_builder_route_carries_no_hidden_value() {
		wp_set_current_user( $this->viewer_id );
		$response = $this->dispatch( 'GET', '/notificationx/v1/builder' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertHidden( $response->get_data(), '/builder for the View role' );
	}

	public function test_creator_builder_route_carries_no_hidden_value() {
		wp_set_current_user( $this->creator_id );
		$response = $this->dispatch( 'GET', '/notificationx/v1/builder' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertHidden( $response->get_data(), '/builder for the Create role' );
	}

	public function test_non_settings_user_gets_no_settings_form() {
		wp_set_current_user( $this->creator_id );
		$data = Settings::get_instance()->get_form_data();

		$this->assertSame( array(), $data['tabs'], 'The settings form, field defaults included, went to a user who cannot edit settings.' );
		// The app still needs the wrapper keys it reads outside the form.
		$this->assertArrayHasKey( 'is_wpml_active', $data );
		$this->assertArrayHasKey( 'config', $data );
	}

	public function test_non_settings_user_gets_only_allowlisted_values() {
		wp_set_current_user( $this->viewer_id );
		$data = Settings::get_instance()->get_form_data();

		$this->assertSame( array( 'openai_model' => 'gpt-test-model' ), $data['savedValues'] );
		$this->assertSame( $data['savedValues'], $data['values'] );
	}

	public function test_allowlist_filter_cannot_add_a_secret() {
		$add = function ( $keys ) {
			$keys[] = 'modules_woocommerce';
			$keys[] = 'openai_access_token';
			$keys[] = 'freemius_dev_sk';
			return $keys;
		};
		add_filter( 'nx_builder_public_settings', $add );
		wp_set_current_user( $this->viewer_id );
		$values = Settings::get_instance()->get_form_data()['savedValues'];
		remove_filter( 'nx_builder_public_settings', $add );

		$this->assertTrue( $values['modules_woocommerce'], 'The allowlist filter could not add an ordinary key.' );
		$this->assertArrayNotHasKey( 'openai_access_token', $values );
		$this->assertArrayNotHasKey( 'freemius_dev_sk', $values );
	}

	public function test_public_builder_settings_tolerates_bad_input() {
		$this->assertSame( array(), Settings::public_builder_settings( false ) );

		$junk = function () {
			return array( 'openai_model', 42, null, array( 'x' ) );
		};
		add_filter( 'nx_builder_public_settings', $junk );
		$values = Settings::public_builder_settings( array( 'openai_model' => 'm', 'other' => 'o' ) );
		remove_filter( 'nx_builder_public_settings', $junk );

		$this->assertSame( array( 'openai_model' => 'm' ), $values );
	}

	public function test_subscriber_and_visitor_still_get_403_on_builder() {
		wp_set_current_user( $this->subscriber_id );
		$this->assertSame( 403, $this->dispatch( 'GET', '/notificationx/v1/builder' )->get_status() );

		wp_set_current_user( 0 );
		$this->assertContains( $this->dispatch( 'GET', '/notificationx/v1/builder' )->get_status(), array( 401, 403 ) );
	}

	/* =====================================================================
	 * Settings administrators
	 * ================================================================== */

	public function test_settings_admin_still_gets_the_full_form_and_values() {
		wp_set_current_user( $this->settings_admin_id );
		$data    = Settings::get_instance()->get_form_data();
		$encoded = wp_json_encode( $data );

		$this->assertNotEmpty( $data['tabs'], 'A settings administrator lost the settings form.' );
		$this->assertStringContainsString( 'SECRET-FIELD-DEFAULT', $encoded, 'A settings administrator lost a field default.' );
		$this->assertSame( 'SECRET-FREEMIUS-SK', $data['savedValues']['freemius_dev_sk'] );
		$this->assertSame( 'SECRET-YT-REFRESH', $data['savedValues']['yt_settings']['token_info']['refresh_token'] );
		$this->assertSame( 'SECRET-OPENAI', $data['savedValues']['openai_access_token'] );
	}

	public function test_saving_without_secrets_keeps_the_stored_ones() {
		wp_set_current_user( $this->settings_admin_id );

		// What a save from a stale or redacted copy of the form posts.
		Settings::get_instance()->save_settings( array( 'openai_model' => 'gpt-other' ) );

		$this->assertSame( 'SECRET-FREEMIUS-SK', Settings::get_instance()->get( 'settings.freemius_dev_sk' ) );
		$this->assertSame( 'SECRET-YT-REFRESH', Settings::get_instance()->get( 'settings.yt_settings' )['token_info']['refresh_token'] );
		$this->assertSame( 'gpt-other', Settings::get_instance()->get( 'settings.openai_model' ) );
	}

	public function test_settings_admin_can_still_change_freemius_secret() {
		wp_set_current_user( $this->settings_admin_id );

		Settings::get_instance()->save_settings( array( 'freemius_dev_sk' => 'ROTATED' ) );

		$this->assertSame( 'ROTATED', Settings::get_instance()->get( 'settings.freemius_dev_sk' ) );
	}

	/* =====================================================================
	 * Export / import and other outputs
	 * ================================================================== */

	public function test_export_omits_yt_settings_freemius_secret_and_webhook_url() {
		wp_set_current_user( $this->settings_admin_id );
		$data     = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-settings' => 1 ) )->get_data();
		$settings = $data['data']['download']['settings'];

		$this->assertArrayNotHasKey( 'yt_settings', $settings );
		$this->assertArrayNotHasKey( 'freemius_dev_sk', $settings );
		$this->assertArrayNotHasKey( 'brevo_webhook_url', $settings );
		$this->assertSame( '1234', $settings['freemius_dev_id'], 'Export dropped ordinary configuration.' );
	}

	public function test_reimporting_the_export_keeps_stored_credentials() {
		wp_set_current_user( $this->settings_admin_id );

		$export  = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-settings' => 1 ) )->get_data();
		$payload = wp_json_encode( array( 'settings' => $export['data']['download']['settings'] ) );
		$this->dispatch( 'POST', '/notificationx/v1/import', array( 'import' => $payload ) );

		$this->assertSame( 'SECRET-FREEMIUS-SK', Settings::get_instance()->get( 'settings.freemius_dev_sk' ) );
		$this->assertSame( 'SECRET-YT-ACCESS', Settings::get_instance()->get( 'settings.yt_settings' )['token_info']['access_token'] );
	}

	public function test_get_settings_ability_omits_registered_secrets() {
		$output = ( new GetSettings() )->execute( array() );

		$this->assertArrayNotHasKey( 'freemius_dev_sk', $output['settings'], 'freemius_dev_sk has no telling key name and went out unmasked.' );
		$this->assertArrayNotHasKey( 'yt_settings', $output['settings'] );
		$this->assertStringNotContainsString( 'SECRET-FREEMIUS-SK', wp_json_encode( $output ) );
	}

	public function test_secret_keys_list_is_unique_strings() {
		$junk = function ( $keys ) {
			$keys[] = 'yt_settings';
			$keys[] = 42;
			$keys[] = array( 'x' );
			return $keys;
		};
		add_filter( 'nx_secret_settings', $junk );
		$keys = Settings::get_secret_settings_keys();
		remove_filter( 'nx_secret_settings', $junk );

		$this->assertSame( array_values( array_unique( $keys ) ), $keys );
		$this->assertSame( $keys, array_filter( $keys, 'is_string' ) );
		$this->assertContains( 'yt_settings', $keys );
		$this->assertContains( 'freemius_dev_sk', $keys );
		$this->assertContains( 'brevo_webhook_url', $keys );
	}
}
