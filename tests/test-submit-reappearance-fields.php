<?php
/**
 * The "After a form submit" fields (submit_reappearance, submit_hide_days).
 *
 * They exist on the three notifications with a form: Popup (themes four to
 * seven), Exit Intent (theme one, Pro's theme seven, or an Elementor design) and
 * Elementor-built Notification Bars. Their default, "Same as closing", keeps
 * today's behaviour, and a notification saved before they existed reads back
 * with that default.
 *
 * The builder rules are pinned in tests/js/fixtures/submit-reappearance-rules.json,
 * which tests/js/frontend/submit-reappearance-rules.test.js evaluates with
 * quickbuilder's own rule engine.
 *
 * @package Notificationx
 */

use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\NotificationX;

class Test_Submit_Reappearance_Fields extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		delete_transient( 'nx_builder_fields' );
	}

	public function tear_down() {
		delete_transient( 'nx_builder_fields' );
		parent::tear_down();
	}

	/**
	 * Every place a field of this name appears in the builder, with the name of
	 * the section that holds it.
	 *
	 * @param array  $fields  Builder fields (any level).
	 * @param string $name    Field name.
	 * @param string $section Name of the enclosing section.
	 * @param array  $found   Accumulator.
	 * @return array List of [ 'section' => string, 'field' => array ].
	 */
	protected function find_fields( $fields, $name, $section = '', $found = array() ) {
		if ( ! is_array( $fields ) ) {
			return $found;
		}
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( isset( $field['name'] ) && $name === $field['name'] && isset( $field['type'] ) && 'section' !== $field['type'] ) {
				$found[] = array(
					'section' => $section,
					'field'   => $field,
				);
				continue;
			}
			$inner = isset( $field['type'] ) && 'section' === $field['type'] && isset( $field['name'] ) ? $field['name'] : $section;
			if ( isset( $field['fields'] ) ) {
				$found = $this->find_fields( $field['fields'], $name, $inner, $found );
			}
			if ( isset( $field['tabs'] ) ) {
				$found = $this->find_fields( $field['tabs'], $name, $inner, $found );
			}
		}
		return $found;
	}

	/**
	 * The field of this name in this section.
	 *
	 * @param string $name    Field name.
	 * @param string $section Section name.
	 * @return array
	 */
	protected function field_in( $name, $section ) {
		$tabs = NotificationX::get_instance()->get_tab();
		foreach ( $this->find_fields( $tabs['tabs'], $name ) as $hit ) {
			if ( $section === $hit['section'] ) {
				return $hit['field'];
			}
		}
		$this->fail( "No $name field in the $section section." );
	}

	/**
	 * The rules fixture shared with the JS test.
	 *
	 * @return array
	 */
	protected function fixture() {
		$json = file_get_contents( dirname( __FILE__ ) . '/js/fixtures/submit-reappearance-rules.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local test fixture.
		$data = json_decode( $json, true );
		unset( $data['_comment'] );
		return $data;
	}

	public function test_fields_exist_in_the_three_sections_only() {
		$tabs = NotificationX::get_instance()->get_tab();
		foreach ( array( 'submit_reappearance', 'submit_hide_days' ) as $name ) {
			$sections = wp_list_pluck( $this->find_fields( $tabs['tabs'], $name ), 'section' );
			sort( $sections );
			$this->assertSame( array( 'exit_intent_popup_settings', 'popup_settings', 'visibility' ), $sections, $name );
		}
	}

	public function test_select_defaults_to_same_as_closing() {
		foreach ( $this->fixture() as $type => $expected ) {
			$field = $this->field_in( 'submit_reappearance', $expected['section'] );
			$this->assertSame( 'select', $field['type'], $type );
			$this->assertSame( 'same_as_close', $field['default'], $type );
			$this->assertSame(
				array( 'same_as_close', 'days', 'never' ),
				array_values( wp_list_pluck( $field['options'], 'value' ) ),
				$type
			);
		}
	}

	public function test_days_field_defaults_to_30_within_1_to_365() {
		foreach ( $this->fixture() as $type => $expected ) {
			$field = $this->field_in( 'submit_hide_days', $expected['section'] );
			$this->assertSame( 'number', $field['type'], $type );
			$this->assertSame( 30, $field['default'], $type );
			$this->assertSame( 1, $field['min'], $type );
			$this->assertSame( 365, $field['max'], $type );
		}
	}

	/**
	 * The rules the builder gets are exactly the ones the JS test evaluates.
	 */
	public function test_rules_match_the_shared_fixture() {
		foreach ( $this->fixture() as $type => $expected ) {
			foreach ( array( 'submit_reappearance', 'submit_hide_days' ) as $name ) {
				$field = $this->field_in( $name, $expected['section'] );
				$this->assertSame(
					wp_json_encode( $expected[ $name ] ),
					wp_json_encode( $field['rules'] ),
					"$type $name"
				);
			}
		}
	}

	/**
	 * A notification saved before the fields existed reads back with
	 * "Same as closing", in the post and in the frontend payload.
	 */
	public function test_notification_saved_without_the_fields_gets_same_as_closing() {
		global $wpdb;
		$saved = PostType::get_instance()->save_post(
			array(
				'type'    => 'popup',
				'source'  => 'popup_notification',
				'themes'  => 'popup_notification_theme-five',
				'enabled' => true,
				'title'   => 'saved before the submit rule',
			)
		);
		$nx_id = (int) $saved['nx_id'];
		$this->assertNotEmpty( $nx_id );

		// What an existing row looks like: no submit_* keys at all.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the test edits the raw row on purpose.
		$data = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}nx_posts WHERE nx_id = %d", $nx_id ) ) );
		unset( $data['submit_reappearance'], $data['submit_hide_days'] );
		$wpdb->update( "{$wpdb->prefix}nx_posts", array( 'data' => maybe_serialize( $data ) ), array( 'nx_id' => $nx_id ) );
		$raw = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}nx_posts WHERE nx_id = %d", $nx_id ) ) );
		// phpcs:enable
		$this->assertArrayNotHasKey( 'submit_reappearance', $raw );

		$post = PostType::get_instance()->get_post( $nx_id );
		$this->assertSame( 'same_as_close', $post['submit_reappearance'] );
		$this->assertSame( 30, $post['submit_hide_days'] );

		$payload = FrontEnd::get_instance()->get_notifications_data( array( 'popup' => array( $nx_id ) ) );
		$this->assertSame( 'same_as_close', $payload['popup'][ $nx_id ]['post']['submit_reappearance'] );
	}

	/**
	 * The site scope of the visitor's "submitted" key: short, stable, and
	 * different for two sites (sub-directory multisite, Cross Domain embeds).
	 */
	public function test_storage_scope_is_per_site() {
		$scope = Helper::storage_scope();
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{10}$/', $scope );
		$this->assertSame( $scope, Helper::storage_scope(), 'stable between calls' );

		$other = function () {
			return 'https://example.org/another-site';
		};
		add_filter( 'pre_option_home', $other );
		$other_scope = Helper::storage_scope();
		remove_filter( 'pre_option_home', $other );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{10}$/', $other_scope );
		$this->assertNotSame( $scope, $other_scope );
	}

	/**
	 * The page and the Cross Domain Notice snippet both carry the scope.
	 */
	public function test_localize_data_carries_the_storage_scope() {
		$frontend = FrontEnd::get_instance();
		$this->assertSame( Helper::storage_scope(), $frontend->get_localize_data( array() )['storage_scope'] );
		$this->assertSame( Helper::storage_scope(), $frontend->get_localize_data( array( 'cross' => true ) )['storage_scope'] );
	}

	/**
	 * A saved rule reaches the frontend payload of each type.
	 */
	public function test_saved_rule_reaches_the_frontend() {
		$cases = array(
			'popup'    => array( 'popup', 'popup_notification', 'popup_notification_theme-five' ),
			'pressbar' => array( 'notification_bar', 'press_bar', 'press_bar_theme-one' ),
		);
		foreach ( $cases as $param => $case ) {
			$saved = PostType::get_instance()->save_post(
				array(
					'type'                => $case[0],
					'source'              => $case[1],
					'themes'              => $case[2],
					'enabled'             => true,
					'title'               => "submit rule $param",
					'submit_reappearance' => 'days',
					'submit_hide_days'    => 14,
				)
			);
			$nx_id   = (int) $saved['nx_id'];
			$payload = FrontEnd::get_instance()->get_notifications_data( array( $param => array( $nx_id ) ) );
			$this->assertArrayHasKey( $nx_id, $payload[ $param ], $param );
			$this->assertSame( 'days', $payload[ $param ][ $nx_id ]['post']['submit_reappearance'], $param );
			$this->assertEquals( 14, $payload[ $param ][ $nx_id ]['post']['submit_hide_days'], $param );
		}
	}
}
