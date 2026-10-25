<?php
/**
 * "Verified card" (vc) themes, Part 1: Receipt and Showcase.
 *
 * Covers the PHP side only:
 *  - the vc entries are appended at the END of the Sales Notification and
 *    WooCommerce theme lists, are free (no is_pro) and carry the planned
 *    template params, image shape and no defaults;
 *  - the theme trigger contains @link_button:false and the template params;
 *  - the full keys join woo_template_new['_themes'] and nothing else
 *    ($conversions_count, woo_template_sales_count, res_themes);
 *  - existing theme lists are unchanged apart from the appended keys;
 *  - Custom Notification (type and Sales > Custom source) does not offer them;
 *  - the Advanced Design (advance_edit) and Button (link_button) toggles are
 *    hidden for the vc keys and behave exactly as before for every other key;
 *  - the thumbnails exist.
 *
 * The keys come from GlobalFields::vc_theme_keys(), the single PHP list.
 *
 * @package Notificationx
 */

use NotificationX\Core\Rules;
use NotificationX\Extensions\CustomNotification\CustomNotification;
use NotificationX\Extensions\CustomNotification\CustomNotificationConversions;
use NotificationX\Extensions\EDD\EDD;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\WooCommerce\WooCommerceSales as WooCommerceSalesExtension;
use NotificationX\Types\Conversions;
use NotificationX\Types\WooCommerceSales as WooCommerceSalesType;

/**
 * @group themes
 * @group vc-themes
 */
class Test_VC_Themes extends WP_UnitTestCase {

	/**
	 * Theme slugs of Types/Conversions.php and Types/WooCommerceSales.php
	 * before the vc themes were added, in picker order.
	 *
	 * @var string[]
	 */
	const EXISTING_SALES_THEMES = array(
		'theme-one',
		'conv-theme-twelve',
		'conv-theme-thirteen',
		'conv-theme-fifteen',
		'theme-two',
		'theme-three',
		'theme-five',
		'theme-four',
		'conv-theme-six',
		'maps_theme',
		'conv-theme-ten',
		'conv-theme-eleven',
		'conv-theme-fourteen',
		'conv-theme-sixteen',
		'conv-theme-seven',
		'conv-theme-eight',
		'conv-theme-nine',
	);

	/**
	 * Part 1 vc slugs, in the order they are appended.
	 *
	 * @var string[]
	 */
	const VC_SLUGS = array( 'vc-receipt', 'vc-showcase' );

	/**
	 * @var Conversions
	 */
	protected $conversions;

	/**
	 * @var WooCommerceSalesType
	 */
	protected $woo_sales;

	public function set_up() {
		parent::set_up();
		// Types fill their theme/template arrays in init(); call it directly so
		// the test does not depend on module gating or hook order.
		$this->conversions = Conversions::get_instance();
		$this->conversions->init();
		$this->woo_sales = WooCommerceSalesType::get_instance();
		$this->woo_sales->init();
	}

	/**
	 * Theme types under test: prefix => Type instance.
	 *
	 * @return array
	 */
	protected function types() {
		return array(
			'conversions_'       => $this->conversions,
			'woocommerce_sales_' => $this->woo_sales,
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Source of truth
	 * ---------------------------------------------------------------------
	 */

	public function test_vc_theme_keys_lists_the_part_one_keys() {
		$keys = GlobalFields::vc_theme_keys();
		$this->assertIsArray( $keys );
		$this->assertSame( count( $keys ), count( array_unique( $keys ) ), 'vc_theme_keys() has duplicates.' );

		$expected = array(
			'conversions_vc-receipt',
			'woocommerce_sales_vc-receipt',
			'conversions_vc-showcase',
			'woocommerce_sales_vc-showcase',
		);
		foreach ( $expected as $key ) {
			$this->assertContains( $key, $keys );
		}
		foreach ( $keys as $key ) {
			$this->assertNotFalse( strpos( $key, '_vc-' ), "{$key} does not contain '_vc-'." );
		}
	}

	public function test_every_vc_key_is_a_declared_theme() {
		$declared = array();
		foreach ( $this->types() as $prefix => $type ) {
			foreach ( array_keys( $type->get_themes() ) as $slug ) {
				$declared[] = $prefix . $slug;
			}
		}
		foreach ( GlobalFields::vc_theme_keys() as $key ) {
			// Part 2 keys live on other Types; only check the ones this test owns.
			if ( 0 !== strpos( $key, 'conversions_' ) && 0 !== strpos( $key, 'woocommerce_sales_' ) ) {
				continue;
			}
			$this->assertContains( $key, $declared );
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Theme entries
	 * ---------------------------------------------------------------------
	 */

	public function test_vc_themes_are_appended_last_and_existing_order_is_unchanged() {
		foreach ( $this->types() as $prefix => $type ) {
			$slugs = array_keys( $type->get_themes() );
			$this->assertSame(
				array_merge( self::EXISTING_SALES_THEMES, self::VC_SLUGS ),
				$slugs,
				"{$prefix} theme order changed."
			);
		}
	}

	public function test_existing_theme_entries_do_not_use_vc_assets() {
		foreach ( $this->types() as $prefix => $type ) {
			$themes = $type->get_themes();
			foreach ( self::EXISTING_SALES_THEMES as $slug ) {
				$this->assertArrayHasKey( $slug, $themes );
				$this->assertFalse(
					strpos( $themes[ $slug ]['source'], '/themes/vc/' ),
					"{$prefix}{$slug} points at a vc thumbnail."
				);
			}
		}
	}

	public function test_vc_theme_entries() {
		foreach ( $this->types() as $prefix => $type ) {
			$themes = $type->get_themes();
			foreach ( self::VC_SLUGS as $slug ) {
				$this->assertArrayHasKey( $slug, $themes, "{$prefix}{$slug} missing." );
				$theme = $themes[ $slug ];

				$this->assertArrayNotHasKey( 'is_pro', $theme, "{$prefix}{$slug} must be free." );
				$this->assertArrayNotHasKey( 'defaults', $theme, "{$prefix}{$slug} must have no defaults." );
				$this->assertSame( 'rounded', $theme['image_shape'] );
				$this->assertSame(
					NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/' . $slug . '.png',
					$theme['source']
				);

				$template = $theme['template'];
				$this->assertSame( 'tag_name', $template['first_param'] );
				$this->assertSame( 'tag_product_title', $template['third_param'] );
				$this->assertSame( 'tag_time', $template['fourth_param'] );
				$this->assertSame( 'Someone', $template['custom_first_param'] );
				$this->assertSame( 'Anonymous Product', $template['custom_third_param'] );
				$this->assertSame( 'Some time ago', $template['custom_fourth_param'] );
			}

			$this->assertSame( 'just purchased', $themes['vc-receipt']['template']['second_param'] );
			$this->assertSame( 'Just purchased', $themes['vc-showcase']['template']['second_param'] );
			// Receipt uses the shared fields unchanged, like theme-one.
			$this->assertSame( $themes['theme-one']['template'], $themes['vc-receipt']['template'] );
		}
	}

	public function test_picker_options_are_free_and_use_vc_thumbnails() {
		$extensions = array(
			'conversions_'       => EDD::get_instance(),
			'woocommerce_sales_' => WooCommerceSalesExtension::get_instance(),
		);
		foreach ( $extensions as $prefix => $extension ) {
			$options = $extension->__nx_themes( array() );
			$keys    = array_keys( $options );
			$this->assertSame( array( $prefix . 'vc-receipt', $prefix . 'vc-showcase' ), array_slice( $keys, -2 ) );

			foreach ( self::VC_SLUGS as $slug ) {
				$option = $options[ $prefix . $slug ];
				$this->assertEmpty( $option['is_pro'], "{$prefix}{$slug} is locked as Pro." );
				$this->assertSame(
					NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/' . $slug . '.png',
					$option['icon']
				);
			}
		}
	}

	public function test_theme_trigger_sets_template_link_button_and_image_shape() {
		$extensions = array(
			'conversions_'       => EDD::get_instance(),
			'woocommerce_sales_' => WooCommerceSalesExtension::get_instance(),
		);
		$second = array(
			'vc-receipt'  => 'just purchased',
			'vc-showcase' => 'Just purchased',
		);
		foreach ( $extensions as $prefix => $extension ) {
			$triggers = $extension->__themes_trigger( array() );
			foreach ( self::VC_SLUGS as $slug ) {
				$key = $prefix . $slug;
				$this->assertArrayHasKey( $key, $triggers );
				$trigger = $triggers[ $key ];

				$this->assertContains( '@link_button:false', $trigger );
				$this->assertContains( '@image_shape:rounded', $trigger );
				$this->assertContains( '@image_shape_default:rounded', $trigger );
				$this->assertContains( '@notification-template.first_param:tag_name', $trigger );
				$this->assertContains( '@notification-template.second_param:' . $second[ $slug ], $trigger );
				$this->assertContains( '@notification-template.third_param:tag_product_title', $trigger );
				$this->assertContains( '@notification-template.fourth_param:tag_time', $trigger );

				foreach ( $trigger as $item ) {
					$this->assertIsString( $item );
					$this->assertStringStartsNotWith( '@link_button:1', $item );
					$this->assertStringStartsNotWith( '@link_button_text', $item );
					$this->assertStringStartsNotWith( '@show_notification_image', $item );
				}
			}
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Template groups and count lists
	 * ---------------------------------------------------------------------
	 */

	public function test_vc_keys_join_woo_template_new_only() {
		$expected_conversions = array(
			'conversions_theme-one',
			'conversions_theme-two',
			'conversions_theme-three',
			'conversions_theme-four',
			'conversions_theme-five',
			'conversions_conv-theme-ten',
			'conversions_conv-theme-eleven',
			'conversions_conv-theme-twelve',
			'conversions_conv-theme-thirteen',
			'conversions_conv-theme-fifteen',
			'woocommerce_sales_theme-one',
			'woocommerce_sales_theme-two',
			'woocommerce_sales_theme-three',
			'woocommerce_sales_theme-four',
			'woocommerce_sales_theme-five',
			'woocommerce_sales_conv-theme-ten',
			'woocommerce_sales_conv-theme-eleven',
			'woocommerce_sales_conv-theme-twelve',
			'woocommerce_sales_conv-theme-thirteen',
			'woocommerce_sales_conv-theme-fifteen',
			'conversions_vc-receipt',
			'conversions_vc-showcase',
			'woocommerce_sales_vc-receipt',
			'woocommerce_sales_vc-showcase',
		);
		$templates = $this->conversions->get_templates();
		$this->assertSame( $expected_conversions, $templates['woo_template_new']['_themes'] );

		$expected_woo = array(
			'woocommerce_sales_theme-one',
			'woocommerce_sales_theme-two',
			'woocommerce_sales_theme-three',
			'woocommerce_sales_theme-four',
			'woocommerce_sales_theme-five',
			'woocommerce_sales_conv-theme-ten',
			'woocommerce_sales_conv-theme-eleven',
			'woocommerce_sales_conv-theme-twelve',
			'woocommerce_sales_conv-theme-thirteen',
			'woocommerce_sales_conv-theme-fifteen',
			'woocommerce_sales_vc-receipt',
			'woocommerce_sales_vc-showcase',
		);
		$woo_templates = $this->woo_sales->get_templates();
		$this->assertSame( $expected_woo, $woo_templates['woo_template_new']['_themes'] );

		// The sales-count group is unchanged.
		$this->assertSame(
			array(
				'conversions_conv-theme-six',
				'conversions_conv-theme-seven',
				'conversions_conv-theme-eight',
				'conversions_conv-theme-nine',
				'conversions_conv-theme-fourteen',
				'conversions_conv-theme-sixteen',
				'woocommerce_sales_conv-theme-six',
				'woocommerce_sales_conv-theme-seven',
				'woocommerce_sales_conv-theme-eight',
				'woocommerce_sales_conv-theme-nine',
				'woocommerce_sales_conv-theme-fourteen',
				'woocommerce_sales_conv-theme-sixteen',
			),
			$templates['woo_template_sales_count']['_themes']
		);
		$this->assertSame(
			array(
				'woocommerce_sales_conv-theme-six',
				'woocommerce_sales_conv-theme-seven',
				'woocommerce_sales_conv-theme-eight',
				'woocommerce_sales_conv-theme-nine',
				'woocommerce_sales_conv-theme-fourteen',
				'woocommerce_sales_conv-theme-sixteen',
			),
			$woo_templates['woo_template_sales_count']['_themes']
		);

		// Only the two template groups above exist; no vc-only group in Part 1.
		$this->assertSame( array( 'woo_template_new', 'woo_template_sales_count' ), array_keys( $templates ) );
		$this->assertSame( array( 'woo_template_new', 'woo_template_sales_count' ), array_keys( $woo_templates ) );
	}

	public function test_vc_keys_are_not_counted_themes() {
		$this->assertSame(
			array(
				'conversions_conv-theme-seven',
				'conversions_conv-theme-eight',
				'conversions_conv-theme-nine',
				'conversions_conv-theme-fourteen',
				'conversions_conv-theme-sixteen',
				'woocommerce_sales_conv-theme-seven',
				'woocommerce_sales_conv-theme-eight',
				'woocommerce_sales_conv-theme-nine',
				'woocommerce_sales_conv-theme-fourteen',
				'woocommerce_sales_conv-theme-sixteen',
			),
			$this->conversions->conversions_count
		);
		foreach ( GlobalFields::vc_theme_keys() as $key ) {
			$this->assertNotContains( $key, $this->conversions->conversions_count );
		}
	}

	public function test_res_themes_are_unchanged() {
		$expected = array(
			'res-theme-one'    => 'woo_template_new',
			'res-theme-two'    => 'woo_template_new',
			'res-theme-three'  => 'woo_template_new',
			'res-theme-four'   => 'woo_template_new',
			'res-theme-five'   => 'maps_template_new',
			'res-theme-six'    => 'maps_template_new',
			'res-theme-seven'  => 'woo_template_new',
			'res-theme-eight'  => 'woo_template_new',
			'res-theme-nine'   => 'woo_template_sales_count',
			'res-theme-ten'    => 'woo_template_sales_count',
			'res-theme-eleven' => 'woo_template_sales_count',
		);
		foreach ( $this->types() as $prefix => $type ) {
			$res = $type->get_res_themes();
			$this->assertSame( array_keys( $expected ), array_keys( $res ), "{$prefix} res themes changed." );
			foreach ( $expected as $slug => $template ) {
				$this->assertSame( $template, $res[ $slug ]['_template'] );
				$this->assertFalse( strpos( $slug, 'vc-' ) );
			}
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Custom Notification exclusion
	 * ---------------------------------------------------------------------
	 */

	public function test_custom_notification_type_offers_no_vc_theme() {
		$custom = CustomNotification::get_instance();

		foreach ( array( 'conversions', 'comments', 'reviews', 'download_stats', 'email_subscription' ) as $type ) {
			$themes = $custom->get_themes_for_type( $type );
			$this->assertIsArray( $themes );
			foreach ( $themes as $theme ) {
				$this->assertFalse( strpos( $theme, '_vc-' ), "Custom Notification offers {$theme} for {$type}." );
			}

			// Everything else is still offered: the list equals the previous
			// result (registered themes minus count/maps themes) minus vc.
			$all = ExtensionFactory::get_instance()->get_themes_for_type( $type );
			if ( 'conversions' === $type ) {
				$all = array_diff( $all, Conversions::get_instance()->conversions_count );
			}
			$all      = array_diff( $all, array( 'conversions_maps_theme', 'conversions_conv-theme-six', 'comments_maps_theme', 'email_subscription_maps_theme' ) );
			$expected = array();
			foreach ( $all as $theme ) {
				if ( false === strpos( $theme, '_vc-' ) ) {
					$expected[] = $theme;
				}
			}
			$this->assertSame( $expected, $themes );
		}

		foreach ( $custom->supported_themes() as $group => $themes ) {
			foreach ( $themes as $theme ) {
				$this->assertFalse( strpos( $theme, '_vc-' ), "supported_themes()[{$group}] has {$theme}." );
			}
		}
	}

	public function test_sales_custom_source_offers_no_vc_theme() {
		$source = CustomNotificationConversions::get_instance();
		$themes = $source->get_themes();
		$this->assertIsArray( $themes );

		foreach ( array_keys( $themes ) as $key ) {
			$this->assertFalse( strpos( $key, '_vc-' ), "custom_notification_conversions offers {$key}." );
		}

		// Every other Sales Notification theme is still offered, in order and unchanged.
		$expected = array();
		foreach ( $this->conversions->get_themes() as $slug => $theme ) {
			if ( in_array( $slug, self::VC_SLUGS, true ) ) {
				continue;
			}
			$expected[ 'conversions_' . $slug ] = $theme;
		}
		$this->assertSame( $expected, $themes );

		// The picker therefore never lists this source for a vc option.
		$options = $source->__nx_themes( array() );
		foreach ( GlobalFields::vc_theme_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, $options );
		}
		$this->assertArrayNotHasKey( 'conversions_vc-receipt', $source->__themes_trigger( array() ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Advanced Design and Button toggles
	 * ---------------------------------------------------------------------
	 */

	public function test_advance_edit_toggles_are_hidden_for_vc_keys_only() {
		$fields = $this->find_fields( $this->builder_tabs(), 'advance_edit' );
		// Design > Themes (CSS-hidden) and the "Advanced Design" section header toggle.
		$this->assertGreaterThanOrEqual( 2, count( $fields ), 'advance_edit fields not found.' );

		foreach ( $fields as $field ) {
			$this->assert_hidden_for_vc_and_unchanged_otherwise( $field );
		}
	}

	public function test_link_button_toggle_is_hidden_for_vc_keys_only() {
		$fields = $this->find_fields( $this->builder_tabs(), 'link_button' );
		$this->assertCount( 1, $fields, 'Expected one link_button field.' );

		$this->assert_hidden_for_vc_and_unchanged_otherwise( $fields[0] );
	}

	public function test_vc_rule_shape() {
		$rule = Rules::includes( 'themes', GlobalFields::vc_theme_keys(), true );
		$this->assertSame(
			array( '!includes', 'themes', GlobalFields::vc_theme_keys() ),
			json_decode( wp_json_encode( $rule ), true )
		);
		// '!includes' rules are never merged into other includes rules.
		$this->assertFalse( $rule->can_add( Rules::includes( 'themes', array( 'conversions_theme-one' ) ) ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Thumbnails
	 * ---------------------------------------------------------------------
	 */

	public function test_vc_thumbnails_exist() {
		foreach ( self::VC_SLUGS as $slug ) {
			$file = NOTIFICATIONX_ASSETS_PATH . 'admin/images/extensions/themes/vc/' . $slug . '.png';
			$this->assertFileExists( $file );
			$this->assertGreaterThan( 0, filesize( $file ) );
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Builder tabs as plain arrays (Rule objects serialized as the admin
	 * receives them).
	 *
	 * @return array
	 */
	protected function builder_tabs() {
		$tabs = GlobalFields::get_instance()->tabs();
		return json_decode( wp_json_encode( $tabs['tabs'] ), true );
	}

	/**
	 * Every field definition named $name (recursive).
	 *
	 * @param mixed  $node
	 * @param string $name
	 * @return array
	 */
	protected function find_fields( $node, $name ) {
		$found = array();
		if ( ! is_array( $node ) ) {
			return $found;
		}
		if ( isset( $node['name'], $node['type'] ) && $name === $node['name'] && in_array( $node['type'], array( 'toggle', 'checkbox' ), true ) ) {
			$found[] = $node;
		}
		foreach ( $node as $child ) {
			if ( is_array( $child ) ) {
				$found = array_merge( $found, $this->find_fields( $child, $name ) );
			}
		}
		return $found;
	}

	/**
	 * The field is hidden for every vc key, and for every other value its
	 * visibility equals the same rules without the vc clause (today's rules).
	 *
	 * @param array $field
	 */
	protected function assert_hidden_for_vc_and_unchanged_otherwise( $field ) {
		$this->assertArrayHasKey( 'rules', $field, "{$field['name']} has no rules." );
		$rules    = $field['rules'];
		$vc_count = 0;
		$previous = $this->strip_vc_clause( $rules, $vc_count );
		$this->assertSame( 1, $vc_count, "{$field['name']} must have exactly one vc clause." );

		$base = array(
			'themes_tab' => 'for_desktop',
			'type'       => 'conversions',
			'source'     => 'woocommerce',
			'link_type'  => 'product_page',
		);

		// Hidden for every vc key, even where every other condition passes.
		foreach ( GlobalFields::vc_theme_keys() as $key ) {
			$values = array_merge( $base, array( 'themes' => $key ) );
			$this->assertTrue( $this->evaluate( $previous, $values ), "Baseline should show {$field['name']} here." );
			$this->assertFalse( $this->evaluate( $rules, $values ), "{$field['name']} is visible for {$key}." );
		}

		// Unchanged for every other value.
		$themes = array(
			null,
			'',
			'conversions_theme-one',
			'conversions_conv-theme-twelve',
			'conversions_conv-theme-fifteen',
			'woocommerce_sales_theme-one',
			'woocommerce_sales_conv-theme-thirteen',
			'comments_theme-one',
			'nx_bar_theme-one',
			'youtube_channel-1',
			'page_analytics_pa-theme-one',
		);
		foreach ( $themes as $theme ) {
			foreach ( array( 'for_desktop', 'for_mobile', 'nxbar_build_with_ai' ) as $themes_tab ) {
				foreach ( array( 'conversions', 'woocommerce_sales', 'video', 'comments', 'page_analytics' ) as $type ) {
					foreach ( array( 'none', 'product_page' ) as $link_type ) {
						$values = array(
							'themes_tab' => $themes_tab,
							'type'       => $type,
							'source'     => 'woocommerce',
							'link_type'  => $link_type,
						);
						if ( null !== $theme ) {
							$values['themes'] = $theme;
						}
						$this->assertSame(
							$this->evaluate( $previous, $values ),
							$this->evaluate( $rules, $values ),
							"{$field['name']} changed for " . wp_json_encode( $values )
						);
					}
				}
			}
		}
	}

	/**
	 * Removes the ['!includes', 'themes', vc keys] clause from serialized rules.
	 *
	 * @param mixed $rules
	 * @param int   $count Number of clauses removed.
	 * @return mixed Rules without the clause, or null when nothing is left.
	 */
	protected function strip_vc_clause( $rules, &$count ) {
		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return $rules;
		}
		if ( in_array( $rules[0], array( 'and', 'or' ), true ) ) {
			$children = array();
			foreach ( array_slice( $rules, 1 ) as $child ) {
				$child = $this->strip_vc_clause( $child, $count );
				if ( null !== $child ) {
					$children[] = $child;
				}
			}
			return empty( $children ) ? null : array_merge( array( $rules[0] ), $children );
		}
		if ( '!includes' === $rules[0] && 'themes' === $rules[1] && is_array( $rules[2] ) ) {
			$value = $rules[2];
			$keys  = GlobalFields::vc_theme_keys();
			sort( $value );
			sort( $keys );
			if ( $value === $keys ) {
				$count++;
				return null;
			}
		}
		return $rules;
	}

	/**
	 * Evaluates serialized rules the way the quickbuilder `when()` does for
	 * the conditions used here (is, !is, includes, !includes, and, or).
	 *
	 * @param mixed $rules
	 * @param array $values
	 * @return bool
	 */
	protected function evaluate( $rules, $values ) {
		if ( null === $rules || '' === $rules || array() === $rules ) {
			return true;
		}
		$op = $rules[0];
		if ( 'and' === $op || 'or' === $op ) {
			$results = array();
			foreach ( array_slice( $rules, 1 ) as $child ) {
				$results[] = $this->evaluate( $child, $values );
			}
			return 'and' === $op ? ! in_array( false, $results, true ) : in_array( true, $results, true );
		}

		list( $condition, $key, $expected ) = $rules;
		$actual = array_key_exists( $key, $values ) ? $values[ $key ] : null;
		switch ( $condition ) {
			case 'is':
				return $actual == $expected; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- mirrors JS ==.
			case '!is':
				return $actual != $expected; // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- mirrors JS !=.
			case 'includes':
				return $this->includes( $actual, $expected, $values );
			case '!includes':
				return ! $this->includes( $actual, $expected, $values );
		}
		$this->fail( "Unsupported rule condition {$condition}." );
		return false;
	}

	/**
	 * quickbuilder `includes`.
	 *
	 * @param mixed $actual
	 * @param mixed $expected
	 * @param array $values
	 * @return bool
	 */
	protected function includes( $actual, $expected, $values ) {
		if ( empty( $values ) ) {
			return false;
		}
		if ( is_array( $expected ) && is_array( $actual ) ) {
			return (bool) array_intersect( $actual, $expected );
		}
		if ( is_array( $expected ) && is_string( $actual ) ) {
			return in_array( $actual, $expected, true );
		}
		if ( is_array( $actual ) && is_string( $expected ) ) {
			return in_array( $expected, $actual, true );
		}
		return false;
	}
}
