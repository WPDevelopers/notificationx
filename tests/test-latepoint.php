<?php
/**
 * Tests for the LatePoint integration (LatePointConversions).
 *
 * Covers the pure / near-pure logic in
 * includes/Extensions/LatePoint/LatePointConversions.php that needs no
 * LatePoint plugin present:
 *  - check_booking_eligibility() — the capture-time allowlist gate that
 *    stops cancelled bookings and hidden services from being published;
 *  - mask_service_name() and booking_link() — display-time filters;
 *  - parse_created_at() — the created_at string parser;
 *  - build_entry_data() — exercised via lightweight stdClass stubs, since
 *    its only LatePoint-specific behaviour is guarded by method_exists()
 *    checks that simply no-op against a stub;
 *  - the two class_exists('LatePoint') guard clauses in get_bookings() and
 *    reconcile(), which are reachable and meaningful without the plugin;
 *  - Type/Extension registration invariants, including that this remains a
 *    FREE (non-Pro) integration.
 *
 * Anything that genuinely requires LatePoint's own model classes
 * (\OsBookingModel property/query behaviour beyond the guard clauses above)
 * is marked skipped with a reason rather than faked.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\LatePoint\LatePointConversions;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Types\TypeFactory;
use NotificationX\Types\Types;

class Test_LatePoint_Conversions extends WP_UnitTestCase {

	/**
	 * @var LatePointConversions
	 */
	protected $ext;

	public function set_up() {
		parent::set_up();
		// get_instance() (not `new`), same as ExtensionFactory::register_extensions()
		// itself uses — this reuses the singleton the plugin bootstrap already built
		// and avoids re-running __construct(), which schedules a cron event when the
		// module is enabled.
		$this->ext = LatePointConversions::get_instance();
	}

	/**
	 * Invoke a protected method via Reflection. build_entry_data(),
	 * parse_created_at() and get_bookings() are all `protected`.
	 *
	 * @param string $method
	 * @param array  $args
	 * @return mixed
	 */
	protected function call_protected( $method, array $args = [] ) {
		$reflection = new ReflectionMethod( LatePointConversions::class, $method );
		$reflection->setAccessible( true );
		return $reflection->invokeArgs( $this->ext, $args );
	}

	/* -----------------------------------------------------------------
	 * Registration invariants
	 * ------------------------------------------------------------- */

	public function test_id_is_latepoint() {
		$this->assertSame( 'latepoint', $this->ext->id );
	}

	public function test_types_is_conversions() {
		$this->assertSame( 'conversions', $this->ext->types );
	}

	public function test_module_is_modules_latepoint() {
		$this->assertSame( 'modules_latepoint', $this->ext->module );
	}

	/**
	 * LatePoint is a FREE integration. A regression here would silently
	 * Pro-gate every site currently relying on it.
	 */
	public function test_is_not_pro() {
		$this->assertFalse( $this->ext->is_pro );
	}

	public function test_extension_factory_key_matches_declared_id() {
		$extensions = ExtensionFactory::get_instance()->get_all();
		$this->assertArrayHasKey(
			'latepoint',
			$extensions,
			'ExtensionFactory did not register latepoint under its own id.'
		);
		$this->assertInstanceOf( LatePointConversions::class, $extensions['latepoint'] );
		$this->assertSame( 'latepoint', $extensions['latepoint']->id );
	}

	public function test_declared_type_resolves_in_type_factory() {
		$type_factory = TypeFactory::get_instance();
		$this->assertInstanceOf(
			Types::class,
			$type_factory->get( $this->ext->types ),
			"Declared type '{$this->ext->types}' did not resolve to a real Types instance."
		);
	}

	/* -----------------------------------------------------------------
	 * check_booking_eligibility( $return, $entry, $settings )
	 *
	 * Capture-time allowlist gate, hooked to nx_can_entry_latepoint. This is
	 * the single highest-value target: it is what stops cancelled bookings
	 * and hidden services from being published on a public site.
	 * ------------------------------------------------------------- */

	protected function eligible_data( array $overrides = [] ) {
		return array_merge(
			[
				'status'    => 'approved',
				'name'      => 'Jane Doe',
				'timestamp' => time() - 60,
			],
			$overrides
		);
	}

	public function test_allowed_status_passes_the_return_value_through_unchanged() {
		// A non-boolean sentinel proves this is a genuine pass-through of
		// $return, not a coincidental true.
		$sentinel = 'sentinel-' . __METHOD__;
		$result   = $this->ext->check_booking_eligibility(
			$sentinel,
			[ 'data' => $this->eligible_data() ],
			[]
		);
		$this->assertSame( $sentinel, $result );
	}

	public function test_cancelled_status_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'status' => 'cancelled' ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_no_show_status_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'status' => 'no_show' ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_pending_status_is_rejected_under_default_allowlist() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'status' => 'pending' ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	/**
	 * LatePoint lets admins define arbitrary custom statuses (its status set
	 * is not a closed enum). This is the single most important property of
	 * the gate: it must be an ALLOWLIST, not a denylist, so an unrecognised
	 * status is rejected by default rather than let through.
	 */
	public function test_unrecognised_custom_status_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'status' => 'awaiting_vip_review' ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_missing_status_key_is_rejected_without_warning() {
		$data = $this->eligible_data();
		unset( $data['status'] );
		$result = $this->ext->check_booking_eligibility( true, [ 'data' => $data ], [] );
		$this->assertFalse( $result );
	}

	public function test_blank_name_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'name' => '' ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_whitespace_only_name_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'name' => "  \t \n " ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_missing_timestamp_key_is_rejected_without_warning() {
		$data = $this->eligible_data();
		unset( $data['timestamp'] );
		$result = $this->ext->check_booking_eligibility( true, [ 'data' => $data ], [] );
		$this->assertFalse( $result );
	}

	public function test_non_numeric_timestamp_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'timestamp' => 'not-a-timestamp' ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_far_future_timestamp_is_rejected() {
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'timestamp' => time() + ( 2 * DAY_IN_SECONDS ) ] ) ],
			[]
		);
		$this->assertFalse( $result );
	}

	public function test_settings_without_booking_status_key_falls_back_to_default_statuses() {
		// 'completed' is in DEFAULT_STATUSES (approved+completed) but is not
		// the status used by the other happy-path tests here, so this proves
		// the actual fallback set is applied, not just "any array".
		$result = $this->ext->check_booking_eligibility(
			true,
			[ 'data' => $this->eligible_data( [ 'status' => 'completed' ] ) ],
			[] // No 'latepoint_booking_status' key at all.
		);
		$this->assertTrue( $result );
	}

	public function test_missing_entry_data_key_is_rejected_without_warning() {
		$result = $this->ext->check_booking_eligibility( true, [], [] );
		$this->assertFalse( $result );
	}

	public function test_empty_entry_data_array_is_rejected_without_warning() {
		// Every key ('status', 'name', 'timestamp') is absent at once — the
		// strongest single check that no branch reads an undefined array key
		// directly (PHP 8.2 would raise a warning, and phpunit.xml.dist turns
		// warnings into failures via convertWarningsToExceptions).
		$result = $this->ext->check_booking_eligibility( true, [ 'data' => [] ], [] );
		$this->assertFalse( $result );
	}

	/* -----------------------------------------------------------------
	 * mask_service_name( $entry, $settings )
	 * ------------------------------------------------------------- */

	public function test_hide_service_name_toggle_on_replaces_title() {
		$entry  = [ 'title' => 'Deep Tissue Massage' ];
		$result = $this->ext->mask_service_name( $entry, [ 'latepoint_hide_service_name' => '1' ] );
		$this->assertSame( __( 'an appointment', 'notificationx' ), $result['title'] );
	}

	public function test_hide_service_name_toggle_off_leaves_title_untouched() {
		$entry  = [ 'title' => 'Deep Tissue Massage' ];
		$result = $this->ext->mask_service_name( $entry, [ 'latepoint_hide_service_name' => '0' ] );
		$this->assertSame( 'Deep Tissue Massage', $result['title'] );
	}

	public function test_absent_hide_service_name_key_leaves_title_untouched() {
		$entry  = [ 'title' => 'Deep Tissue Massage' ];
		$result = $this->ext->mask_service_name( $entry, [] );
		$this->assertSame( 'Deep Tissue Massage', $result['title'] );
	}

	/* -----------------------------------------------------------------
	 * booking_link( $link, $post, $entry )
	 * ------------------------------------------------------------- */

	public function test_booking_link_returns_configured_url_when_link_type_is_booking_page() {
		$post = [
			'link_type'                  => 'booking_page',
			'latepoint_booking_page_url' => 'https://example.com/book-now',
		];
		$result = $this->ext->booking_link( 'https://example.com/original', $post, [] );
		$this->assertSame( esc_url_raw( 'https://example.com/book-now' ), $result );
	}

	public function test_booking_link_does_not_override_when_link_type_is_none() {
		$post = [
			'link_type'                  => 'none',
			'latepoint_booking_page_url' => 'https://example.com/book-now',
		];
		$original = 'https://example.com/original';
		$result   = $this->ext->booking_link( $original, $post, [] );
		$this->assertSame( $original, $result );
	}

	public function test_booking_link_does_not_override_when_url_is_missing() {
		$post     = [ 'link_type' => 'booking_page' ];
		$original = 'https://example.com/original';
		$result   = $this->ext->booking_link( $original, $post, [] );
		$this->assertSame( $original, $result );
	}

	/* -----------------------------------------------------------------
	 * parse_created_at( $booking ) — protected, via Reflection.
	 * ------------------------------------------------------------- */

	public function test_parse_created_at_parses_valid_string_as_utc_timestamp() {
		$booking  = (object) [ 'created_at' => '2024-01-15 10:30:00' ];
		$expected = ( new DateTime( '2024-01-15 10:30:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();

		$result = $this->call_protected( 'parse_created_at', [ $booking ] );
		$this->assertSame( $expected, $result );
	}

	public function test_parse_created_at_returns_false_for_null_created_at() {
		$booking = (object) [ 'created_at' => null ];
		$this->assertFalse( $this->call_protected( 'parse_created_at', [ $booking ] ) );
	}

	public function test_parse_created_at_returns_false_for_empty_string() {
		$booking = (object) [ 'created_at' => '' ];
		$this->assertFalse( $this->call_protected( 'parse_created_at', [ $booking ] ) );
	}

	public function test_parse_created_at_returns_false_for_garbage_string() {
		$booking = (object) [ 'created_at' => 'not-a-real-date' ];
		$this->assertFalse( $this->call_protected( 'parse_created_at', [ $booking ] ) );
	}

	/* -----------------------------------------------------------------
	 * build_entry_data( $booking ) — protected, via Reflection.
	 *
	 * LatePoint's \OsBookingModel/\OsCustomerModel/\OsServiceModel do not
	 * exist in CI, but the method only ever does ->id / property access plus
	 * method_exists()-guarded calls — a stdClass stub exercises every branch
	 * that does not require a *real* LatePoint model, because method_exists()
	 * on a stdClass simply evaluates to false and those optional-field
	 * branches are skipped, exactly as they would be for a service/customer
	 * whose model lacks that accessor.
	 * ------------------------------------------------------------- */

	protected function stub_booking( array $overrides = [] ) {
		return (object) array_merge(
			[
				'id'         => 501,
				'status'     => 'approved',
				'created_at' => '2024-03-01 09:00:00',
				'customer'   => (object) [
					'id'         => 9,
					'first_name' => 'Ada',
					'last_name'  => 'Lovelace',
					'email'      => 'ada@example.com',
				],
				'service'    => (object) [
					'id'   => 3,
					'name' => 'Consultation',
				],
			],
			$overrides
		);
	}

	public function test_build_entry_data_returns_expected_payload_for_valid_booking() {
		$data = $this->call_protected( 'build_entry_data', [ $this->stub_booking() ] );

		$this->assertIsArray( $data );
		$this->assertSame( 'Ada L.', $data['name'] );
		$this->assertSame( 'Consultation', $data['title'] );
		$this->assertSame( 501, $data['booking_id'] );
		$this->assertSame( 3, $data['service_id'] );
		$this->assertSame( 'approved', $data['status'] );
		$this->assertSame( 'ada@example.com', $data['email'] );
		// No get_selection_image_url()/get_avatar_url() exist on the stub, so
		// method_exists() correctly skips populating these optional keys
		// instead of fataling on an undefined method call.
		$this->assertArrayNotHasKey( 'service_image', $data );
		$this->assertArrayNotHasKey( 'customer_avatar', $data );
	}

	public function test_build_entry_data_returns_false_when_customer_missing() {
		$booking = $this->stub_booking( [ 'customer' => null ] );
		$this->assertFalse( $this->call_protected( 'build_entry_data', [ $booking ] ) );
	}

	public function test_build_entry_data_returns_false_when_service_missing() {
		$booking = $this->stub_booking( [ 'service' => null ] );
		$this->assertFalse( $this->call_protected( 'build_entry_data', [ $booking ] ) );
	}

	public function test_build_entry_data_returns_false_when_customer_has_no_name() {
		$booking = $this->stub_booking(
			[
				'customer' => (object) [
					'id'         => 9,
					'first_name' => '',
					'last_name'  => '',
				],
			]
		);
		$this->assertFalse( $this->call_protected( 'build_entry_data', [ $booking ] ) );
	}

	public function test_build_entry_data_returns_false_when_created_at_unparseable() {
		$booking = $this->stub_booking( [ 'created_at' => 'garbage' ] );
		$this->assertFalse( $this->call_protected( 'build_entry_data', [ $booking ] ) );
	}

	/**
	 * The is_hidden() rejection branch in build_entry_data() is gated behind
	 * method_exists( $service, 'is_hidden' ), which is only ever true for
	 * LatePoint's real OsServiceModel. A stdClass stub can never satisfy
	 * method_exists(), so this branch cannot be exercised without the
	 * LatePoint plugin installed — documented here rather than left
	 * silently uncovered.
	 */
	public function test_build_entry_data_hidden_service_rejection_requires_latepoint_plugin() {
		$this->markTestSkipped(
			'is_hidden() rejection in build_entry_data() requires a real OsServiceModel ' .
			'(method_exists() gate cannot be satisfied by a stdClass stub); needs the ' .
			'LatePoint plugin installed to exercise.'
		);
	}

	/* -----------------------------------------------------------------
	 * class_exists('LatePoint') guard clauses — reachable without the
	 * plugin, and meaningful: they are what stops these methods from ever
	 * touching \OsBookingModel once LatePoint is deactivated.
	 * ------------------------------------------------------------- */

	public function test_get_bookings_short_circuits_when_latepoint_plugin_absent() {
		if ( class_exists( 'LatePoint' ) ) {
			$this->markTestSkipped( 'LatePoint plugin class is unexpectedly present in this environment.' );
		}
		$result = $this->call_protected( 'get_bookings', [ [] ] );
		$this->assertSame( [], $result );
	}

	public function test_reconcile_clears_scheduled_hook_when_latepoint_plugin_absent() {
		if ( class_exists( 'LatePoint' ) ) {
			$this->markTestSkipped( 'LatePoint plugin class is unexpectedly present in this environment.' );
		}
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'nx_latepoint_reconcile' );
		$this->assertNotFalse( wp_next_scheduled( 'nx_latepoint_reconcile' ), 'Precondition: event should be scheduled.' );

		$this->ext->reconcile();

		$this->assertFalse(
			wp_next_scheduled( 'nx_latepoint_reconcile' ),
			'reconcile() must unschedule nx_latepoint_reconcile once LatePoint is gone, or it recurs forever.'
		);
	}

	/**
	 * Beyond the guard clause above, reconcile() drives \OsBookingModel /
	 * \OsCustomerModel / \OsServiceModel lookups per stored entry — that part
	 * genuinely needs the LatePoint plugin installed.
	 */
	public function test_reconcile_stale_entry_pruning_requires_latepoint_plugin() {
		$this->markTestSkipped(
			'reconcile() beyond the class_exists() guard queries live LatePoint models; ' .
			'requires the LatePoint plugin to be installed.'
		);
	}
}
