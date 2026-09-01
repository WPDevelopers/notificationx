<?php
/**
 * Regression tests for NX-02, NX-03, NX-04 and NX-05.
 *
 * NX-02  /builder resolves `read_notificationx` -- the lowest capability the
 *        product defines -- yet returned the entire settings blob, credentials
 *        included. `settingsRedirect` only told the admin app not to render the
 *        settings screen.
 * NX-03  /import and /export resolved `edit_notificationx` while every other
 *        settings-bearing route required `edit_notificationx_settings`. Import
 *        also passed a client-supplied post array to `wp_insert_post()`.
 * NX-04  `elementor_id` is attacker-controlled, and export dereferenced it with
 *        `get_post()` + `get_post_meta()` and no ownership check.
 * NX-05  The `delete_users` gate on the role selectors lived in a form-schema
 *        filter, so it never ran on save.
 *
 * @package Notificationx
 */

use NotificationX\Core\REST;
use NotificationX\Core\Database;
use NotificationX\Admin\Settings;

class Test_Security_Settings_Boundary extends WP_UnitTestCase {

	protected $server;
	protected $viewer_id;
	protected $creator_id;
	protected $settings_admin_id;
	protected $super_admin_id;

	/** Seeded credentials that must never leak. */
	const SECRETS = array(
		'mailchimp_api_key' => 'SECRET-MAILCHIMP',
		'ga_client_secret'  => 'SECRET-GA-CLIENT',
		'envato_token'      => 'SECRET-ENVATO',
	);

	public function setUp(): void {
		parent::setUp();

		Database::get_instance()->Create_DB();

		REST::get_instance();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->viewer_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new WP_User( $this->viewer_id ) )->add_cap( 'read_notificationx' );

		$this->creator_id = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$creator          = new WP_User( $this->creator_id );
		$creator->add_cap( 'read_notificationx' );
		$creator->add_cap( 'edit_notificationx' );

		// Editor deliberately: holds settings authority but NOT `delete_users`.
		$this->settings_admin_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$settings_admin          = new WP_User( $this->settings_admin_id );
		$settings_admin->add_cap( 'read_notificationx' );
		$settings_admin->add_cap( 'edit_notificationx' );
		$settings_admin->add_cap( 'edit_notificationx_settings' );

		$this->super_admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->seed_settings();
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	private function seed_settings( $extra = array() ) {
		$settings = array_merge(
			self::SECRETS,
			array(
				'nx_pa_settings'      => array( 'token_info' => array( 'refresh_token' => 'SECRET-REFRESH' ) ),
				'modules_woocommerce' => true,
				'settings_roles'      => array( 'administrator' ),
				'notification_roles'  => array( 'administrator' ),
			),
			$extra
		);
		Settings::get_instance()->set( 'settings', $settings );
	}

	private function dispatch( $method, $route, $params = array() ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $this->server->dispatch( $request );
	}

	private function assertNoSecrets( $blob, $message ) {
		$encoded = wp_json_encode( $blob );
		foreach ( self::SECRETS as $key => $value ) {
			$this->assertStringNotContainsString( $value, $encoded, $message . " (leaked {$key})" );
		}
		$this->assertStringNotContainsString( 'SECRET-REFRESH', $encoded, $message . ' (leaked OAuth refresh token)' );
	}

	/* =====================================================================
	 * NX-02 -- settings disclosure below settings authority
	 * ================================================================== */

	public function test_viewer_does_not_receive_secrets_in_form_data() {
		wp_set_current_user( $this->viewer_id );
		$this->assertNoSecrets( Settings::get_instance()->get_form_data(), 'read_notificationx received credentials' );
	}

	public function test_creator_does_not_receive_secrets_in_form_data() {
		wp_set_current_user( $this->creator_id );
		$this->assertNoSecrets( Settings::get_instance()->get_form_data(), 'edit_notificationx received credentials' );
	}

	public function test_logged_out_does_not_receive_secrets() {
		wp_set_current_user( 0 );
		$this->assertNoSecrets( Settings::get_instance()->get_form_data(), 'anonymous received credentials' );
	}

	public function test_settings_admin_still_receives_secrets() {
		wp_set_current_user( $this->settings_admin_id );
		$data = Settings::get_instance()->get_form_data();

		$this->assertSame(
			'SECRET-MAILCHIMP',
			isset( $data['values']['mailchimp_api_key'] ) ? $data['values']['mailchimp_api_key'] : null,
			'A settings administrator can no longer read back their own API key.'
		);
	}

	public function test_non_secret_settings_survive_redaction() {
		wp_set_current_user( $this->creator_id );
		$data = Settings::get_instance()->get_form_data();

		$this->assertTrue(
			! empty( $data['values']['modules_woocommerce'] ),
			'Redaction removed ordinary configuration, not just credentials.'
		);
	}

	public function test_builder_route_does_not_leak_secrets() {
		wp_set_current_user( $this->viewer_id );
		$response = $this->dispatch( 'GET', '/notificationx/v1/builder' );

		$this->assertNoSecrets( $response->get_data(), '/builder leaked credentials to read_notificationx' );
	}

	/* =====================================================================
	 * NX-03 -- import/export capability boundary
	 * ================================================================== */

	public function test_creator_cannot_export_settings() {
		wp_set_current_user( $this->creator_id );
		$response = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-settings' => 1 ) );

		$this->assertSame( 403, $response->get_status(), 'edit_notificationx was allowed to export settings.' );
		$this->assertNoSecrets( $response->get_data(), 'Denied export still returned credentials' );
	}

	public function test_creator_cannot_import_settings() {
		wp_set_current_user( $this->creator_id );

		$payload  = wp_json_encode( array( 'settings' => array( 'modules_woocommerce' => false ) ) );
		$response = $this->dispatch( 'POST', '/notificationx/v1/import', array( 'import' => $payload ) );

		$this->assertSame( 403, $response->get_status(), 'edit_notificationx was allowed to replace settings.' );
		$this->assertTrue(
			(bool) Settings::get_instance()->get( 'settings.modules_woocommerce' ),
			'Settings were modified despite the request being denied.'
		);
	}

	public function test_settings_admin_export_omits_secrets() {
		wp_set_current_user( $this->settings_admin_id );
		$response = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-settings' => 1 ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $data['data']['download']['settings'], 'Settings export returned nothing at all.' );
		$this->assertNoSecrets( $data, 'Settings export contained credentials' );
		$this->assertArrayHasKey(
			'modules_woocommerce',
			$data['data']['download']['settings'],
			'Export dropped ordinary configuration along with the credentials.'
		);
	}

	/**
	 * The round trip must not blank working integrations: an export has its
	 * credentials stripped, so importing one has to restore what is stored.
	 */
	public function test_importing_a_redacted_export_preserves_stored_secrets() {
		wp_set_current_user( $this->settings_admin_id );

		$export  = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-settings' => 1 ) )->get_data();
		$payload = wp_json_encode( array( 'settings' => $export['data']['download']['settings'] ) );

		$this->dispatch( 'POST', '/notificationx/v1/import', array( 'import' => $payload ) );

		$this->assertSame(
			'SECRET-MAILCHIMP',
			Settings::get_instance()->get( 'settings.mailchimp_api_key' ),
			'Importing a redacted export destroyed a stored API key.'
		);
		$this->assertSame(
			'SECRET-REFRESH',
			Settings::get_instance()->get( 'settings.nx_pa_settings' )['token_info']['refresh_token'],
			'Importing a redacted export destroyed the stored OAuth refresh token.'
		);
	}

	public function test_settings_admin_can_still_change_a_secret() {
		wp_set_current_user( $this->settings_admin_id );

		Settings::get_instance()->save_settings( array( 'mailchimp_api_key' => 'ROTATED' ) );

		$this->assertSame(
			'ROTATED',
			Settings::get_instance()->get( 'settings.mailchimp_api_key' ),
			'Preservation logic blocked a legitimate credential rotation.'
		);
	}

	/* =====================================================================
	 * NX-03 -- arbitrary post insertion through import
	 * ================================================================== */

	private function malicious_import_payload( $elementor_id = 4242 ) {
		return wp_json_encode( array(
			'notifications' => array(
				array(
					'nx_id'        => 1,
					'source'       => 'press_bar',
					'type'         => 'press_bar',
					'themes'       => 'theme-one',
					'elementor_id' => $elementor_id,
				),
			),
			'elementor'     => array(
				$elementor_id => array(
					'post' => array(
						'ID'          => 9999,
						'post_title'  => 'Injected Page',
						'post_type'   => 'page',
						'post_status' => 'publish',
						'post_author' => 1,
					),
					'meta' => array(
						'_elementor_data'  => array( '[]' ),
						'_wp_page_template' => array( 'elementor_canvas' ),
						'nx_evil_meta'     => array( 'pwned' ),
					),
				),
			),
		) );
	}

	public function test_import_cannot_choose_post_type_status_or_author() {
		wp_set_current_user( $this->creator_id );

		$this->dispatch( 'POST', '/notificationx/v1/import', array( 'import' => $this->malicious_import_payload() ) );

		$this->assertEmpty(
			get_posts( array( 'post_type' => 'page', 'title' => 'Injected Page', 'post_status' => 'any' ) ),
			'Import created a page of the attacker\'s choosing.'
		);

		$created = get_posts( array( 'post_type' => 'nx_bar', 'post_status' => 'any', 'numberposts' => -1 ) );
		$this->assertNotEmpty( $created, 'The legitimate nx_bar document was not created.' );

		$doc = $created[0];
		$this->assertSame( 'nx_bar', $doc->post_type );
		$this->assertSame( 'pending', $doc->post_status, 'A contributor managed to publish through import.' );
		$this->assertSame( (string) $this->creator_id, (string) $doc->post_author, 'Import honoured an attacker-supplied author.' );
	}

	public function test_import_drops_meta_outside_the_allowlist() {
		wp_set_current_user( $this->creator_id );

		$this->dispatch( 'POST', '/notificationx/v1/import', array( 'import' => $this->malicious_import_payload() ) );

		$created = get_posts( array( 'post_type' => 'nx_bar', 'post_status' => 'any', 'numberposts' => -1 ) );
		$this->assertNotEmpty( $created );

		$this->assertSame(
			'',
			(string) get_post_meta( $created[0]->ID, 'nx_evil_meta', true ),
			'Import wrote an arbitrary meta key.'
		);

		// `_elementor_data` rather than `_wp_page_template`: core re-validates
		// the page template inside wp_update_post() and resets it to 'default'
		// when the template is not registered, which it is not while Elementor
		// is inactive. That would make the assertion depend on the environment
		// rather than on the allowlist.
		$this->assertSame(
			'[]',
			get_post_meta( $created[0]->ID, '_elementor_data', true ),
			'Import dropped an allowlisted meta key.'
		);
	}

	/* =====================================================================
	 * NX-04 -- arbitrary post/postmeta read through export
	 * ================================================================== */

	public function test_export_will_not_dereference_a_foreign_elementor_id() {
		wp_set_current_user( $this->creator_id );

		$victim_id = self::factory()->post->create( array(
			'post_type'    => 'page',
			'post_title'   => 'Private Board Minutes',
			'post_content' => 'CONFIDENTIAL-BODY',
		) );
		add_post_meta( $victim_id, 'billing_address', 'CONFIDENTIAL-META' );

		// The notification's own data blob is client-controlled, so the linked
		// ID can point anywhere; get_posts() merges it up to the top level.
		add_filter( 'nx_get_posts', function () use ( $victim_id ) {
			return array(
				array(
					'nx_id'        => 1,
					'source'       => 'press_bar',
					'elementor_id' => $victim_id,
					'enabled'      => true,
				),
			);
		} );

		$response = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-notification' => 1 ) );
		$encoded  = wp_json_encode( $response->get_data() );

		$this->assertStringNotContainsString( 'CONFIDENTIAL-META', $encoded, 'Export leaked postmeta of an unrelated post.' );
		$this->assertStringNotContainsString( 'CONFIDENTIAL-BODY', $encoded, 'Export leaked the body of an unrelated post.' );
		$this->assertStringNotContainsString( 'Private Board Minutes', $encoded, 'Export leaked the title of an unrelated post.' );
	}

	public function test_export_still_includes_a_genuine_nx_bar_document() {
		wp_set_current_user( $this->creator_id );

		$bar_id = self::factory()->post->create( array( 'post_type' => 'nx_bar', 'post_title' => 'Real Bar' ) );
		add_post_meta( $bar_id, '_elementor_data', '[]' );

		add_filter( 'nx_get_posts', function () use ( $bar_id ) {
			return array(
				array(
					'nx_id'        => 1,
					'source'       => 'press_bar',
					'elementor_id' => $bar_id,
					'enabled'      => true,
				),
			);
		} );

		$response = $this->dispatch( 'POST', '/notificationx/v1/export', array( 'export-notification' => 1 ) );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'elementor', $data['data']['download'], 'A genuine nx_bar design was dropped from export.' );
	}

	/* =====================================================================
	 * NX-05 -- role escalation through /settings
	 * ================================================================== */

	public function test_settings_admin_cannot_rewrite_role_assignments() {
		wp_set_current_user( $this->settings_admin_id );

		Settings::get_instance()->save_settings( array(
			'settings_roles'     => array( 'subscriber' ),
			'notification_roles' => array( 'subscriber' ),
		) );

		$this->assertSame(
			array( 'administrator' ),
			Settings::get_instance()->get( 'settings.settings_roles' ),
			'A user without delete_users granted settings authority to subscribers.'
		);
		$this->assertSame(
			array( 'administrator' ),
			Settings::get_instance()->get( 'settings.notification_roles' ),
			'A user without delete_users rewrote notification role assignments.'
		);
	}

	public function test_delete_settings_cannot_launder_a_role_change() {
		wp_set_current_user( $this->settings_admin_id );

		Settings::get_instance()->save_settings( array(
			'delete_settings' => true,
			'settings_roles'  => array( 'subscriber' ),
		) );

		$this->assertNotContains(
			'subscriber',
			(array) Settings::get_instance()->get( 'settings.settings_roles' ),
			'A settings reset was used to smuggle a role change through.'
		);
	}

	public function test_administrator_can_still_manage_roles() {
		wp_set_current_user( $this->super_admin_id );

		Settings::get_instance()->save_settings( array( 'settings_roles' => array( 'editor' ) ) );

		// `get_selected_roles()` always prepends `administrator` so that an
		// admin cannot lock themselves out; that is product behaviour, not drift.
		$this->assertSame(
			array( 'administrator', 'editor' ),
			Settings::get_instance()->get( 'settings.settings_roles' ),
			'An administrator can no longer manage role assignments.'
		);
	}
}
