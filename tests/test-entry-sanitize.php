<?php
/**
 * Regression tests for Entries::sanitize_entry_data() (issue #203).
 *
 * The sanitizer ran sanitize_text_field() on every string, which removes
 * percent-encoded octets. A non-Latin permalink such as
 * `/product/%ce%bc%cf%80…-cr2025/` was stored as `/product/--5-cr2025/`, so
 * the notification linked to a 404. Whole http(s) URLs now go through
 * esc_url_raw(); everything else stays on sanitize_text_field().
 *
 * The sanitizer is also the fix for a stored XSS (Patchstack, NX Pro <= 3.1.3),
 * so the payload tests below must keep passing.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Entries;

class Test_Entry_Sanitize extends WP_UnitTestCase {

	const GREEK_LINK = 'https://example.org/product/%ce%bc%cf%80%ce%b1%cf%84%ce%b1%cf%81%ce%b9%ce%b5%cf%82-%ce%bb%ce%b9%ce%b8%ce%b9%ce%bf%cf%85-5%cf%84%ce%bc%cf%87-cr2025/';

	private function sanitize( $data ) {
		return Entries::get_instance()->sanitize_entry_data( $data );
	}

	/* ---------------------------------------------------------------------
	 * URLs are preserved
	 * ------------------------------------------------------------------ */

	public function test_keeps_percent_encoded_link() {
		$this->assertSame( self::GREEK_LINK, $this->sanitize( array( 'link' => self::GREEK_LINK ) )['link'] );
	}

	public function test_keeps_percent_encoded_image_url() {
		$url = 'https://example.org/wp-content/uploads/%ce%b1.jpg';
		$this->assertSame( $url, $this->sanitize( array( 'image' => $url ) )['image'] );
	}

	public function test_keeps_raw_utf8_url() {
		$url = 'https://example.org/product/μπαταριες/';
		$this->assertSame( $url, $this->sanitize( array( 'link' => $url ) )['link'] );
	}

	public function test_keeps_latin_url_with_query_string() {
		$url = 'https://example.org/product/hoodie/?a=1&b=2#reviews';
		$this->assertSame( $url, $this->sanitize( array( 'link' => $url ) )['link'] );
	}

	public function test_keeps_url_in_nested_array() {
		$data = $this->sanitize( array( 'items' => array( array( 'link' => self::GREEK_LINK ) ) ) );
		$this->assertSame( self::GREEK_LINK, $data['items'][0]['link'] );
	}

	public function test_keeps_url_passed_as_plain_string() {
		$this->assertSame( self::GREEK_LINK, $this->sanitize( self::GREEK_LINK ) );
	}

	public function test_trims_url() {
		$this->assertSame( self::GREEK_LINK, $this->sanitize( '  ' . self::GREEK_LINK . "\n" ) );
	}

	/* ---------------------------------------------------------------------
	 * Text is unchanged
	 * ------------------------------------------------------------------ */

	public function test_keeps_non_latin_text() {
		$data = $this->sanitize(
			array(
				'title' => 'Μπαταριες λιθιου',
				'city'  => 'Αθήνα',
			)
		);
		$this->assertSame( 'Μπαταριες λιθιου', $data['title'] );
		$this->assertSame( 'Αθήνα', $data['city'] );
	}

	public function test_keeps_non_string_scalars() {
		$data = $this->sanitize(
			array(
				'id'        => 42,
				'timestamp' => 1767225600,
				'enabled'   => true,
			)
		);
		$this->assertSame( 42, $data['id'] );
		$this->assertSame( 1767225600, $data['timestamp'] );
		$this->assertTrue( $data['enabled'] );
	}

	/**
	 * Text that contains a URL but is not one stays on the text sanitizer.
	 */
	public function test_sentence_with_url_uses_text_sanitizer() {
		$this->assertSame(
			'See https://example.org/',
			$this->sanitize( 'See https://example.org/%ce%b1' )
		);
	}

	/* ---------------------------------------------------------------------
	 * XSS payloads (issue #203 security table)
	 * ------------------------------------------------------------------ */

	public function test_strips_markup() {
		$this->assertSame( '', $this->sanitize( '<svg onload=alert(1)>' ) );
	}

	public function test_strips_markup_in_array_wrapped_value() {
		$data = $this->sanitize( array( 'name' => array( '<img src=x onerror=alert(1)>' ) ) );
		$this->assertSame( '', $data['name'][0] );
	}

	public function test_javascript_url_uses_text_sanitizer() {
		$payload = 'javascript:alert(1)';
		$this->assertSame( sanitize_text_field( $payload ), $this->sanitize( $payload ) );
	}

	public function test_data_url_uses_text_sanitizer() {
		$this->assertSame(
			'data:text/html,',
			$this->sanitize( 'data:text/html,<script>alert(1)</script>' )
		);
	}

	public function test_url_loses_angle_brackets() {
		$out = $this->sanitize( 'https://x.test/<img/src=x/onerror=alert(1)>' );
		$this->assertStringNotContainsString( '<', $out );
		$this->assertStringNotContainsString( '>', $out );
	}

	public function test_url_loses_double_quotes() {
		$out = $this->sanitize( 'https://x.test/"onmouseover="alert(1)' );
		$this->assertStringNotContainsString( '"', $out );
	}

	public function test_url_loses_encoded_line_breaks() {
		$this->assertSame( 'https://x.test/ab', $this->sanitize( 'https://x.test/a%0d%0Ab' ) );
	}

	/**
	 * Encoded markup stays encoded; nothing in the frontend decodes it.
	 */
	public function test_url_keeps_encoded_brackets_encoded() {
		$url = 'https://x.test/%3Cscript%3E';
		$this->assertSame( $url, $this->sanitize( $url ) );
	}

	public function test_invalid_utf8_url_is_rejected() {
		$this->assertSame( '', $this->sanitize( "https://x.test/\xC3\x28" ) );
	}
}
