<?php
/**
 * The seasonal offer campaign (Black Friday / Cyber Monday 2026): the offer notice on the
 * WordPress Dashboard and the pointer beside the NotificationX menu item.
 *
 * The notice is one block in Admin::notices() plus one CSS block keyed on the notice's id. The
 * two halves are tied together only by that id: a notice whose CSS names another id renders
 * unstyled, and nothing else reports it. The notice block is asserted against the source,
 * because the notice library needs a WordPress admin request. The pointer
 * (Admin\OfferPointer) is exercised directly, with the clock passed in, so the suite does not
 * depend on today's date.
 *
 * See docs/features/offer-campaign.md.
 *
 * @package Notificationx
 */

use NotificationX\Admin\OfferPointer;

/**
 * @group offer-campaign
 */
class Test_Offer_Campaign extends WP_UnitTestCase {

	const NOTICE_ID = 'nx_bfcm_2026';

	const SELECTOR = '#wpnotice-notificationx-nx_bfcm_2026';

	const LINK = 'https://notificationx.com/bfcm2026/admin-notice';

	/**
	 * A moment inside the campaign.
	 *
	 * @var int
	 */
	private $during;

	/**
	 * A moment after the campaign.
	 *
	 * @var int
	 */
	private $after;

	/**
	 * Administrator id.
	 *
	 * @var int
	 */
	private $admin;

	public function setUp(): void {
		parent::setUp();

		$this->during = (int) strtotime( '12:00:00pm 1st December, 2026' );
		$this->after  = (int) strtotime( '12:00:00pm 20th December, 2026' );
		$this->admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );

		delete_option( OfferPointer::PRIORITY_OPTION );
		// A free site, whether or not the test run loaded NotificationX Pro.
		add_filter( 'nx_offer_pointer_pro_active', '__return_false' );
		wp_set_current_user( $this->admin );
		set_current_screen( 'dashboard' );

		// Core registers `wp-pointer` on every admin request. An earlier test may have replaced
		// the global style registry, and an unregistered handle is never queued.
		if ( ! wp_style_is( 'wp-pointer', 'registered' ) ) {
			wp_register_style( 'wp-pointer', false, array(), '1' );
		}
		if ( ! wp_script_is( 'wp-pointer', 'registered' ) ) {
			wp_register_script( 'wp-pointer', false, array(), '1', true );
		}
	}

	public function tearDown(): void {
		delete_option( OfferPointer::PRIORITY_OPTION );
		remove_all_filters( 'nx_offer_pointer_pro_active' );
		wp_dequeue_script( OfferPointer::SCRIPT_HANDLE );
		wp_deregister_script( OfferPointer::SCRIPT_HANDLE );
		wp_dequeue_style( 'wp-pointer' );
		set_current_screen( 'front' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * A plugin file's contents.
	 *
	 * @param string $relative Path from the plugin root.
	 * @return string
	 */
	private function read( $relative ) {
		$path = dirname( __DIR__ ) . '/' . $relative;
		$this->assertFileExists( $path );

		return (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
	}

	/**
	 * The campaign's notice block of Admin.php, from its comment to the notice registry hand-off.
	 *
	 * @return string
	 */
	private function notice_block() {
		$source = $this->read( 'includes/Admin/Admin.php' );
		$start  = strpos( $source, '// ' . self::NOTICE_ID . ' notice' );
		$end    = strpos( $source, 'self::$cache_bank->create_account' );

		$this->assertNotFalse( $start, 'Admin.php has no ' . self::NOTICE_ID . ' notice block.' );
		$this->assertNotFalse( $end );
		$this->assertGreaterThan( $start, $end );

		return substr( $source, $start, $end - $start );
	}

	/**
	 * A UTC timestamp.
	 *
	 * @param string $date Date and time.
	 * @return int
	 */
	private function utc( $date ) {
		return ( new DateTime( $date, new DateTimeZone( 'UTC' ) ) )->getTimestamp();
	}

	/**
	 * The JSON the pointer's script receives.
	 *
	 * @return array
	 */
	private function pointer_data() {
		$data = implode( '', (array) wp_scripts()->get_data( OfferPointer::SCRIPT_HANDLE, 'before' ) );
		$this->assertStringContainsString( 'window.nxOfferPointer = ', $data );

		$json = json_decode( rtrim( substr( $data, strpos( $data, '{' ) ), ';' ), true );
		$this->assertIsArray( $json );

		return $json;
	}

	// ---------------------------------------------------------------------
	// The dashboard notice.
	// ---------------------------------------------------------------------

	public function test_the_notice_is_registered_under_its_own_id() {
		$block = $this->notice_block();

		$this->assertSame( 1, preg_match_all( '/\$notices->add\(/', $block ) );
		$this->assertSame( 1, preg_match( "/\\\$notices->add\(\s*'" . self::NOTICE_ID . "',/", $block ) );
	}

	public function test_the_notice_links_to_the_campaign_page_in_a_new_tab() {
		$block = $this->notice_block();

		$this->assertSame( self::LINK, OfferPointer::URL );
		$this->assertStringContainsString( "href='\" . esc_url( OfferPointer::URL ) . \"' target='_blank'>Upgrade To Pro Now</a>", $block );
	}

	public function test_the_notice_runs_from_15_november_to_14_december_2026() {
		$block = $this->notice_block();

		// One campaign, two surfaces: the notice reads the pointer's dates.
		$this->assertSame( 1, preg_match( "/'start'\s*=>\s*strtotime\( OfferPointer::START \)/", $block ) );
		$this->assertSame( 1, preg_match( "/\"expire\"\s*=>\s*strtotime\( OfferPointer::EXPIRE \)/", $block ) );

		// WordPress runs PHP in UTC.
		$this->assertSame( '2026-11-15 00:00:00', gmdate( 'Y-m-d H:i:s', strtotime( OfferPointer::START ) ) );
		$this->assertSame( '2026-12-14 23:59:59', gmdate( 'Y-m-d H:i:s', strtotime( OfferPointer::EXPIRE ) ) );
	}

	public function test_the_notice_shows_on_the_dashboard_without_pro_and_can_be_dismissed() {
		$block = $this->notice_block();

		$this->assertSame( 1, preg_match( "/'screens'\s*=>\s*\[ 'dashboard' \]/", $block ) );
		$this->assertSame( 1, preg_match( "/'dismissible'\s*=>\s*true/", $block ) );
		$this->assertSame( 1, preg_match( "/'display_if'\s*=>\s*! is_plugin_active\( 'notificationx-pro\/notificationx-pro.php' \)/", $block ) );
		$this->assertStringContainsString( "<a class='nx-notice-action-dismiss dismiss-btn' data-dismiss='true' href='#'>I Want To Pay Full Price</a>", $block );
	}

	public function test_the_notice_copy_is_the_campaign_copy() {
		$block = $this->notice_block();

		$this->assertStringContainsString( '<strong>Black Friday Sale:</strong> Turn holiday traffic into customers with 15+ real-time social proof notifications, AI-powered features &amp; more – <strong>now up to 40% OFF!</strong>', $block );
	}

	public function test_the_notice_emoji_is_an_entity_wordpress_will_not_replace() {
		$block = $this->notice_block();

		// wp-emoji.js swaps a raw emoji for a Twemoji image; it skips `wp-exclude-emoji`.
		$this->assertStringContainsString( "<span class='nx-notice-emoji wp-exclude-emoji' aria-hidden='true'>&#x1F6CD;&#xFE0F;</span>", $block );
		// No raw emoji in the source (an entity survives any file encoding).
		$this->assertSame( 0, preg_match( '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $block ) );
	}

	public function test_the_stylesheet_follows_the_notice_id() {
		$css = $this->read( 'assets/admin/css/wpdeveloper-review-notice.css' );

		$this->assertStringContainsString( self::SELECTOR . ' {', $css );
		// The classes the markup uses are the ones the stylesheet names.
		$this->assertStringContainsString( self::SELECTOR . ' .nx-notice-action-button > a.button-primary', $css );
		$this->assertStringContainsString( self::SELECTOR . ' .nx-notice-action-button .nx-notice-action-dismiss.dismiss-btn', $css );
		$this->assertStringContainsString( self::SELECTOR . ' .wpnotice-content-wrapper .nx-notice-emoji', $css );
	}

	public function test_the_notice_emoji_is_sized_and_aligned_to_the_text() {
		$css   = $this->read( 'assets/admin/css/wpdeveloper-review-notice.css' );
		$start = strpos( $css, self::SELECTOR . ' .wpnotice-content-wrapper .nx-notice-emoji' );
		$this->assertNotFalse( $start );
		$rule = substr( $css, $start, (int) strpos( $css, '}', $start ) - $start );

		// 18px / -2px stood 4px out of the 14px line on each side.
		$this->assertSame( 1, preg_match( '/font-size:\s*14px;/', $rule ) );
		$this->assertSame( 1, preg_match( '/vertical-align:\s*0;/', $rule ) );
	}

	// ---------------------------------------------------------------------
	// The pointer: rules that need no request.
	// ---------------------------------------------------------------------

	/**
	 * @dataProvider moments
	 *
	 * @param string $date     UTC date and time.
	 * @param bool   $expected Whether the pointer may show.
	 */
	public function test_the_pointer_runs_from_15_november_to_14_december_2026( $date, $expected ) {
		$this->assertSame( $expected, OfferPointer::in_window( $this->utc( $date ) ) );
	}

	public function moments() {
		return array(
			'the second before it starts' => array( '2026-11-14 23:59:59', false ),
			'its first second'            => array( '2026-11-15 00:00:00', true ),
			'mid campaign'                => array( '2026-12-01 12:00:00', true ),
			'its last second'             => array( '2026-12-14 23:59:59', true ),
			'the second after it ends'    => array( '2026-12-15 00:00:00', false ),
			'a year later'                => array( '2027-11-20 12:00:00', false ),
		);
	}

	/**
	 * @dataProvider slot_holders
	 *
	 * @param mixed $current  The shared option's value.
	 * @param bool  $expected Whether NotificationX may show.
	 */
	public function test_a_smaller_number_in_the_shared_slot_goes_first( $current, $expected ) {
		$this->assertSame( $expected, OfferPointer::may_take_slot( $current ) );
	}

	public function slot_holders() {
		return array(
			'option missing'            => array( false, true ),
			'null (released)'           => array( null, true ),
			'empty string'              => array( '', true ),
			'zero'                      => array( 0, true ),
			'not a number'              => array( 'yes', true ),
			'Essential Addons (1)'      => array( 1, false ),
			'Essential Blocks (3)'      => array( '3', false ),
			'one before ours (4)'       => array( 4, false ),
			'ours (5)'                  => array( 5, true ),
			'ours, as stored ("5")'     => array( '5', true ),
			'Better Payment, after (9)' => array( 9, true ),
		);
	}

	public function test_the_pointer_priority_is_the_one_the_notices_use() {
		$this->assertSame( 5, OfferPointer::PRIORITY );
		$this->assertSame( 1, preg_match( "/'priority'\s*=>\s*5\b/", $this->read( 'includes/Admin/Admin.php' ) ) );
		$this->assertSame( '_wpdeveloper_plugin_pointer_priority', OfferPointer::PRIORITY_OPTION );
	}

	public function test_the_pointer_shows_on_the_dashboard_and_notificationx_pages_only() {
		foreach ( array( 'dashboard', 'toplevel_page_nx-admin', 'notificationx_page_nx-dashboard', 'notificationx_page_nx-edit', 'notificationx_page_nx-settings', 'notificationx_page_nx-analytics' ) as $screen ) {
			$this->assertTrue( OfferPointer::is_screen( $screen ), $screen );
		}

		foreach ( array( 'plugins', 'edit-post', 'options-general', 'users', 'dashboard-network', 'toplevel_page_nx-admin-network', 'notificationx', '' ) as $screen ) {
			$this->assertFalse( OfferPointer::is_screen( $screen ), $screen );
		}
	}

	public function test_the_pointer_colours_do_not_follow_the_admin_colour_scheme() {
		$css = OfferPointer::css();

		// A scheme recolours `.wp-pointer .wp-pointer-content h3` and `.wp-core-ui .button-primary`;
		// each rule here carries one class more, so the campaign blue holds.
		$this->assertSame( '#2271b1', OfferPointer::COLOR );
		$this->assertStringContainsString( '.wp-pointer.nx-offer-pointer .wp-pointer-content h3{', $css );
		$this->assertStringContainsString( 'background:#2271b1;color:#fff;', $css );
		$this->assertStringContainsString( '.wp-pointer.nx-offer-pointer .wp-pointer-buttons a.button-primary{', $css );
		$this->assertStringContainsString( '.wp-pointer.nx-offer-pointer .wp-pointer-buttons a.close{padding-left:19px;color:#2271b1;', $css );
		// Dismiss carries its own outlined icon, not core's dashicon.
		$this->assertStringContainsString( "a.close:before{content:'';", $css );
		$this->assertStringContainsString( 'data:image/svg+xml,', $css );
		// Button on the left, Dismiss on the right, on one row.
		$this->assertStringContainsString( '.nx-offer-pointer__actions{display:flex;align-items:center;justify-content:space-between;', $css );
		// No selector without the scope: nothing here may restyle another plugin's pointer.
		foreach ( array_filter( explode( '}', $css ) ) as $rule ) {
			foreach ( explode( ',', (string) strstr( $rule, '{', true ) ) as $selector ) {
				$this->assertStringStartsWith( '.wp-pointer.nx-offer-pointer', $selector, $rule );
			}
		}
	}

	public function test_the_pointer_dismissal_is_kept_per_campaign() {
		$this->assertSame( '_nx_offer_pointer_bfcm2026_dismissed', OfferPointer::meta_key() );
	}

	public function test_the_pointer_points_at_the_menu_link_not_the_whole_item() {
		// On NotificationX's pages the item holds the open submenu; its middle is not the label.
		$this->assertSame( '#toplevel_page_nx-admin > a.menu-top', OfferPointer::TARGET );
		// The menu slug the target is built from.
		$this->assertStringContainsString( "'nx-admin',", $this->read( 'includes/Admin/Admin.php' ) );
	}

	public function test_the_pointer_script_ships_and_builds_no_markup_from_strings() {
		$script = $this->read( 'assets/' . OfferPointer::SCRIPT );

		// Text only: every node is built with .text() / .attr(); .html() is only read.
		$this->assertSame( 0, preg_match( '/\.html\(\s*[^)\s]/', $script ) );
		$this->assertStringNotContainsString( 'innerHTML', $script );
		// WordPress places a pointer once, in page coordinates; the menu stays in view.
		$this->assertStringContainsString( "widget.style.position = 'fixed';", $script );
		$this->assertSame( 1, preg_match( "/addEventListener\(\s*'scroll'/", $script ) );
		$this->assertSame( 1, preg_match( "/addEventListener\(\s*'resize'/", $script ) );
		// The class the stylesheet is scoped to.
		$this->assertStringContainsString( "'wp-pointer nx-offer-pointer'", $script );
	}

	public function test_the_pointer_is_wired_with_or_without_pro() {
		$admin = $this->read( 'includes/Admin/Admin.php' );
		$call  = strpos( $admin, 'OfferPointer::register();' );
		$gate  = strpos( $admin, 'if( ! NotificationX::is_pro() ){' );

		$this->assertNotFalse( $call );
		$this->assertNotFalse( $gate );
		// Outside the "no Pro" block: with Pro it still has to give back a slot it holds.
		$this->assertGreaterThan( (int) strpos( $admin, "\n        }\n", (int) $gate ), $call );
	}

	public function test_the_pointer_class_is_in_the_committed_classmap() {
		// The classmap is committed; a class missing from it is a fatal error on release builds.
		$this->assertStringContainsString( "'NotificationX\\\\Admin\\\\OfferPointer'", $this->read( 'vendor/composer/autoload_classmap.php' ) );
		$this->assertStringContainsString( "'NotificationX\\\\Admin\\\\OfferPointer'", $this->read( 'vendor/composer/autoload_static.php' ) );
	}

	// ---------------------------------------------------------------------
	// The pointer: in a request.
	// ---------------------------------------------------------------------

	public function test_an_administrator_gets_the_pointer_during_the_campaign() {
		$this->assertTrue( OfferPointer::is_due( 'dashboard', $this->during ) );
		$this->assertTrue( OfferPointer::is_due( 'toplevel_page_nx-admin', $this->during ) );
	}

	public function test_the_pointer_is_not_due_outside_the_dates_or_on_another_screen() {
		$this->assertFalse( OfferPointer::is_due( 'dashboard', $this->after ) );
		$this->assertFalse( OfferPointer::is_due( 'dashboard', (int) strtotime( '12:00:00pm 1st November, 2026' ) ) );
		$this->assertFalse( OfferPointer::is_due( 'edit-post', $this->during ) );
		$this->assertFalse( OfferPointer::is_due( 'plugins', $this->during ) );
	}

	public function test_pro_never_gets_the_pointer() {
		remove_all_filters( 'nx_offer_pointer_pro_active' );
		add_filter( 'nx_offer_pointer_pro_active', '__return_true' );

		$this->assertFalse( OfferPointer::is_due( 'dashboard', $this->during ) );
		$this->assertFalse( OfferPointer::enqueue( $this->during ) );
		$this->assertFalse( wp_script_is( OfferPointer::SCRIPT_HANDLE, 'enqueued' ) );
		$this->assertFalse( get_option( OfferPointer::PRIORITY_OPTION ) );
	}

	public function test_a_user_who_cannot_manage_options_never_gets_the_pointer() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertFalse( OfferPointer::is_due( 'dashboard', $this->during ) );
		$this->assertFalse( OfferPointer::enqueue( $this->during ) );
	}

	public function test_loading_the_pointer_takes_the_shared_slot_and_prints_the_campaign() {
		$this->assertTrue( OfferPointer::enqueue( $this->during ) );

		$this->assertTrue( wp_script_is( OfferPointer::SCRIPT_HANDLE, 'enqueued' ), 'script' );
		$this->assertTrue( wp_style_is( 'wp-pointer', 'enqueued' ), 'style' );

		$css = implode( '', (array) wp_styles()->get_data( 'wp-pointer', 'after' ) );
		$this->assertStringContainsString( '.wp-pointer.nx-offer-pointer', $css );
		$this->assertSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );

		$script = wp_scripts()->registered[ OfferPointer::SCRIPT_HANDLE ];
		$this->assertContains( 'wp-pointer', $script->deps );
		$this->assertContains( 'jquery', $script->deps );
		$this->assertStringEndsWith( 'assets/admin/js/offer-pointer.js', $script->src );

		$json = $this->pointer_data();
		$this->assertSame( 'Black Friday Is On!', $json['title'] );
		$this->assertSame( 'Up to 40% off NotificationX Pro. Unlock real-time sales and conversion notifications that turn visitors into customers.', $json['text'] );
		$this->assertSame( 'View Deals', $json['button'] );
		$this->assertSame( 'Dismiss', $json['dismiss'] );
		$this->assertSame( self::LINK, $json['url'] );
		$this->assertSame( OfferPointer::TARGET, $json['target'] );
		$this->assertSame( OfferPointer::AJAX_ACTION, $json['action'] );
		$this->assertSame( 'left', $json['edge'] );
		$this->assertSame( 1, wp_verify_nonce( $json['nonce'], OfferPointer::NONCE_ACTION ) );
	}

	public function test_another_plugins_pointer_with_a_smaller_number_goes_first() {
		update_option( OfferPointer::PRIORITY_OPTION, 3 );

		$this->assertFalse( OfferPointer::enqueue( $this->during ) );
		$this->assertFalse( wp_script_is( OfferPointer::SCRIPT_HANDLE, 'enqueued' ) );
		// The other plugin's claim is left alone.
		$this->assertSame( 3, (int) get_option( OfferPointer::PRIORITY_OPTION ) );
	}

	public function test_the_pointer_goes_before_a_plugin_with_a_larger_number() {
		update_option( OfferPointer::PRIORITY_OPTION, 9 );

		$this->assertTrue( OfferPointer::enqueue( $this->during ) );
		$this->assertSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );
	}

	public function test_a_dismissal_is_kept_for_that_user_and_frees_the_slot() {
		OfferPointer::enqueue( $this->during );
		$this->assertSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );

		OfferPointer::record_dismissal( $this->admin );

		$this->assertTrue( OfferPointer::is_dismissed( $this->admin ) );
		$this->assertFalse( OfferPointer::is_due( 'dashboard', $this->during ) );
		$this->assertNotSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );
		$this->assertTrue( OfferPointer::may_take_slot( get_option( OfferPointer::PRIORITY_OPTION ) ) );

		// Another administrator has not dismissed it.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertTrue( OfferPointer::is_due( 'dashboard', $this->during ) );
	}

	public function test_a_dismissal_never_releases_another_plugins_slot() {
		update_option( OfferPointer::PRIORITY_OPTION, 3 );

		OfferPointer::record_dismissal( $this->admin );

		$this->assertSame( 3, (int) get_option( OfferPointer::PRIORITY_OPTION ) );
	}

	public function test_the_slot_is_given_back_once_the_campaign_is_over() {
		update_option( OfferPointer::PRIORITY_OPTION, OfferPointer::PRIORITY );

		$this->assertFalse( OfferPointer::release_when_over( $this->during ) );
		$this->assertSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );

		$this->assertTrue( OfferPointer::release_when_over( $this->after ) );
		$this->assertNotSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );
	}

	public function test_the_slot_is_given_back_when_pro_is_activated() {
		update_option( OfferPointer::PRIORITY_OPTION, OfferPointer::PRIORITY );
		remove_all_filters( 'nx_offer_pointer_pro_active' );
		add_filter( 'nx_offer_pointer_pro_active', '__return_true' );

		$this->assertTrue( OfferPointer::release_when_over( $this->during ) );
		$this->assertNotSame( OfferPointer::PRIORITY, (int) get_option( OfferPointer::PRIORITY_OPTION ) );
	}

	public function test_the_dismiss_request_is_registered_for_logged_in_users_only() {
		OfferPointer::register();

		$this->assertNotFalse( has_action( 'wp_ajax_' . OfferPointer::AJAX_ACTION, array( OfferPointer::class, 'dismiss' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_nopriv_' . OfferPointer::AJAX_ACTION ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( OfferPointer::class, 'on_enqueue_scripts' ) ) );
		$this->assertNotFalse( has_action( 'admin_init', array( OfferPointer::class, 'on_admin_init' ) ) );
	}

	public function test_the_dismiss_request_needs_the_nonce() {
		$_POST['nonce']    = 'wrong';
		$_REQUEST['nonce'] = 'wrong';
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function ( $message ) {
					throw new WPDieException( (string) $message );
				};
			}
		);

		try {
			OfferPointer::dismiss();
			$this->fail( 'A dismissal without a valid nonce was accepted.' );
		} catch ( WPDieException $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		} finally {
			unset( $_POST['nonce'], $_REQUEST['nonce'] );
			remove_all_filters( 'wp_doing_ajax' );
			remove_all_filters( 'wp_die_ajax_handler' );
		}

		$this->assertFalse( OfferPointer::is_dismissed( $this->admin ) );
	}
}
