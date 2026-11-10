<?php
/**
 * Card size presets (84708): the `card_size` setting and its server-side guard.
 *
 * Free's own supported list holds the sales (P4), comments (P5), reviews and
 * download stats (P6) families.
 * For every other theme
 * the setting must be invisible until its family is added: hidden in the
 * builder, removed from what the frontend and the preview receive, and never
 * written to existing notifications. THEME below is such a theme; the tests
 * add it through the `nx_card_size_themes` filter where they need support.
 *
 * @package Notificationx
 */

use NotificationX\Core\CardSize;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\QuickBuild;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\Preview;

class Test_Card_Size extends WP_UnitTestCase {

	// A theme of a family that isn't in this round (Page Analytics).
	const THEME = 'page_analytics_pa-theme-one';
	const OTHER = 'page_analytics_pa-theme-two';

	// Free's own list: sales (P4), comments (P5), reviews and download stats (P6).
	const FREE = array(
		'conversions_theme-one',
		'conversions_theme-two',
		'conversions_theme-three',
		'conversions_theme-five',
		'conversions_conv-theme-twelve',
		'conversions_conv-theme-thirteen',
		'conversions_conv-theme-fifteen',
		'woocommerce_sales_theme-one',
		'woocommerce_sales_theme-two',
		'woocommerce_sales_theme-three',
		'woocommerce_sales_theme-five',
		'woocommerce_sales_conv-theme-twelve',
		'woocommerce_sales_conv-theme-thirteen',
		'woocommerce_sales_conv-theme-fifteen',
		'comments_theme-one',
		'comments_theme-two',
		'comments_theme-three',
		'comments_theme-six-free',
		'comments_theme-seven-free',
		'comments_theme-eight-free',
		'reviews_total-rated',
		'reviews_reviewed',
		'reviews_review_saying',
		'reviews_review-comment',
		'reviews_review-comment-2',
		'reviews_review-comment-3',
		'woo_reviews_total-rated',
		'woo_reviews_reviewed',
		'woo_reviews_review_saying',
		'woo_reviews_review-comment',
		'woo_reviews_review-comment-2',
		'woo_reviews_review-comment-3',
		'reviewx_total-rated',
		'reviewx_reviewed',
		'reviewx_review_saying',
		'reviewx_review-comment',
		'reviewx_review-comment-2',
		'reviewx_review-comment-3',
		'woocommerce_sales_reviews_total-rated',
		'woocommerce_sales_reviews_reviewed',
		'woocommerce_sales_reviews_review_saying',
		'woocommerce_sales_reviews_review-comment',
		'woocommerce_sales_reviews_review-comment-2',
		'woocommerce_sales_reviews_review-comment-3',
		'download_stats_today-download',
		'download_stats_7day-download',
		'download_stats_actively_using',
		'download_stats_total-download',
	);

	public function tear_down() {
		remove_all_filters( 'nx_card_size_themes' );
		parent::tear_down();
	}

	protected function support( $themes ) {
		add_filter(
			'nx_card_size_themes',
			function ( $list ) use ( $themes ) {
				return array_merge( $list, (array) $themes );
			}
		);
	}

	protected function field() {
		$tabs = GlobalFields::get_instance()->tabs();
		return $tabs['tabs']['customize_tab']['fields']['appearance']['fields']['card_size'];
	}

	protected function settings( $extra = array() ) {
		return array_merge(
			array(
				// The guard reads only `themes`; this type and source save without
				// side effects (no source fetches data on save).
				'type'   => 'conversions',
				'source' => 'woocommerce',
				'themes' => self::THEME,
			),
			$extra
		);
	}

	// --- The builder field -----------------------------------------------------

	public function test_field_sits_next_to_size_with_default_and_three_options() {
		$appearance = GlobalFields::get_instance()->tabs()['tabs']['customize_tab']['fields']['appearance']['fields'];
		$this->assertArrayHasKey( 'size', $appearance );

		$field = $this->field();
		$this->assertSame( 'card_size', $field['name'] );
		$this->assertSame( 'select', $field['type'] );
		$this->assertSame( 'default', $field['default'] );
		$this->assertSame( 52, $field['priority'] );
		$this->assertSame( array( 'compact', 'default', 'large' ), array_keys( $field['options'] ) );
		$this->assertSame( array( 'compact', 'default', 'large' ), array_column( $field['options'], 'value' ) );
		$this->assertSame( CardSize::values(), array_column( $field['options'], 'value' ) );
	}

	public function test_field_shows_for_the_free_themes_only() {
		$this->assertSame( self::FREE, CardSize::supported_themes() );
		$this->assertEquals( Rules::includes( 'themes', self::FREE ), $this->field()['rules'] );
		$this->assertFalse( CardSize::is_supported( self::THEME ) );
	}

	public function test_field_shows_for_themes_added_by_the_filter() {
		$this->support( array( self::THEME, self::OTHER ) );
		$this->assertEquals( Rules::includes( 'themes', array_merge( self::FREE, array( self::THEME, self::OTHER ) ) ), $this->field()['rules'] );
	}

	/**
	 * Every listed key is a free theme of its type, and every free theme of the
	 * sales, comments, reviews and download stats sources is listed: nothing Pro-locked,
	 * nothing missing. Google Reviews is a Pro source, so it isn't listed.
	 */
	public function test_free_list_is_exactly_the_free_sales_themes() {
		$free = array();
		foreach ( array( 'conversions' => \NotificationX\Types\Conversions::get_instance(), 'woocommerce_sales' => \NotificationX\Types\WooCommerceSales::get_instance(), 'comments' => \NotificationX\Types\Comments::get_instance(), 'reviews' => \NotificationX\Types\Reviews::get_instance(), 'download_stats' => \NotificationX\Types\DownloadStats::get_instance() ) as $type => $instance ) {
			$themes = $instance->get_themes();
			$this->assertNotEmpty( $themes, "$type has themes" );
			foreach ( $themes as $name => $theme ) {
				if ( empty( $theme['is_pro'] ) ) {
					$free[] = "{$type}_{$name}";
				}
			}
		}
		// Review sources with their own themes (keys already prefixed by the source).
		foreach ( array( \NotificationX\Extensions\WooCommerce\WooReviews::get_instance(), \NotificationX\Extensions\ReviewX\ReviewX::get_instance(), \NotificationX\Extensions\WooCommerce\WooCommerceSalesReviews::get_instance() ) as $source ) {
			$this->assertEmpty( $source->is_pro, get_class( $source ) );
			$themes = $source->get_themes();
			$this->assertCount( 6, $themes, get_class( $source ) );
			foreach ( $themes as $key => $theme ) {
				if ( empty( $theme['is_pro'] ) ) {
					$free[] = $key;
				}
			}
		}
		$this->assertNotEmpty( \NotificationX\Extensions\Google\GoogleReviews::get_instance()->is_pro );
		sort( $free );
		$listed = CardSize::FREE_THEMES;
		sort( $listed );
		$this->assertSame( $free, $listed );
	}

	public function test_free_themes_keep_compact_and_large_without_a_filter() {
		foreach ( self::FREE as $theme ) {
			foreach ( array( 'compact', 'large' ) as $value ) {
				$settings = array( 'type' => 'conversions', 'themes' => $theme, 'card_size' => $value );
				$this->assertSame( $settings, CardSize::normalize( $settings ), "$value / $theme" );
				$this->assertSame( $value, apply_filters( 'nx_filtered_post', $settings, array() )['card_size'], "nx_filtered_post: $value / $theme" );
			}
			$this->assertArrayNotHasKey( 'card_size', CardSize::normalize( array( 'themes' => $theme, 'card_size' => 'default' ) ) );
		}
		// The Pro-locked themes stay at Default until an add-on lists them.
		foreach ( array( 'conversions_theme-four', 'conversions_conv-theme-seven', 'conversions_conv-theme-fourteen', 'woocommerce_sales_conv-theme-sixteen', 'conversions_maps_theme', 'comments_theme-four', 'comments_theme-five', 'google_reviews_total-rated', 'google_reviews_review-comment', 'google_reviews_maps_theme' ) as $theme ) {
			$this->assertArrayNotHasKey( 'card_size', CardSize::normalize( array( 'themes' => $theme, 'card_size' => 'compact' ) ), $theme );
		}
	}

	public function test_field_is_not_in_the_quick_builder() {
		$quick = QuickBuild::get_instance()->tabs( GlobalFields::get_instance()->tabs() );
		$this->assertContains( 'position', $quick['show'] );
		$this->assertNotContains( 'card_size', $quick['show'] );
	}

	// --- The supported list ----------------------------------------------------

	public function test_scales() {
		$this->assertSame( array( 'compact' => 0.8, 'large' => 1.2 ), CardSize::SCALES );
		$this->assertSame( 'card_size', CardSize::FIELD );
		$this->assertSame( 'default', CardSize::DEFAULT_VALUE );
	}

	public function test_supported_themes_drops_verified_cards_and_junk() {
		$this->support( array( self::THEME, 'conversions_vc-receipt', '', 5, null, array( 'x' ), self::THEME, self::OTHER, 'conversions_theme-one' ) );
		$this->assertSame( array_merge( self::FREE, array( self::THEME, self::OTHER ) ), CardSize::supported_themes() );
		$this->assertTrue( CardSize::is_supported( self::THEME ) );
		$this->assertFalse( CardSize::is_supported( 'conversions_vc-receipt' ) );
		$this->assertFalse( CardSize::is_supported( null ) );
		$this->assertFalse( CardSize::is_supported( array( self::THEME ) ) );
	}

	public function test_supported_themes_survives_a_broken_filter() {
		add_filter( 'nx_card_size_themes', '__return_false' );
		$this->assertSame( self::FREE, CardSize::supported_themes() );
	}

	// --- normalize() -----------------------------------------------------------

	public function test_normalize_leaves_settings_without_the_key_untouched() {
		$settings = $this->settings( array( 'size' => array( 'desktop' => 500 ) ) );
		$this->assertSame( $settings, CardSize::normalize( $settings ) );
		$this->support( self::THEME );
		$this->assertSame( $settings, CardSize::normalize( $settings ) );
		foreach ( array( null, '', 'x', 5, false ) as $not_settings ) {
			$this->assertSame( $not_settings, CardSize::normalize( $not_settings ) );
		}
	}

	/**
	 * Every value, for an unsupported, a supported and a Verified Cards theme.
	 */
	public function test_normalize_table() {
		$this->support( array( self::THEME, 'conversions_vc-receipt' ) );
		$kept = array(
			// value, theme, kept?
			array( 'compact', self::THEME, true ),
			array( 'large', self::THEME, true ),
			array( 'default', self::THEME, false ),
			array( 'compact', self::OTHER, false ),
			array( 'large', self::OTHER, false ),
			array( 'compact', 'conversions_vc-receipt', false ),
			array( 'large', 'conversions_vc-receipt', false ),
			array( 'Compact', self::THEME, false ),
			array( 'huge', self::THEME, false ),
			array( '', self::THEME, false ),
			array( null, self::THEME, false ),
			array( 0.8, self::THEME, false ),
			array( 1, self::THEME, false ),
			array( array( 'compact' ), self::THEME, false ),
		);
		foreach ( $kept as list( $value, $theme, $keep ) ) {
			$settings   = $this->settings( array( 'themes' => $theme, 'card_size' => $value ) );
			$normalized = CardSize::normalize( $settings );
			$label      = var_export( $value, true ) . " / $theme";
			if ( $keep ) {
				$this->assertSame( $settings, $normalized, $label );
			} else {
				unset( $settings['card_size'] );
				$this->assertSame( $settings, $normalized, $label );
			}
		}
	}

	public function test_normalize_removes_the_value_without_a_theme() {
		$this->support( self::THEME );
		$settings = $this->settings( array( 'card_size' => 'compact' ) );
		unset( $settings['themes'] );
		$this->assertArrayNotHasKey( 'card_size', CardSize::normalize( $settings ) );
		$this->assertArrayNotHasKey( 'card_size', CardSize::normalize( array_merge( $settings, array( 'themes' => array( self::THEME ) ) ) ) );
	}

	// --- The frontend hook -----------------------------------------------------

	public function test_filtered_post_applies_the_guard() {
		$this->assertSame( 10, has_filter( 'nx_filtered_post', array( CardSize::get_instance(), 'filtered_post' ) ) );

		$filtered = apply_filters( 'nx_filtered_post', $this->settings( array( 'card_size' => 'compact' ) ), array() );
		$this->assertArrayNotHasKey( 'card_size', $filtered );

		$this->support( self::THEME );
		$filtered = apply_filters( 'nx_filtered_post', $this->settings( array( 'card_size' => 'compact' ) ), array() );
		$this->assertSame( 'compact', $filtered['card_size'] );

		$filtered = apply_filters( 'nx_filtered_post', $this->settings( array( 'card_size' => 'default' ) ), array() );
		$this->assertArrayNotHasKey( 'card_size', $filtered );
	}

	/**
	 * A notification saved before the setting existed: reading it fills in the
	 * field default (NotificationX::normalize_post() does that for every field),
	 * the frontend never receives it, and nothing is written to the row.
	 */
	public function test_existing_notification_reads_as_default_and_is_never_written() {
		$saved = PostType::get_instance()->save_post(
			$this->settings(
				array(
					'enabled' => true,
					'title'   => 'card size: existing notification',
				)
			)
		);
		$nx_id = (int) $saved['nx_id'];

		$row_before = Database::get_instance()->get_post( Database::$table_posts, $nx_id, 'data, updated_at' );
		$this->assertArrayNotHasKey( 'card_size', (array) $row_before['data'] );

		$post = PostType::get_instance()->get_post( $nx_id );
		$this->assertSame( 'default', $post['card_size'] );
		$this->assertArrayNotHasKey( 'card_size', apply_filters( 'nx_filtered_post', $post, array() ) );

		$this->support( self::THEME );
		$post = PostType::get_instance()->get_post( $nx_id );
		$this->assertSame( 'default', $post['card_size'] );
		$this->assertArrayNotHasKey( 'card_size', apply_filters( 'nx_filtered_post', $post, array() ) );

		$this->assertSame( $row_before, Database::get_instance()->get_post( Database::$table_posts, $nx_id, 'data, updated_at' ) );
	}

	public function test_saved_value_is_kept_when_its_theme_is_unsupported() {
		$saved = PostType::get_instance()->save_post(
			$this->settings(
				array(
					'enabled'   => true,
					'title'     => 'card size: saved compact',
					'card_size' => 'compact',
				)
			)
		);
		$nx_id = (int) $saved['nx_id'];

		// Stored as saved, so a theme that becomes supported again restores it.
		$this->assertSame( 'compact', PostType::get_instance()->get_post( $nx_id )['card_size'] );
		$this->assertArrayNotHasKey( 'card_size', apply_filters( 'nx_filtered_post', PostType::get_instance()->get_post( $nx_id ), array() ) );

		$this->support( self::THEME );
		$this->assertSame( 'compact', apply_filters( 'nx_filtered_post', PostType::get_instance()->get_post( $nx_id ), array() )['card_size'] );
	}

	// --- The builder preview ---------------------------------------------------

	public function test_preview_applies_the_guard() {
		$preview = array_merge( $this->settings(), array( 'global_queue' => false, 'card_size' => 'large' ) );

		$this->assertArrayNotHasKey( 'card_size', Preview::get_instance()->preview_settings( $preview ) );

		$this->support( self::THEME );
		$this->assertSame( 'large', Preview::get_instance()->preview_settings( $preview )['card_size'] );

		$preview['card_size'] = 'default';
		$this->assertArrayNotHasKey( 'card_size', Preview::get_instance()->preview_settings( $preview ) );
	}
}
