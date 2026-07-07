<?php
/**
 * REST registration tests for NotificationX.
 *
 * Verifies that after `rest_api_init` fires, the NotificationX REST namespace
 * (`notificationx/v1`) and its core routes are present in the REST server's
 * route table.
 *
 * All asserted routes were confirmed against the source:
 * - includes/Core/REST.php            (namespace `notificationx/v1`; /builder,
 *   /core-install, /settings, /miscellaneous, /get-data, /notice,
 *   /delete-cookies, /import, /export)
 * - includes/Core/Rest/Posts.php      (/nx)
 * - includes/Core/Rest/Analytics.php  (/analytics, /analytics/get)
 * - includes/Core/Rest/BulkAction.php (/bulk-action/delete)
 * - includes/Core/Rest/Integration.php(/api-connect)
 * - includes/Core/Rest/Popup.php      (/popup-submit)
 *
 * @package Notificationx
 */

use NotificationX\Core\REST;

class Test_REST extends WP_UnitTestCase {

	/**
	 * @var WP_REST_Server
	 */
	protected $server;

	public function setUp(): void {
		parent::setUp();

		// Ensure the REST subsystem is instantiated so its `rest_api_init`
		// hooks (registered in the constructors of REST and its Rest\* helpers)
		// are attached. Classes use the GetInstance trait -> ::get_instance().
		REST::get_instance();

		// Spin up a fresh REST server and fire the registration action, mirroring
		// the canonical WordPress core REST test bootstrap.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	/**
	 * The plugin's namespace must be registered on the server.
	 */
	public function test_namespace_is_registered() {
		$this->assertContains(
			'notificationx/v1',
			$this->server->get_namespaces(),
			'The notificationx/v1 REST namespace should be registered.'
		);
	}

	/**
	 * The namespace index route must exist in the route table.
	 */
	public function test_namespace_index_route_exists() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey(
			'/notificationx/v1',
			$routes,
			'The namespace index route /notificationx/v1 should be present.'
		);
	}

	/**
	 * Core routes registered directly by REST::register_routes() must exist.
	 */
	public function test_core_rest_routes_are_registered() {
		$routes = $this->server->get_routes();

		$expected = array(
			'/notificationx/v1/builder',
			'/notificationx/v1/core-install',
			'/notificationx/v1/settings',
			'/notificationx/v1/miscellaneous',
			'/notificationx/v1/get-data',
			'/notificationx/v1/notice',
			'/notificationx/v1/delete-cookies',
			'/notificationx/v1/import',
			'/notificationx/v1/export',
		);

		foreach ( $expected as $route ) {
			$this->assertArrayHasKey(
				$route,
				$routes,
				"Expected core REST route {$route} to be registered."
			);
		}
	}

	/**
	 * Routes registered by the Rest\* helper controllers must exist.
	 */
	public function test_rest_controller_routes_are_registered() {
		$routes = $this->server->get_routes();

		$expected = array(
			'/notificationx/v1/nx',              // Rest\Posts
			'/notificationx/v1/analytics',       // Rest\Analytics
			'/notificationx/v1/analytics/get',   // Rest\Analytics
			'/notificationx/v1/bulk-action/delete', // Rest\BulkAction
			'/notificationx/v1/api-connect',     // Rest\Integration
			'/notificationx/v1/popup-submit',    // Rest\Popup
		);

		foreach ( $expected as $route ) {
			$this->assertArrayHasKey(
				$route,
				$routes,
				"Expected REST controller route {$route} to be registered."
			);
		}
	}

	/**
	 * A registered route should expose at least one HTTP method endpoint.
	 */
	public function test_builder_route_has_endpoint_definition() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/notificationx/v1/builder', $routes );
		$this->assertNotEmpty(
			$routes['/notificationx/v1/builder'],
			'The /notificationx/v1/builder route should have at least one endpoint definition.'
		);
	}
}
