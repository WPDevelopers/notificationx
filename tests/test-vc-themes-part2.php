<?php
/**
 * "Verified card" (vc) themes, Part 2: Thread, Scorecard and Counter.
 *
 * Covers the PHP side only (Part 1, Receipt and Showcase, and the shared
 * Advanced Design / Button rules are in test-vc-themes.php):
 *  - each vc entry is appended at the END of its theme list, is free (no
 *    is_pro), has no defaults and carries the planned template params;
 *  - the theme trigger contains @link_button:false and the template params;
 *    Scorecard never uses tag_rated or tag_time;
 *  - Thread joins comments_template_with_comments, Counter joins
 *    wp_stats_template_new, each Scorecard gets its own group listing only
 *    its key; the existing groups are unchanged apart from the appended key;
 *  - GlobalFields::vc_theme_keys() lists every Part 2 key;
 *  - the thumbnails exist.
 *
 * Types and Extensions are instantiated and init()/init_extension() is called
 * directly, so the test does not depend on module gating or on WooCommerce,
 * ReviewX or Pro being active.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\Google\GoogleReviews;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\ReviewX\ReviewX;
use NotificationX\Extensions\WooCommerce\WooCommerceSalesReviews;
use NotificationX\Extensions\WooCommerce\WooReviews;
use NotificationX\Extensions\WordPress\WPComments;
use NotificationX\Extensions\WordPress\WPOrgReview;
use NotificationX\Extensions\WordPress\WPOrgStats;
use NotificationX\Types\Comments;
use NotificationX\Types\DownloadStats;
use NotificationX\Types\Reviews;

/**
 * @group themes
 * @group vc-themes
 */
class Test_VC_Themes_Part2 extends WP_UnitTestCase {

	/**
	 * Existing theme slugs before Part 2, in picker order.
	 */
	const EXISTING_COMMENTS = array( 'theme-one', 'theme-two', 'theme-three', 'theme-six-free', 'theme-seven-free', 'theme-eight-free', 'theme-four', 'theme-five', 'maps_theme' );
	const EXISTING_REVIEWS  = array( 'total-rated', 'reviewed', 'review_saying', 'review-comment', 'review-comment-2', 'review-comment-3' );
	const EXISTING_GOOGLE   = array( 'total-rated', 'reviewed', 'review-comment', 'review-comment-2', 'review-comment-3', 'maps_theme' );
	const EXISTING_STATS    = array( 'today-download', '7day-download', 'actively_using', 'total-download' );

	const PART2_KEYS = array(
		'comments_vc-thread',
		'reviews_vc-scorecard',
		'woo_reviews_vc-scorecard',
		'woocommerce_sales_reviews_vc-scorecard',
		'reviewx_vc-scorecard',
		'google_reviews_vc-scorecard',
		'download_stats_vc-counter',
	);

	/**
	 * Scorecard holders: extension or type instance, theme prefix, expected
	 * template (third, fifth, sixth) and the template group key.
	 *
	 * @return array
	 */
	protected function scorecards() {
		$reviews = Reviews::get_instance();
		$reviews->init();
		$woo = WooReviews::get_instance();
		$woo->init_extension();
		$woo_sales = WooCommerceSalesReviews::get_instance();
		$woo_sales->init_extension();
		$reviewx = ReviewX::get_instance();
		$reviewx->init_extension();
		$google = GoogleReviews::get_instance();
		$google->init_extension();

		return array(
			'reviews'                   => array( $reviews, 'reviews_', 'tag_plugin_name', 'tag_plugin_review', 'tag_title', 'vc_scorecard_template' ),
			'woo_reviews'               => array( $woo, 'woo_reviews_', 'tag_product_title', 'tag_plugin_review', null, 'vc_scorecard_template' ),
			'woocommerce_sales_reviews' => array( $woo_sales, 'woocommerce_sales_reviews_', 'tag_product_title', 'tag_plugin_review', null, 'vc_scorecard_template' ),
			'reviewx'                   => array( $reviewx, 'reviewx_', 'tag_product_post_title', 'tag_plugin_review', 'tag_title', 'vc_scorecard_template' ),
			'google_reviews'            => array( $google, 'google_reviews_', 'tag_place_name', 'tag_place_review', null, 'google_reviews_vc_scorecard_template' ),
		);
	}

	/**
	 * Theme slugs of an extension's own list or a Type's list.
	 *
	 * @param object $holder Type or Extension.
	 * @return array
	 */
	protected function own_themes( $holder ) {
		return $holder instanceof Reviews ? $holder->get_themes() : $holder->themes;
	}

	public function test_vc_theme_keys_lists_every_part_two_key() {
		$keys = GlobalFields::vc_theme_keys();
		$this->assertSame( count( $keys ), count( array_unique( $keys ) ), 'vc_theme_keys() has duplicates.' );
		foreach ( self::PART2_KEYS as $key ) {
			$this->assertContains( $key, $keys );
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Thread
	 * ---------------------------------------------------------------------
	 */

	public function test_thread_entry() {
		$comments = Comments::get_instance();
		$comments->init();
		$themes = $comments->get_themes();

		$this->assertSame( array_merge( self::EXISTING_COMMENTS, array( 'vc-thread' ) ), array_keys( $themes ) );
		$theme = $themes['vc-thread'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'circle', $theme['image_shape'] );
		$this->assertSame( NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/vc-thread.png', $theme['source'] );
		$this->assertSame( 'tag_name', $theme['template']['first_param'] );
		$this->assertSame( 'commented on', $theme['template']['second_param'] );
		$this->assertSame( 'tag_post_comment', $theme['template']['third_param'] );
		$this->assertSame( 'tag_time', $theme['template']['fourth_param'] );

		$templates = $comments->get_templates();
		$this->assertSame(
			array( 'comments_theme-six-free', 'comments_theme-seven-free', 'comments_theme-eight-free', 'comments_vc-thread' ),
			$templates['comments_template_with_comments']['_themes']
		);
		$this->assertNotContains( 'comments_vc-thread', $templates['comments_template_new']['_themes'] );
		$this->assertSame( array( 'comments_template_new', 'comments_template_with_comments' ), array_keys( $templates ) );

		// Not added to the Pro trim-length field.
		$this->assertNotContains( 'comments_vc-thread', $comments->content_trim_length_dependency( array() ) );
	}

	public function test_thread_picker_and_trigger() {
		$source  = WPComments::get_instance();
		$options = $source->__nx_themes( array() );
		$keys    = array_keys( $options );
		$this->assertSame( 'comments_vc-thread', end( $keys ) );
		$this->assertEmpty( $options['comments_vc-thread']['is_pro'] );

		$trigger = $source->__themes_trigger( array() )['comments_vc-thread'];
		$this->assertContains( '@link_button:false', $trigger );
		$this->assertContains( '@image_shape:circle', $trigger );
		$this->assertContains( '@notification-template.first_param:tag_name', $trigger );
		$this->assertContains( '@notification-template.third_param:tag_post_comment', $trigger );
		$this->assertContains( '@notification-template.fourth_param:tag_time', $trigger );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Scorecard
	 * ---------------------------------------------------------------------
	 */

	public function test_scorecard_entries_are_last_and_free() {
		$existing = array(
			'reviews'                   => self::EXISTING_REVIEWS,
			'woo_reviews'               => self::EXISTING_REVIEWS,
			'woocommerce_sales_reviews' => self::EXISTING_REVIEWS,
			'reviewx'                   => self::EXISTING_REVIEWS,
			'google_reviews'            => self::EXISTING_GOOGLE,
		);
		foreach ( $this->scorecards() as $id => list( $holder, $prefix, $third, $fifth, $sixth ) ) {
			$themes = $this->own_themes( $holder );
			$this->assertSame( array_merge( $existing[ $id ], array( 'vc-scorecard' ) ), array_keys( $themes ), "{$id} theme order changed." );

			$theme = $themes['vc-scorecard'];
			$this->assertArrayNotHasKey( 'is_pro', $theme, "{$id} must be free." );
			$this->assertArrayNotHasKey( 'defaults', $theme );
			$this->assertSame( 'circle', $theme['image_shape'] );
			$this->assertSame( NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/vc-scorecard.png', $theme['source'] );

			$template = $theme['template'];
			$this->assertSame( 'tag_username', $template['first_param'], "{$id}: never tag_rated." );
			$this->assertSame( 'on', $template['second_param'] );
			$this->assertSame( $third, $template['third_param'] );
			$this->assertSame( 'tag_rating', $template['fourth_param'] );
			$this->assertSame( $fifth, $template['fifth_param'] );
			if ( null === $sixth ) {
				$this->assertArrayNotHasKey( 'sixth_param', $template, "{$id} has no review title row." );
			} else {
				$this->assertSame( $sixth, $template['sixth_param'] );
			}
			$this->assertNotContains( 'tag_rated', $template );
			$this->assertNotContains( 'tag_time', $template );
		}
	}

	public function test_scorecard_image_defaults() {
		$cards = $this->scorecards();
		// Google uses the reviewer avatar like its other single-review themes;
		// every other source keeps its own default (no trigger).
		$this->assertSame( 'greview_avatar', $this->own_themes( $cards['google_reviews'][0] )['vc-scorecard']['show_notification_image'] );
		foreach ( array( 'reviews', 'woo_reviews', 'woocommerce_sales_reviews', 'reviewx' ) as $id ) {
			$this->assertArrayNotHasKey( 'show_notification_image', $this->own_themes( $cards[ $id ][0] )['vc-scorecard'] );
		}
	}

	public function test_scorecard_groups_list_only_their_key_and_tags() {
		foreach ( $this->scorecards() as $id => list( $holder, $prefix, $third, $fifth, $sixth, $group ) ) {
			$templates = $holder->get_templates();
			$this->assertSame( $group, array_key_last( $templates ), "{$id}: the vc group is appended last." );
			$tmpl = $templates[ $group ];
			$this->assertSame( array( $prefix . 'vc-scorecard' ), $tmpl['_themes'] );
			$this->assertSame( array( 'tag_username' ), array_keys( $tmpl['first_param'] ) );
			$this->assertSame( array( $third ), array_keys( $tmpl['third_param'] ) );
			$this->assertSame( array( 'tag_rating' ), array_keys( $tmpl['fourth_param'] ) );
			$this->assertSame( array( $fifth ), array_keys( $tmpl['fifth_param'] ) );
			if ( null === $sixth ) {
				$this->assertArrayNotHasKey( 'sixth_param', $tmpl );
			} else {
				$this->assertSame( array( $sixth ), array_keys( $tmpl['sixth_param'] ) );
			}

			// The existing groups do not list the vc key.
			foreach ( $templates as $key => $other ) {
				if ( $key === $group ) {
					continue;
				}
				$this->assertNotContains( $prefix . 'vc-scorecard', $other['_themes'], "{$id}: {$key} lists the vc key." );
			}
		}

		// ReviewX labels its product subject "Product Title".
		$rx = $this->scorecards()['reviewx'][0]->get_templates();
		$this->assertSame( 'Product Title', $rx['vc_scorecard_template']['third_param']['tag_product_post_title'] );
	}

	public function test_scorecard_trigger_on_wp_reviews() {
		Reviews::get_instance()->init();
		$source  = WPOrgReview::get_instance();
		$options = $source->__nx_themes( array() );
		$keys    = array_keys( $options );
		$this->assertSame( 'reviews_vc-scorecard', end( $keys ) );
		$this->assertEmpty( $options['reviews_vc-scorecard']['is_pro'] );

		$trigger = $source->__themes_trigger( array() )['reviews_vc-scorecard'];
		$this->assertContains( '@link_button:false', $trigger );
		$this->assertContains( '@image_shape:circle', $trigger );
		$this->assertContains( '@notification-template.first_param:tag_username', $trigger );
		$this->assertContains( '@notification-template.fourth_param:tag_rating', $trigger );
		$this->assertContains( '@notification-template.fifth_param:tag_plugin_review', $trigger );
		$this->assertContains( '@notification-template.sixth_param:tag_title', $trigger );
		$this->assertNotContains( '@notification-template.first_param:tag_rated', $trigger );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Counter
	 * ---------------------------------------------------------------------
	 */

	public function test_counter_entry() {
		$stats = DownloadStats::get_instance();
		$stats->init();
		$themes = $stats->get_themes();

		$this->assertSame( array_merge( self::EXISTING_STATS, array( 'vc-counter' ) ), array_keys( $themes ) );
		$theme = $themes['vc-counter'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'rounded', $theme['image_shape'] );
		$this->assertSame( NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/vc-counter.png', $theme['source'] );
		$this->assertSame(
			array(
				'first_param'  => 'tag_plugin_theme_name',
				'second_param' => 'has been downloaded',
				'third_param'  => 'tag_last_week',
				'fourth_param' => 'tag_last_week_text',
			),
			$theme['template']
		);

		$templates = $stats->get_templates();
		$this->assertSame(
			array( 'download_stats_today-download', 'download_stats_7day-download', 'download_stats_total-download', 'download_stats_vc-counter' ),
			$templates['wp_stats_template_new']['_themes']
		);
		$this->assertSame( array( 'download_stats_actively_using' ), $templates['actively_using_template_new']['_themes'] );
	}

	public function test_counter_picker_and_trigger() {
		DownloadStats::get_instance()->init();
		$source  = WPOrgStats::get_instance();
		$options = $source->__nx_themes( array() );
		$keys    = array_keys( $options );
		$this->assertSame( 'download_stats_vc-counter', end( $keys ) );
		$this->assertEmpty( $options['download_stats_vc-counter']['is_pro'] );

		$trigger = $source->__themes_trigger( array() )['download_stats_vc-counter'];
		$this->assertContains( '@link_button:false', $trigger );
		$this->assertContains( '@image_shape:rounded', $trigger );
		$this->assertContains( '@notification-template.third_param:tag_last_week', $trigger );
		$this->assertContains( '@notification-template.fourth_param:tag_last_week_text', $trigger );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Thumbnails
	 * ---------------------------------------------------------------------
	 */

	public function test_part_two_thumbnails_exist() {
		foreach ( array( 'vc-thread', 'vc-scorecard', 'vc-counter' ) as $slug ) {
			$file = NOTIFICATIONX_ASSETS_PATH . 'admin/images/extensions/themes/vc/' . $slug . '.png';
			$this->assertFileExists( $file );
			$this->assertGreaterThan( 0, filesize( $file ) );
		}
	}
}
