<?php
/**
 * Regression tests for notification settings stored with the wrong JSON type.
 *
 * MCP create/update-notification and the REST save store whatever JSON the
 * caller sent, and field defaults are often '' even for list fields. Several
 * front-end consumers then fatal on PHP 8 (explode() on an array, array_map()
 * on a string, shuffle() on a string, ...) on every page load or on the public
 * `notice` REST route. NotificationX::normalize_post() now coerces each field to
 * the shape its consumers expect; these tests pin that down.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\NotificationX;

class Test_Field_Type_Normalization extends WP_UnitTestCase {

	/**
	 * @var NotificationX
	 */
	protected $nx;

	public function set_up() {
		parent::set_up();
		$this->nx = NotificationX::get_instance();
	}

	/**
	 * Text-like fields: a list of scalars becomes a comma separated string, the
	 * format `custom_ids` / `taxonomy_ids` document. Other shapes become ''.
	 */
	public function test_normalize_string_value() {
		$this->assertSame( '12,34', $this->nx->normalize_string_value( array( 12, 34 ) ) );
		$this->assertSame( '12', $this->nx->normalize_string_value( array( '12' ) ) );
		$this->assertSame( '', $this->nx->normalize_string_value( array() ) );
		$this->assertSame( '', $this->nx->normalize_string_value( array( array( 1 ) ) ) );
		$this->assertSame( '1,2', $this->nx->normalize_string_value( (object) array( 'a' => 1, 'b' => 2 ) ) );

		// Scalars and null pass through untouched.
		$this->assertSame( '12, 34', $this->nx->normalize_string_value( '12, 34' ) );
		$this->assertSame( 5, $this->nx->normalize_string_value( 5 ) );
		$this->assertNull( $this->nx->normalize_string_value( null ) );
	}

	/**
	 * Repeater fields: always a list of array rows.
	 */
	public function test_normalize_rows_value() {
		$this->assertSame( array(), $this->nx->normalize_rows_value( '' ) );
		$this->assertSame( array(), $this->nx->normalize_rows_value( 'Someone bought X' ) );
		$this->assertSame( array(), $this->nx->normalize_rows_value( null ) );
		$this->assertSame( array(), $this->nx->normalize_rows_value( array( 'a', 'b' ) ) );
		$this->assertSame(
			array( 0 => array( 'title' => 'A' ), 2 => array( 'title' => 'C' ) ),
			$this->nx->normalize_rows_value( array( array( 'title' => 'A' ), 'junk', (object) array( 'title' => 'C' ) ) )
		);
	}

	/**
	 * The guarded keys are coerced even when the cached field list lacks them.
	 */
	public function test_normalize_post_guards_known_keys() {
		$normalized = $this->nx->normalize_post(
			array(
				'notification-template' => '{{name}} purchased {{title}}',
				'custom_contents'       => 'Someone bought X',
				'custom_ids'            => array( 12, 34 ),
				'taxonomy_ids'          => array( 5 ),
			)
		);

		$this->assertSame( array(), $normalized['notification-template'] );
		$this->assertSame( array(), $normalized['custom_contents'] );
		$this->assertSame( '12,34', $normalized['custom_ids'] );
		$this->assertSame( '5', $normalized['taxonomy_ids'] );
	}

	/**
	 * Valid values keep their shape.
	 */
	public function test_normalize_post_keeps_valid_values() {
		$template   = array( 'first_param' => 'tag_name', 'second_param' => 'purchased' );
		$rows       = array( array( 'title' => 'A' ) );
		$normalized = $this->nx->normalize_post(
			array(
				'notification-template' => $template,
				'custom_contents'       => $rows,
				'custom_ids'            => '12,34',
			)
		);

		$this->assertSame( $template, $normalized['notification-template'] );
		$this->assertSame( $rows, $normalized['custom_contents'] );
		$this->assertSame( '12,34', $normalized['custom_ids'] );
	}

	/**
	 * Every field the builder declares as text-like or repeater is coerced.
	 */
	public function test_normalize_post_uses_field_types() {
		$string_field = null;
		$rows_field   = null;
		foreach ( $this->nx->get_field_names() as $name => $field ) {
			if ( ! empty( $field['multiple'] ) || empty( $field['type'] ) || isset( NotificationX::GUARDED_FIELDS[ $name ] ) ) {
				continue;
			}
			if ( ! $string_field && in_array( $field['type'], NotificationX::STRING_FIELD_TYPES, true ) ) {
				$string_field = $name;
			}
			if ( ! $rows_field && in_array( $field['type'], NotificationX::ROWS_FIELD_TYPES, true ) ) {
				$rows_field = $name;
			}
		}
		$this->assertNotNull( $string_field, 'No text-like field in the builder config.' );

		$stored = array( $string_field => array( 'a', 'b' ) );
		if ( $rows_field ) {
			$stored[ $rows_field ] = 'not rows';
		}
		$normalized = $this->nx->normalize_post( $stored );

		$this->assertSame( 'a,b', $normalized[ $string_field ] );
		if ( $rows_field ) {
			$this->assertSame( array(), $normalized[ $rows_field ] );
		}
	}

	/**
	 * A notification stored with array IDs must not fatal the front-end loop
	 * (Locations explode()s them).
	 */
	public function test_front_end_loop_survives_array_custom_ids() {
		$nx_id = $this->insert_notification(
			array(
				'show_on'       => 'on_selected',
				'all_locations' => array( 'is_custom', 'is_taxonomy' ),
				'custom_ids'    => array( 12, 34 ),
				'taxonomy_ids'  => array( 5, 6 ),
			)
		);

		$post = PostType::get_instance()->get_post( $nx_id );
		$this->assertSame( '12,34', $post['custom_ids'] );
		$this->assertSame( '5,6', $post['taxonomy_ids'] );

		$result = FrontEnd::get_instance()->get_notifications_ids();
		$this->assertIsArray( $result );
	}

	/**
	 * save_post() coerces before storing, so the raw row is clean too (readers
	 * such as generate_custom_css() and the nx_get_post filters that save_post()
	 * itself fires see the stored shape, not normalize_post()'s).
	 */
	public function test_save_post_stores_coerced_types() {
		global $wpdb;
		$nx_id = $this->insert_notification(
			array(
				'custom_ids'            => array( 12, 34 ),
				'notification-template' => 'plain string',
				'custom_contents'       => 'not rows',
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- test reads the raw row.
		$raw  = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}nx_posts WHERE nx_id = %d", $nx_id ) );
		$data = maybe_unserialize( $raw );

		$this->assertSame( '12,34', $data['custom_ids'] );
		$this->assertSame( array(), $data['notification-template'] );
		$this->assertSame( array(), $data['custom_contents'] );
	}

	/**
	 * A string notification-template must not fatal filtered_post().
	 */
	public function test_filtered_post_survives_string_template() {
		$nx_id = $this->insert_notification( array( 'notification-template' => '{{name}} purchased {{title}}' ) );
		$post  = PostType::get_instance()->get_post( $nx_id );

		$this->assertSame( array(), $post['notification-template'] );
		$this->assertIsArray( FrontEnd::get_instance()->filtered_post( $post, array() ) );
	}

	/**
	 * The public `notice` route passes query params straight through; scalar
	 * values like `?global=1` must not fatal.
	 */
	public function test_notifications_data_accepts_scalar_params() {
		$data = FrontEnd::get_instance()->get_notifications_data(
			array(
				'global'   => '1',
				'pressbar' => '163',
				'active'   => '',
			)
		);
		$this->assertIsArray( $data );
	}

	/**
	 * Async-select fields sent as plain IDs (MCP / REST) keep their values
	 * instead of turning into empty strings.
	 */
	public function test_async_select_accepts_plain_ids() {
		$post_type = PostType::get_instance();
		$field     = $this->nx->get_field( 'product_list' );
		if ( empty( $field['multiple'] ) ) {
			$this->markTestSkipped( 'product_list is not a multi-select field in this build.' );
		}

		$post = $post_type->async_select_remove_label(
			array( 'data' => array( 'product_list' => array( 12, '34' ) ) ),
			array(),
			0
		);
		$this->assertSame( array( 12, '34' ), $post['data']['product_list'] );

		$post = $post_type->async_select_remove_label(
			array( 'data' => array( 'product_list' => array( array( 'value' => 12, 'label' => 'Hat' ) ) ) ),
			array(),
			0
		);
		$this->assertSame( array( 12 ), $post['data']['product_list'] );
	}

	/**
	 * Saving settings must clear the cached builder config that normalize_post()
	 * reads field types from, so module toggles take effect.
	 */
	public function test_save_settings_clears_builder_fields_cache() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		if ( ! current_user_can( 'edit_notificationx_settings' ) ) {
			get_role( 'administrator' )->add_cap( 'edit_notificationx_settings' );
			wp_set_current_user( 0 );
			wp_set_current_user( $admin );
		}

		set_transient( 'nx_builder_fields', array( 'stale' => true ), DAY_IN_SECONDS );
		\NotificationX\Admin\Settings::get_instance()->save_settings( array() );

		$this->assertFalse( get_transient( 'nx_builder_fields' ) );
	}

	/**
	 * Insert an enabled notification with raw data, bypassing the builder.
	 *
	 * @param array $data Extra settings.
	 * @return int nx_id
	 */
	protected function insert_notification( $data ) {
		$data = array_merge(
			array(
				'type'    => 'notification_bar',
				'source'  => 'press_bar',
				'themes'  => 'press_bar_theme-one',
				'enabled' => true,
				'title'   => 'type normalization test',
			),
			$data
		);
		$saved = PostType::get_instance()->save_post( $data );
		$this->assertNotEmpty( $saved['nx_id'] );
		return (int) $saved['nx_id'];
	}
}
