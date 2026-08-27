<?php
/**
 * Facebook Reviews: normalizing an API review into a renderable entry.
 *
 * The interesting cases are all the ragged ones. Facebook recommendations carry
 * no star rating, a reviewer's photo depends on that person's privacy settings,
 * a recommendation can be a bare thumbs-up with no words, and most dates are
 * only ever "2 weeks ago". Every one of those is a normal review, so what these
 * tests pin down is that none of them produces a broken notification — and that
 * nothing is invented to paper over a gap.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\Facebook\FacebookReviews;
use NotificationX\Extensions\Facebook\FacebookReviewsManaged;

class Test_Facebook_Reviews extends WP_UnitTestCase {

	/** A complete review as the NotificationX API delivers it. */
	private function payload( $overrides = [] ) {
		return array_replace_recursive( [
			'event'               => 'social_review.created',
			'source'              => 'facebook',
			'connection_id'       => 'conn_abc123',
			'review_id'           => 'story_1',
			'page_id'             => '111222333',
			'page_name'           => 'Example Business',
			'page_rating'         => [ 'overall' => 4.6, 'count' => 128 ],
			'reviewer'            => [
				'name'   => 'Jane Doe',
				'avatar' => 'https://scontent.example/jane.jpg',
				'url'    => 'https://www.facebook.com/jane',
			],
			'rating'              => null,
			'recommendation_type' => 'positive',
			'content'             => 'Excellent service, would come back.',
			'review_url'          => 'https://www.facebook.com/examplebiz/posts/1',
			'created_at'          => '2026-08-01T10:30:00+00:00',
			'updated_at'          => null,
			'meta'                => [],
		], $overrides );
	}

	public function test_maps_a_complete_review() {
		$data = FacebookReviews::map_review( $this->payload() );

		$this->assertSame( 'Jane Doe', $data['username'] );
		$this->assertSame( 'https://scontent.example/jane.jpg', $data['profile_photo_url'] );
		$this->assertSame( 'https://www.facebook.com/jane', $data['reviewer_url'] );
		$this->assertSame( 'Example Business', $data['place_name'] );
		$this->assertSame( 'Excellent service, would come back.', $data['place_review'] );
		$this->assertTrue( $data['is_recommended'] );
		$this->assertSame( 128, $data['rated'] );
		$this->assertSame( 4.6, $data['page_rating'] );
		$this->assertSame( strtotime( '2026-08-01T10:30:00+00:00' ), $data['timestamp'] );
	}

	/**
	 * The mapping decision that matters most. A Facebook recommendation is not a
	 * five-star review; inventing a star would put stars on a notification for a
	 * review that never had them and corrupt any average shown beside it.
	 */
	public function test_a_recommendation_gets_no_invented_star_rating() {
		$positive = FacebookReviews::map_review( $this->payload( [ 'recommendation_type' => 'positive' ] ) );
		$negative = FacebookReviews::map_review( $this->payload( [ 'recommendation_type' => 'negative' ] ) );

		$this->assertSame( 0, $positive['rating'] );
		$this->assertTrue( $positive['is_recommended'] );
		$this->assertSame( 0, $negative['rating'] );
		$this->assertFalse( $negative['is_recommended'] );
	}

	public function test_keeps_a_legacy_star_rating() {
		$data = FacebookReviews::map_review( $this->payload( [ 'rating' => 4, 'recommendation_type' => null ] ) );

		$this->assertSame( 4, $data['rating'] );
		$this->assertArrayNotHasKey( 'is_recommended', $data );
	}

	public function test_discards_an_out_of_range_rating() {
		foreach ( [ 0, 6, -3, 99 ] as $bogus ) {
			$data = FacebookReviews::map_review( $this->payload( [ 'rating' => $bogus, 'recommendation_type' => null ] ) );
			$this->assertSame( 0, $data['rating'], "rating {$bogus} should be discarded" );
		}
	}

	public function test_missing_reviewer_name_gets_a_readable_fallback() {
		$data = FacebookReviews::map_review( $this->payload( [ 'reviewer' => [ 'name' => '' ] ] ) );

		$this->assertSame( __( 'A Facebook user', 'notificationx' ), $data['username'] );
	}

	public function test_missing_avatar_and_profile_are_empty_not_broken() {
		$data = FacebookReviews::map_review( $this->payload( [ 'reviewer' => [ 'avatar' => null, 'url' => null ] ] ) );

		$this->assertSame( '', $data['profile_photo_url'] );
		$this->assertSame( '', $data['reviewer_url'] );
	}

	/** A bare thumbs-up is a normal Facebook review and must still be stored. */
	public function test_a_recommendation_with_no_text_is_kept() {
		$data = FacebookReviews::map_review( $this->payload( [ 'content' => '' ] ) );

		$this->assertNotEmpty( $data );
		$this->assertSame( '', $data['place_review'] );
		$this->assertTrue( $data['is_recommended'] );
	}

	/** ...and renders as a sentence rather than "Sam just reviewed ." */
	public function test_empty_review_text_falls_back_to_the_page_name_at_render_time() {
		$data     = FacebookReviews::map_review( $this->payload( [ 'content' => '' ] ) );
		$rendered = FacebookReviews::get_instance()->conversion_data( $data, [ 'themes' => 'facebook_reviews_review-comment' ] );

		$this->assertSame( 'Example Business', $rendered['place_review'] );
	}

	public function test_recommendation_label_is_built_at_render_time() {
		$extension = FacebookReviews::get_instance();

		$positive = $extension->conversion_data( FacebookReviews::map_review( $this->payload() ), [ 'themes' => '' ] );
		$negative = $extension->conversion_data( FacebookReviews::map_review( $this->payload( [ 'recommendation_type' => 'negative' ] ) ), [ 'themes' => '' ] );
		$starred  = $extension->conversion_data( FacebookReviews::map_review( $this->payload( [ 'rating' => 5, 'recommendation_type' => null ] ) ), [ 'themes' => '' ] );

		$this->assertSame( __( 'Recommends', 'notificationx' ), $positive['recommendation'] );
		$this->assertSame( __( "Doesn't recommend", 'notificationx' ), $negative['recommendation'] );
		$this->assertStringContainsString( '5', $starred['recommendation'] );
	}

	/**
	 * An undated review must not be presented as breaking news. Facebook often
	 * gives no date at all, and defaulting to "now" would render a years-old
	 * review as "a few seconds ago".
	 */
	public function test_an_undated_review_uses_the_collection_time_not_now() {
		$data = FacebookReviews::map_review( $this->payload( [
			'created_at' => null,
			'meta'       => [ 'collected_at' => '2026-08-20T00:00:00+00:00' ],
		] ) );

		$this->assertSame( strtotime( '2026-08-20T00:00:00+00:00' ), $data['timestamp'] );
	}

	public function test_an_approximate_date_keeps_the_sources_own_label() {
		$data = FacebookReviews::map_review( $this->payload( [
			'created_at' => null,
			'meta'       => [ 'relative_time' => '5 days ago', 'date_is_approximate' => true ],
		] ) );

		$this->assertSame( '5 days ago', $data['time_label'] );
		$this->assertTrue( $data['date_is_approximate'] );
	}

	public function test_a_review_with_no_permalink_links_to_the_pages_reviews_tab() {
		$data = FacebookReviews::map_review( $this->payload( [ 'review_url' => null ] ) );

		$this->assertSame( 'https://www.facebook.com/111222333/reviews', $data['url'] );
	}

	public function test_optional_extras_are_carried_when_present() {
		$data = FacebookReviews::map_review( $this->payload( [
			'meta' => [
				'tags'        => [ 'Service', 'Value' ],
				'photos'      => [ 'https://scontent.example/p1.jpg' ],
				'engagement'  => [ 'reactions' => 22, 'comments' => 4 ],
				'owner_reply' => [ 'text' => 'Thank you Jane!' ],
			],
		] ) );

		$this->assertSame( 'Service, Value', $data['tags'] );
		$this->assertSame( 'https://scontent.example/p1.jpg', $data['review_photo'] );
		$this->assertSame( 22, $data['likes'] );
		$this->assertSame( 4, $data['comments'] );
		$this->assertSame( 'Thank you Jane!', $data['owner_reply'] );
	}

	public function test_extras_are_absent_rather_than_empty_when_the_source_had_none() {
		$data = FacebookReviews::map_review( $this->payload() );

		foreach ( [ 'tags', 'review_photo', 'likes', 'comments', 'owner_reply' ] as $key ) {
			$this->assertArrayNotHasKey( $key, $data, "{$key} should be absent, so isset() means 'we have it'" );
		}
	}

	// -------------------------------------------------------------- hostile input

	/** Review text is attacker-controlled: it is written by a stranger on Facebook. */
	public function test_markup_in_review_content_is_stripped() {
		$data = FacebookReviews::map_review( $this->payload( [
			'content'  => "<script>alert('xss')</script> Nice place",
			'reviewer' => [ 'name' => '<img src=x onerror=alert(1)>' ],
		] ) );

		$this->assertStringNotContainsString( '<script', $data['place_review'] );
		$this->assertStringNotContainsString( 'onerror', $data['username'] );
	}

	public function test_non_http_urls_are_rejected() {
		$data = FacebookReviews::map_review( $this->payload( [
			'reviewer'   => [ 'avatar' => 'javascript:alert(1)', 'url' => 'data:text/html,x' ],
			'review_url' => 'javascript:alert(2)',
		] ) );

		$this->assertSame( '', $data['profile_photo_url'] );
		$this->assertSame( '', $data['reviewer_url'] );
		$this->assertStringStartsWith( 'https://www.facebook.com/', $data['url'] );
	}

	public function test_a_payload_without_identity_is_rejected() {
		$this->assertSame( [], FacebookReviews::map_review( $this->payload( [ 'review_id' => '' ] ) ) );
		$this->assertSame( [], FacebookReviews::map_review( $this->payload( [ 'page_id' => '' ] ) ) );
		$this->assertSame( [], FacebookReviews::map_review( 'not an array' ) );
	}

	// ------------------------------------------------------------------ identity

	/** The same review delivered twice must not become two notifications. */
	public function test_entry_key_is_stable_for_the_same_review() {
		$first  = FacebookReviews::entry_key( '111222333', 'story_1' );
		$second = FacebookReviews::entry_key( '111222333', 'story_1' );

		$this->assertSame( $first, $second );
		$this->assertNotSame( $first, FacebookReviews::entry_key( '111222333', 'story_2' ) );
		$this->assertNotSame( $first, FacebookReviews::entry_key( '999', 'story_1' ), 'the same story id on another Page is another review' );
	}

	// ------------------------------------------------- aggregate vs individual

	/**
	 * A campaign shows either the Page's aggregate rating or its individual
	 * reviews. Mixing them drops a nameless, wordless summary into a per-review
	 * rotation, where it renders as "  just reviewed Example Business".
	 */
	public function test_only_a_rating_count_template_wants_the_aggregate_entry() {
		$this->assertTrue( FacebookReviews::wants_summary( [ 'notification-template' => [ 'first_param' => 'tag_rated' ] ] ) );
		$this->assertFalse( FacebookReviews::wants_summary( [ 'notification-template' => [ 'first_param' => 'tag_username' ] ] ) );
	}

	public function test_a_campaign_with_no_template_yet_falls_back_to_its_theme() {
		$this->assertTrue( FacebookReviews::wants_summary( [ 'themes' => 'facebook_reviews_total-rated' ] ) );
		$this->assertFalse( FacebookReviews::wants_summary( [ 'themes' => 'facebook_reviews_review-comment' ] ) );
		$this->assertFalse( FacebookReviews::wants_summary( [] ) );
	}

	// ------------------------------------------------------------------ webhook

	public function test_webhook_signature_round_trip() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 'site-token-xyz' ], false );

		$body      = '{"review_id":"story_1"}';
		$timestamp = (string) time();
		$delivery  = 'facebook:111:story_1:1';
		$signature = 'sha256=' . hash_hmac( 'sha256', $timestamp . '.' . $delivery . '.' . $body, 'site-token-xyz' );

		$this->assertTrue( FacebookReviewsManaged::verify_webhook( $body, $timestamp, $delivery, $signature ) );
	}

	public function test_webhook_rejects_a_forged_signature() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 'site-token-xyz' ], false );

		$this->assertSame(
			'invalid_signature',
			FacebookReviewsManaged::verify_webhook( '{}', (string) time(), 'd1', 'sha256=' . str_repeat( '0', 64 ) )
		);
	}

	/** A captured request must not stay replayable forever. */
	public function test_webhook_rejects_a_stale_timestamp() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 'site-token-xyz' ], false );

		$body      = '{}';
		$timestamp = (string) ( time() - ( FacebookReviewsManaged::WEBHOOK_SKEW + 60 ) );
		$delivery  = 'd1';
		$signature = 'sha256=' . hash_hmac( 'sha256', $timestamp . '.' . $delivery . '.' . $body, 'site-token-xyz' );

		$this->assertSame( 'expired_timestamp', FacebookReviewsManaged::verify_webhook( $body, $timestamp, $delivery, $signature ) );
	}

	public function test_webhook_is_refused_when_the_site_is_not_connected() {
		delete_option( FacebookReviewsManaged::OPT_AUTH );

		$this->assertSame( 'not_connected', FacebookReviewsManaged::verify_webhook( '{}', (string) time(), 'd1', 'sha256=x' ) );
	}

	// --------------------------------------------------------------- attestation

	/**
	 * Owner-attested connect exists so a deployment whose API has no Meta app
	 * can still ship — App Review and Business Verification take weeks and can
	 * be refused. The plugin's job is to ask the API which modes it offers and
	 * show those, rather than advertising a login that will 404.
	 */
	public function test_connect_modes_come_from_the_api() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 't', 'site_id' => 's' ], false );
		delete_transient( FacebookReviewsManaged::CACHE_STATUS );
		$this->stub_api( [ 'connect_modes' => [ 'attested' ] ] );

		$this->assertSame( [ 'attested' ], FacebookReviewsManaged::connect_modes() );
	}

	/** An API too old to report its modes only ever supported the Facebook login. */
	public function test_connect_modes_fall_back_to_oauth_for_an_older_api() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 't', 'site_id' => 's' ], false );
		delete_transient( FacebookReviewsManaged::CACHE_STATUS );
		$this->stub_api( [ 'connections' => [] ] );

		$this->assertSame( [ 'oauth' ], FacebookReviewsManaged::connect_modes() );
	}

	public function test_unknown_connect_modes_are_discarded() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 't', 'site_id' => 's' ], false );
		delete_transient( FacebookReviewsManaged::CACHE_STATUS );
		$this->stub_api( [ 'connect_modes' => [ 'attested', 'telepathy', '<script>' ] ] );

		$this->assertSame( [ 'attested' ], FacebookReviewsManaged::connect_modes() );
	}

	/**
	 * The API's rejection message IS the instruction — which code to add, or
	 * which domain to set. Replacing it with something generic would leave the
	 * customer with no way forward.
	 */
	public function test_a_failed_attestation_passes_the_apis_instructions_through() {
		update_option( FacebookReviewsManaged::OPT_AUTH, [ 'token' => 't', 'site_id' => 's' ], false );
		$this->stub_api(
			[ 'error' => 'attestation_failed', 'message' => 'Add the code nx-verify-abc anywhere public on the Page.' ],
			422
		);

		$result = FacebookReviewsManaged::attest_verify( 'https://www.facebook.com/x' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'attestation_failed', $result['error'] );
		$this->assertStringContainsString( 'nx-verify-abc', $result['message'] );
	}

	/** Canned API response for the next outbound request. */
	private function stub_api( $body, $status = 200 ) {
		add_filter(
			'pre_http_request',
			static function () use ( $body, $status ) {
				return [
					'headers'  => [],
					'response' => [ 'code' => $status, 'message' => 'OK' ],
					'body'     => wp_json_encode( $body ),
				];
			},
			10,
			3
		);
	}

	// ------------------------------------------------------------------ endpoint

	/** The Bearer token rides every request, so plain http is only for local dev. */
	public function test_endpoint_refuses_plain_http_for_a_public_host() {
		$filter = static fn() => 'http://api.example.com/facebook-reviews/v1';
		add_filter( 'nx_facebook_reviews_managed_endpoint', $filter );
		$endpoint = FacebookReviewsManaged::endpoint();
		remove_filter( 'nx_facebook_reviews_managed_endpoint', $filter );

		$this->assertSame( FacebookReviewsManaged::DEFAULT_ENDPOINT, $endpoint );
	}

	public function test_endpoint_allows_plain_http_for_local_development() {
		$filter = static fn() => 'http://api.notificationx.test/facebook-reviews/v1';
		add_filter( 'nx_facebook_reviews_managed_endpoint', $filter );
		$endpoint = FacebookReviewsManaged::endpoint();
		remove_filter( 'nx_facebook_reviews_managed_endpoint', $filter );

		$this->assertSame( 'http://api.notificationx.test/facebook-reviews/v1', $endpoint );
	}
}
