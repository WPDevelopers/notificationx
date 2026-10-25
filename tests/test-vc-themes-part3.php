<?php
/**
 * "Verified card" (vc) themes, Part 3: Pledge, Enrolment, Submission, Ratings
 * and Installs.
 *
 * Covers the PHP side only (Parts 1 and 2 are in test-vc-themes.php and
 * test-vc-themes-part2.php):
 *  - each vc entry is appended at the END of its theme list, is free (no
 *    is_pro), has no defaults and carries the planned template params;
 *  - Pledge defaults the amount to tag_amount (the other donation themes keep
 *    tag_none);
 *  - Pledge, Enrolment and Submission join their existing template group;
 *    Ratings (per review source) and Installs get their own group that lists
 *    only their key; the existing groups are unchanged apart from the key;
 *  - Google Ratings uses the average (tag_rating) and "reviews", because
 *    Google's count is the total of all ratings;
 *  - WPOrgReview::total_rated_theme() keeps one entry per notification for
 *    reviews_vc-ratings, as for reviews_total-rated;
 *  - GlobalFields::vc_theme_keys() lists every Part 3 key;
 *  - the thumbnails exist (incomplete until they are added).
 *
 * Types and Extensions are instantiated and init()/init_extension() is called
 * directly, so the test does not depend on module gating or on Give, LMS,
 * form, WooCommerce or ReviewX plugins being active.
 *
 * @package Notificationx
 */

use NotificationX\Core\Helper;
use NotificationX\Extensions\CF7\CF7;
use NotificationX\Extensions\Give\Give;
use NotificationX\Extensions\Google\GoogleReviews;
use NotificationX\Extensions\Tutor\Tutor;
use NotificationX\Extensions\WordPress\WPOrgStats;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\ReviewX\ReviewX;
use NotificationX\Extensions\WooCommerce\WooCommerceSalesReviews;
use NotificationX\Extensions\WooCommerce\WooReviews;
use NotificationX\Extensions\WordPress\WPOrgReview;
use NotificationX\Types\ContactForm;
use NotificationX\Types\DownloadStats;
use NotificationX\Types\Donations;
use NotificationX\Types\ELearning;
use NotificationX\Types\Reviews;

/**
 * @group themes
 * @group vc-themes
 */
class Test_VC_Themes_Part3 extends WP_UnitTestCase {

	const PART3_KEYS = array(
		'donation_vc-pledge',
		'elearning_vc-enrol',
		'form_vc-submission',
		'reviews_vc-ratings',
		'woo_reviews_vc-ratings',
		'woocommerce_sales_reviews_vc-ratings',
		'reviewx_vc-ratings',
		'google_reviews_vc-ratings',
		'download_stats_vc-installs',
	);

	/**
	 * Theme slugs before Part 3, in picker order.
	 */
	const EXISTING_DONATION  = array( 'theme-one', 'theme-two', 'theme-three', 'theme-four', 'theme-five', 'conv-theme-six', 'maps_theme', 'conv-theme-seven', 'conv-theme-eight', 'conv-theme-nine' );
	const EXISTING_ELEARNING = array( 'theme-one', 'theme-two', 'theme-three', 'theme-four', 'theme-five', 'conv-theme-six', 'conv-theme-seven', 'conv-theme-eight', 'conv-theme-nine', 'maps_theme' );
	const EXISTING_FORM      = array( 'theme-one', 'theme-two', 'theme-three' );

	/**
	 * Theme slugs of a Type or of an Extension's own list.
	 *
	 * @param object $holder Type or Extension.
	 * @return array
	 */
	protected function own_themes( $holder ) {
		return $holder instanceof \NotificationX\Types\Types ? $holder->get_themes() : $holder->themes;
	}

	public function test_vc_theme_keys_lists_every_part_three_key() {
		$keys = GlobalFields::vc_theme_keys();
		$this->assertSame( count( $keys ), count( array_unique( $keys ) ), 'vc_theme_keys() has duplicates.' );
		foreach ( self::PART3_KEYS as $key ) {
			$this->assertContains( $key, $keys );
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Pledge, Enrolment, Submission
	 * ---------------------------------------------------------------------
	 */

	public function test_pledge_entry_and_group() {
		$type = Donations::get_instance();
		$type->init();
		$themes = $type->get_themes();

		$this->assertSame( array_merge( self::EXISTING_DONATION, array( 'vc-pledge' ) ), array_keys( $themes ) );
		$theme = $themes['vc-pledge'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'circle', $theme['image_shape'] );
		$this->assertSame( NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/vc-pledge.png', $theme['source'] );
		$this->assertSame( 'tag_name', $theme['template']['first_param'] );
		$this->assertSame( 'donated', $theme['template']['second_param'] );
		// The amount is on by default for this theme only; Give's amount ends in "for".
		$this->assertSame( 'tag_amount', $theme['template']['third_param'] );
		$this->assertSame( 'tag_none', $themes['theme-one']['template']['third_param'] );
		$this->assertSame( 'tag_title', $theme['template']['fourth_param'] );
		$this->assertSame( 'tag_time', $theme['template']['fifth_param'] );

		$templates = $type->get_templates();
		$this->assertSame(
			array( 'donation_theme-one', 'donation_theme-two', 'donation_theme-three', 'donation_theme-four', 'donation_theme-five', 'donation_maps_theme', 'donation_vc-pledge' ),
			$templates['donation_template_new']['_themes']
		);
		$this->assertNotContains( 'donation_vc-pledge', $templates['donation_template_sales_count']['_themes'] );
	}

	public function test_enrol_entry_and_group() {
		$type = ELearning::get_instance();
		$type->init();
		$themes = $type->get_themes();

		$this->assertSame( array_merge( self::EXISTING_ELEARNING, array( 'vc-enrol' ) ), array_keys( $themes ) );
		$theme = $themes['vc-enrol'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'rounded', $theme['image_shape'] );
		$this->assertSame( 'tag_name', $theme['template']['first_param'] );
		$this->assertSame( 'just enrolled', $theme['template']['second_param'] );
		$this->assertSame( 'tag_course_title', $theme['template']['third_param'] );
		$this->assertSame( 'tag_time', $theme['template']['fourth_param'] );

		$templates = $type->get_templates();
		$this->assertSame( 'elearning_vc-enrol', end( $templates['elearning_template_new']['_themes'] ) );
		$this->assertNotContains( 'elearning_vc-enrol', $templates['elearning_template_sales_count']['_themes'] );
	}

	public function test_submission_entry_and_group() {
		$type = ContactForm::get_instance();
		$type->init();
		$themes = $type->get_themes();

		$this->assertSame( array_merge( self::EXISTING_FORM, array( 'vc-submission' ) ), array_keys( $themes ) );
		$theme = $themes['vc-submission'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'circle', $theme['image_shape'] );
		// The submitter field is picked per form, as for the other form themes.
		$this->assertSame( 'select_a_tag', $theme['template']['first_param'] );
		$this->assertSame( 'recently contacted via', $theme['template']['second_param'] );
		$this->assertSame( 'tag_title', $theme['template']['third_param'] );
		$this->assertSame( 'tag_time', $theme['template']['fourth_param'] );

		$templates = $type->get_templates();
		$this->assertSame(
			array( 'form_theme-one', 'form_theme-two', 'form_theme-three', 'form_vc-submission' ),
			$templates['form_template_new']['_themes']
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Ratings
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Ratings holders: [ instance, key prefix, expected third_param ].
	 *
	 * @return array
	 */
	protected function ratings() {
		$reviews = Reviews::get_instance();
		$reviews->init();
		$woo = WooReviews::get_instance();
		$woo->init_extension();
		$woo_sales = WooCommerceSalesReviews::get_instance();
		$woo_sales->init_extension();
		$reviewx = ReviewX::get_instance();
		$reviewx->init_extension();

		return array(
			'reviews'                   => array( $reviews, 'reviews_', 'tag_plugin_name' ),
			'woo_reviews'               => array( $woo, 'woo_reviews_', 'tag_product_title' ),
			'woocommerce_sales_reviews' => array( $woo_sales, 'woocommerce_sales_reviews_', 'tag_product_title' ),
			'reviewx'                   => array( $reviewx, 'reviewx_', 'tag_product_post_title' ),
		);
	}

	public function test_ratings_entries_are_last_free_and_aggregate() {
		foreach ( $this->ratings() as $id => list( $holder, $prefix, $third ) ) {
			$themes = $this->own_themes( $holder );
			$keys   = array_keys( $themes );
			$this->assertSame( 'vc-ratings', end( $keys ), "{$id}: Ratings is not last." );
			$this->assertSame( 'vc-scorecard', $keys[ count( $keys ) - 2 ], "{$id}: Ratings must follow Scorecard." );

			$theme = $themes['vc-ratings'];
			$this->assertArrayNotHasKey( 'is_pro', $theme );
			$this->assertArrayNotHasKey( 'defaults', $theme );
			$this->assertSame( 'rounded', $theme['image_shape'] );
			$this->assertArrayNotHasKey( 'show_notification_image', $theme );
			$this->assertSame(
				array(
					'first_param'  => 'tag_rated',
					'second_param' => 'people rated',
					'third_param'  => $third,
				),
				$theme['template'],
				"{$id}: template."
			);

			$templates = $holder->get_templates();
			$this->assertArrayHasKey( 'vc_ratings_template', $templates );
			$group = $templates['vc_ratings_template'];
			$this->assertSame( array( $prefix . 'vc-ratings' ), $group['_themes'] );
			$this->assertSame( array( 'tag_rated' ), array_keys( $group['first_param'] ) );
			$this->assertSame( array( $third ), array_keys( $group['third_param'] ) );
			foreach ( $templates as $key => $other ) {
				if ( 'vc_ratings_template' === $key ) {
					continue;
				}
				$this->assertNotContains( $prefix . 'vc-ratings', $other['_themes'], "{$id}: {$key} lists the vc key." );
			}
		}
	}

	public function test_google_ratings_entry_and_group() {
		$google = GoogleReviews::get_instance();
		$google->init_extension();
		$keys = array_keys( $google->themes );
		$this->assertSame( 'vc-ratings', end( $keys ) );
		$this->assertSame( 'vc-scorecard', $keys[ count( $keys ) - 2 ] );

		$theme = $google->themes['vc-ratings'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'rounded', $theme['image_shape'] );
		// The place icon, as Google's own total-rated theme.
		$this->assertSame( 'greview_icon', $theme['show_notification_image'] );
		$this->assertSame(
			array(
				'first_param'  => 'tag_rated',
				'second_param' => 'reviews',
				'third_param'  => 'tag_place_name',
				'fourth_param' => 'tag_rating',
			),
			$theme['template']
		);

		$templates = $google->get_templates();
		$group     = $templates['google_reviews_vc_ratings_template'];
		$this->assertSame( array( 'google_reviews_vc-ratings' ), $group['_themes'] );
		$this->assertSame( array( 'tag_rated' ), array_keys( $group['first_param'] ) );
		$this->assertSame( array( 'tag_place_name' ), array_keys( $group['third_param'] ) );
		$this->assertSame( array( 'tag_rating' ), array_keys( $group['fourth_param'] ) );
		$this->assertNotContains( 'google_reviews_vc-ratings', $templates['google_reviews_template_new']['_themes'] );
	}

	public function test_wp_reviews_keeps_one_ratings_entry_per_notification() {
		$source = WPOrgReview::get_instance();
		$entry  = function ( $nx_id, $id ) {
			return array(
				'nx_id'   => $nx_id,
				'source'  => 'wp_reviews',
				'id'      => $id,
				'name'    => 'NotificationX',
				'ratings' => array( '5' => 435 ),
				'slug'    => 'notificationx',
			);
		};
		$settings = function ( $theme ) {
			return array(
				'theme'                   => $theme,
				'wp_reviews_product_type' => 'plugin',
			);
		};
		$entries = array( $entry( 1, 'a' ), $entry( 1, 'b' ), $entry( 2, 'c' ), $entry( 2, 'd' ), $entry( 3, 'e' ), $entry( 3, 'f' ) );
		$notifications = array(
			1 => $settings( 'reviews_vc-ratings' ),
			2 => $settings( 'reviews_total-rated' ),
			3 => $settings( 'reviews_vc-scorecard' ),
		);

		$result = array_values( $source->total_rated_theme( $entries, array(), $notifications ) );
		$by_nx  = array();
		foreach ( $result as $item ) {
			$by_nx[ $item['nx_id'] ][] = $item;
		}
		// vc-ratings and total-rated: one entry each, with the 5-star count.
		$this->assertCount( 1, $by_nx[1] );
		$this->assertSame( 435, $by_nx[1][0]['rated'] );
		$this->assertSame( 'NotificationX', $by_nx[1][0]['plugin_name'] );
		$this->assertSame( 'https://wordpress.org/plugins/notificationx', $by_nx[1][0]['link'] );
		$this->assertCount( 1, $by_nx[2] );
		// Every other theme keeps all its entries, untouched.
		$this->assertCount( 2, $by_nx[3] );
		$this->assertArrayNotHasKey( 'rated', $by_nx[3][0] );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Installs
	 * ---------------------------------------------------------------------
	 */

	public function test_installs_entry_and_own_group() {
		$type = DownloadStats::get_instance();
		$type->init();
		$themes = $type->get_themes();
		$keys   = array_keys( $themes );

		$this->assertSame( 'vc-installs', end( $keys ) );
		$this->assertSame( 'vc-counter', $keys[ count( $keys ) - 2 ] );
		$theme = $themes['vc-installs'];
		$this->assertArrayNotHasKey( 'is_pro', $theme );
		$this->assertArrayNotHasKey( 'defaults', $theme );
		$this->assertSame( 'rounded', $theme['image_shape'] );
		$this->assertSame(
			array(
				'first_param'  => 'tag_active_installs',
				'second_param' => 'people are actively using',
				'third_param'  => 'tag_plugin_theme_name',
			),
			$theme['template']
		);

		$templates = $type->get_templates();
		$group     = $templates['vc_installs_template'];
		$this->assertSame( array( 'download_stats_vc-installs' ), $group['_themes'] );
		// Only the active-install count: "40K" fits the tile, "12 times today" would not.
		$this->assertSame( array( 'tag_active_installs' ), array_keys( $group['first_param'] ) );
		$this->assertSame( array( 'tag_plugin_theme_name' ), array_keys( $group['third_param'] ) );
		$this->assertSame( array( 'download_stats_actively_using' ), $templates['actively_using_template_new']['_themes'] );
		$this->assertNotContains( 'download_stats_vc-installs', $templates['wp_stats_template_new']['_themes'] );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Picker and theme triggers
	 * ---------------------------------------------------------------------
	 */

	public function test_picker_options_are_free_and_triggers_set_template_and_no_button() {
		Donations::get_instance()->init();
		ELearning::get_instance()->init();
		ContactForm::get_instance()->init();
		Reviews::get_instance()->init();
		DownloadStats::get_instance()->init();
		$cases = array(
			// source instance, key, expected template triggers.
			array( Give::get_instance(), 'donation_vc-pledge', array( 'first_param:tag_name', 'second_param:donated', 'third_param:tag_amount', 'fourth_param:tag_title', 'fifth_param:tag_time' ) ),
			array( Tutor::get_instance(), 'elearning_vc-enrol', array( 'first_param:tag_name', 'second_param:just enrolled', 'third_param:tag_course_title', 'fourth_param:tag_time' ) ),
			array( CF7::get_instance(), 'form_vc-submission', array( 'first_param:select_a_tag', 'second_param:recently contacted via', 'third_param:tag_title', 'fourth_param:tag_time' ) ),
			array( WPOrgReview::get_instance(), 'reviews_vc-ratings', array( 'first_param:tag_rated', 'second_param:people rated', 'third_param:tag_plugin_name' ) ),
			array( WPOrgStats::get_instance(), 'download_stats_vc-installs', array( 'first_param:tag_active_installs', 'second_param:people are actively using', 'third_param:tag_plugin_theme_name' ) ),
		);
		foreach ( $cases as list( $source, $key, $template ) ) {
			$options = $source->__nx_themes( array() );
			$keys    = array_keys( $options );
			$this->assertSame( $key, end( $keys ), "{$key} is not the last picker card." );
			$this->assertEmpty( $options[ $key ]['is_pro'], "{$key} is locked as Pro." );
			$this->assertStringEndsWith( 'images/extensions/themes/vc/' . substr( $key, strrpos( $key, '_' ) + 1 ) . '.png', $options[ $key ]['icon'] );

			$trigger = $source->__themes_trigger( array() )[ $key ];
			$this->assertContains( '@link_button:false', $trigger, "{$key}: Button must be off." );
			foreach ( $template as $item ) {
				$this->assertContains( '@notification-template.' . $item, $trigger, "{$key}: {$item}." );
			}
		}
	}

	public function test_freemius_installs_count_is_shortened_for_the_vc_key_only() {
		$stats = DownloadStats::get_instance();
		$vc    = array( 'themes' => 'download_stats_vc-installs' );

		$this->assertSame( array( 'active_installs' => Helper::nice_number( 123456 ) ), $stats->vc_installs_count( array( 'active_installs' => 123456 ), $vc ) );
		// Already shortened (WP.org) or not a number: unchanged.
		$this->assertSame( array( 'active_installs' => '40K' ), $stats->vc_installs_count( array( 'active_installs' => '40K' ), $vc ) );
		// Every other theme: unchanged.
		$this->assertSame(
			array( 'active_installs' => 123456 ),
			$stats->vc_installs_count( array( 'active_installs' => 123456 ), array( 'themes' => 'download_stats_actively_using' ) )
		);
		$this->assertNotFalse( has_filter( 'nx_filtered_entry_freemius_stats', array( $stats, 'vc_installs_count' ) ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Thumbnails
	 * ---------------------------------------------------------------------
	 */

	public function test_part_three_thumbnails_exist() {
		$missing = array();
		foreach ( array( 'vc-pledge', 'vc-enrol', 'vc-submission', 'vc-ratings', 'vc-installs' ) as $slug ) {
			$file = NOTIFICATIONX_ASSETS_PATH . 'admin/images/extensions/themes/vc/' . $slug . '.png';
			if ( ! file_exists( $file ) ) {
				$missing[] = $slug;
			}
		}
		if ( $missing ) {
			$this->markTestIncomplete( 'Thumbnails not added yet: ' . implode( ', ', $missing ) );
		}
		$this->assertEmpty( $missing );
	}
}
