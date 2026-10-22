<?php
/**
 * Regression tests for the Contributor+ stored XSS in the Countdown block's
 * `expiryTitle` / `expiryText` attributes.
 *
 * The render callback used to emit the expiry message as
 * `data-expiry-title="<?php esc_attr( wp_kses_post( ... ) ) ?>"`. kses leaves an
 * entity-encoded payload such as `&lt;img src=x onerror=...&gt;` alone (it has
 * no literal `<`), and esc_attr() does not double-encode it. The browser then
 * decodes the entity while parsing the attribute, and frontend.js wrote the
 * decoded value into `innerHTML`, so the `<img onerror>` ran for every visitor.
 *
 * The fix renders the kses'd message server-side into an inert <template> that
 * frontend.js clones as DOM nodes. The same change applies to the Elementor
 * widget (includes/Extensions/Elementor/CountdownWidget.php +
 * assets/public/js/nx-countdown.js).
 *
 * @package Notificationx
 */

use NotificationX\Blocks\Blocks;

class Test_Security_Countdown_Expiry extends WP_UnitTestCase {

	const ENCODED_PAYLOAD = 'ZZ&lt;img src=x onerror=alert(1)&gt;ZZ';
	const LITERAL_PAYLOAD = '<strong>Bye</strong><img src=x onerror=alert(2)>';

	protected function render( $attributes ) {
		return Blocks::get_instance()->countdown_render_callback(
			array_merge(
				array(
					'blockId'    => 'nx-countdown-test',
					'dueTime'    => '2020-01-01T00:00',
					'expireType' => 'text',
				),
				$attributes
			)
		);
	}

	public function test_expiry_message_is_not_passed_through_data_attributes() {
		$html = $this->render(
			array(
				'expiryTitle' => self::ENCODED_PAYLOAD,
				'expiryText'  => self::ENCODED_PAYLOAD,
			)
		);

		$this->assertStringNotContainsString( 'data-expiry-title', $html );
		$this->assertStringNotContainsString( 'data-expiry-text', $html );
	}

	public function test_entity_encoded_payload_stays_text_in_template() {
		$html = $this->render( array( 'expiryTitle' => self::ENCODED_PAYLOAD ) );

		$this->assertStringContainsString( '<template class="nx-countdown-expiry">', $html );
		$this->assertStringContainsString(
			'<h4 class="nx-expiry-title">ZZ&lt;img src=x onerror=alert(1)&gt;ZZ</h4>',
			$html
		);
	}

	public function test_literal_markup_is_ksesd_and_formatting_kept() {
		$html = $this->render( array( 'expiryText' => self::LITERAL_PAYLOAD ) );

		$this->assertStringContainsString( '<strong>Bye</strong>', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
	}

	public function test_no_template_unless_expire_type_is_text() {
		$html = $this->render(
			array(
				'expireType'  => 'none',
				'expiryTitle' => self::ENCODED_PAYLOAD,
			)
		);

		$this->assertStringNotContainsString( '<template', $html );
		$this->assertStringNotContainsString( 'ZZ', $html );
	}
}
