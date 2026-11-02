<?php
/**
 * Tests for the free side of the built-in live visitor count (Live Visitors).
 *
 * Free ships only the source card, its module and generic hooks; NotificationX
 * Pro counts the visitors. Without a Pro that says it counts them
 * (`nx_live_visitors_supported`), nothing of it may run, and existing Google
 * Analytics notifications must behave as before.
 *
 * The JS seam (`nx_frontend_entry`, `notificationx:refresh`) is covered by
 * tests/js/frontend/entry-filter.test.js.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Settings;
use NotificationX\Core\REST;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\LiveVisitors\LiveVisitors;
use NotificationX\Types\PageAnalytics;

class Test_Live_Visitors_Seams extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	protected $modules;

	public function set_up() {
		parent::set_up();
		$this->modules = (array) Settings::get_instance()->get( 'settings.modules' );
	}

	public function tear_down() {
		Settings::get_instance()->set( 'settings.modules', $this->modules );
		remove_all_filters( 'nx_live_visitors_supported' );
		remove_all_filters( 'nx_live_viewers_available' );
		remove_all_filters( 'nx_live_visitors_themes' );
		remove_all_filters( 'nx_public_rest_routes' );
		unset( $GLOBALS['wp']->query_vars['rest_route'] );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	protected function set_modules( $modules ) {
		Settings::get_instance()->set( 'settings.modules', array_merge( $this->modules, $modules ) );
	}

	protected function add_public_routes( $routes ) {
		add_filter(
			'nx_public_rest_routes',
			function () use ( $routes ) {
				return $routes;
			}
		);
	}

	public function test_marker_constant_is_defined() {
		$this->assertTrue( defined( 'NOTIFICATIONX_LIVE_API' ) );
		$this->assertSame( 1, NOTIFICATIONX_LIVE_API );
	}

	public function test_source_is_registered_for_page_analytics() {
		$ext = ExtensionFactory::get_instance()->get( LiveVisitors::ID );
		$this->assertInstanceOf( LiveVisitors::class, $ext );
		$this->assertSame( 'page_analytics', $ext->types );
		$this->assertSame( 'modules_live_visitors', $ext->module );
		$this->assertTrue( $ext->is_pro );
	}

	public function test_not_supported_without_pro_even_if_filtered() {
		add_filter( 'nx_live_visitors_supported', '__return_true' );
		$this->assertFalse( LiveVisitors::is_supported() );
	}

	public function test_source_card_shows_the_upsell_without_pro() {
		$sources = LiveVisitors::get_instance()->__nx_sources( [] );
		$this->assertCount( 1, $sources );
		$this->assertSame( LiveVisitors::ID, $sources[0]['value'] );
		$this->assertTrue( $sources[0]['is_pro'] );
		$this->assertArrayNotHasKey( 'forced', (array) $sources[0]['popup'] );
	}

	public function test_unsupported_source_is_kept_off_the_page() {
		$ext = LiveVisitors::get_instance();
		$this->assertTrue( $ext->exclude_unsupported( false, [ 'source' => LiveVisitors::ID ] ) );
		$this->assertFalse( $ext->exclude_unsupported( false, [ 'source' => 'google' ] ) );
		$this->assertFalse( $ext->exclude_unsupported( false, [] ) );
		// Another filter's exclusion is never undone.
		$this->assertTrue( $ext->exclude_unsupported( true, [ 'source' => 'google' ] ) );
	}

	public function test_only_live_designs_are_offered() {
		$ext = LiveVisitors::get_instance();
		$this->assertSame( [ 'page_analytics_pa-theme-three' ], array_keys( $ext->get_themes() ) );
		$this->assertSame( [ 'page_analytics_res-pa-theme-three' ], array_keys( $ext->get_res_themes() ) );
	}

	public function test_add_ons_can_offer_their_own_live_design() {
		add_filter(
			'nx_live_visitors_themes',
			function ( $keep ) {
				$keep[] = 'page_analytics_pa-theme-one';
				return $keep;
			}
		);
		$this->assertSame(
			[ 'page_analytics_pa-theme-one', 'page_analytics_pa-theme-three' ],
			array_keys( LiveVisitors::get_instance()->get_themes() )
		);
	}

	public function test_total_site_view_is_hidden_for_this_source() {
		$templates = LiveVisitors::get_instance()->__notification_template( [] );
		$rules     = wp_json_encode( $templates['first_param']['options']['tag_siteview']['rules'] );
		$this->assertStringContainsString( '["!includes","source",["live_visitors"]]', $rules );
		// The live option stays available.
		$rules = wp_json_encode( $templates['first_param']['options']['tag_realtime_siteview']['rules'] );
		$this->assertStringNotContainsString( '!includes', $rules );
	}

	public function test_live_viewers_design_follows_the_ga_module_without_pro() {
		$this->set_modules( [ 'modules_google_analytics' => true ] );
		$this->assertTrue( LiveVisitors::live_viewers_available() );

		// Without Pro, the Live Visitors module alone cannot count viewers.
		$this->set_modules( [ 'modules_google_analytics' => false, 'modules_live_visitors' => true ] );
		$this->assertFalse( LiveVisitors::live_viewers_available() );

		add_filter( 'nx_live_viewers_available', '__return_true' );
		$this->assertTrue( LiveVisitors::live_viewers_available() );
	}

	public function test_page_analytics_still_selects_google_by_default() {
		$triggers = PageAnalytics::get_instance()->type_trigger( [] );
		$this->assertSame( '@source:google', $triggers['page_analytics'] );
	}

	public function test_page_analytics_selects_the_built_in_source_without_the_ga_module() {
		$this->set_modules( [ 'modules_google_analytics' => false, 'modules_live_visitors' => true ] );
		$triggers = PageAnalytics::get_instance()->type_trigger( [] );
		$this->assertSame( '@source:live_visitors', $triggers['page_analytics'] );
	}

	// nx_public_rest_routes: only routes in NotificationX's own namespace.

	public function test_public_rest_routes_default_to_none() {
		$rest = REST::get_instance();
		$this->assertSame( [], $rest->public_route_paths( '/notificationx/v1/' ) );
		$this->assertSame(
			[ '/notificationx/v1/notice', '/notificationx/v1/analytics', '/notificationx/v1/delete-cookies', '/notificationx/v1/send-rating' ],
			$rest->bb_exclude_endpoints( [], '' )
		);
	}

	public function test_public_rest_routes_accept_names_and_own_paths() {
		$this->add_public_routes( [ 'live', '/live/', 'notificationx/v1/live', '/notificationx/v1/stats/today/' ] );
		$this->assertSame(
			[ '/notificationx/v1/live', '/notificationx/v1/stats/today' ],
			REST::get_instance()->public_route_paths( '/notificationx/v1/' )
		);
	}

	public function test_public_rest_routes_reject_foreign_routes() {
		$this->setExpectedIncorrectUsage( 'NotificationX\Core\REST::public_route_paths' );
		$this->add_public_routes( [ 'wp/v2/users', '/wp/v2/users', 'myplugin/v1/thing', 'notificationx/v2/live', 'wp-json/wp/v2/users', 'live' ] );
		$rest = REST::get_instance();
		$this->assertSame( [ '/notificationx/v1/live' ], $rest->public_route_paths( '/notificationx/v1/' ) );
		$this->assertNotContains( '/wp-json/wp/v2/users', $rest->jwt_whitelist( [] ) );
		$this->assertNotContains( '/wp/v2/users', $rest->bb_exclude_endpoints( [], '' ) );
	}

	public function test_public_rest_routes_reject_malformed_names() {
		$this->setExpectedIncorrectUsage( 'NotificationX\Core\REST::public_route_paths' );
		$this->add_public_routes( [ '(?P<id>\d+)', '../settings', 'Live', 'a//b', '*', 'live?x=1', 'a b', 'a/b/c/d', str_repeat( 'a', 65 ), 42, null, '' ] );
		$this->assertSame( [], REST::get_instance()->public_route_paths( '/notificationx/v1/' ) );
	}

	public function test_public_rest_routes_survive_a_broken_filter() {
		$this->setExpectedIncorrectUsage( 'NotificationX\Core\REST::public_route_paths' );
		add_filter( 'nx_public_rest_routes', '__return_false' );
		$this->assertSame( [], REST::get_instance()->public_route_paths( '/notificationx/v1/' ) );
	}

	public function test_public_rest_routes_join_all_three_allowlists() {
		$this->add_public_routes( [ 'live' ] );
		$rest = REST::get_instance();

		$this->assertContains( '/notificationx/v1/live', $rest->bb_exclude_endpoints( [], '' ) );
		$this->assertContains( '/wp-json/notificationx/v1/live', $rest->jwt_whitelist( [] ) );

		$GLOBALS['wp']->query_vars['rest_route'] = '/notificationx/v1/live';
		$this->assertTrue( $rest->rest_authentication_errors( new WP_Error( 'rest_disabled', 'off' ) ) );

		// Other routes stay restricted.
		$GLOBALS['wp']->query_vars['rest_route'] = '/notificationx/v1/settings';
		$this->assertInstanceOf( WP_Error::class, $rest->rest_authentication_errors( new WP_Error( 'rest_disabled', 'off' ) ) );
	}

	public function test_public_route_runs_as_a_logged_out_visitor() {
		$this->add_public_routes( [ 'live' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$GLOBALS['wp']->query_vars['rest_route'] = '/notificationx/v1/live';

		// E.g. a failed cookie nonce check: let through, but not as that user.
		$this->assertTrue( REST::get_instance()->rest_authentication_errors( new WP_Error( 'rest_cookie_invalid_nonce', 'bad nonce' ) ) );
		$this->assertSame( 0, get_current_user_id() );
	}

	public function test_public_route_keeps_the_user_when_nothing_failed() {
		$this->add_public_routes( [ 'live' ] );
		$user = self::factory()->user->create();
		wp_set_current_user( $user );
		$GLOBALS['wp']->query_vars['rest_route'] = '/notificationx/v1/live';

		$this->assertNull( REST::get_instance()->rest_authentication_errors( null ) );
		$this->assertSame( $user, get_current_user_id() );
	}
}
