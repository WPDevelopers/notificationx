<?php
/**
 * Deactivation feedback form ("Sorry to see you go") tests for NotificationX.
 *
 * PluginInsights prints the form's CSS and jQuery inline on plugins.php. These
 * tests pin the parts that broke the form on the Plugins screen:
 *
 *  1. The open modal stacks above the admin menu (z-index 9990) and the admin
 *     bar (z-index 99999), so the overlay dims and blocks the whole screen.
 *  2. Every class the footer links use has a style rule ("Just Deactivate"
 *     printed `wpsp-put-deactivate-btn`, which had none).
 *  3. The submit handler hides the form body and shows the spinner through
 *     elements that exist (it used `#wpinsights-goodbye-form-notificationx`,
 *     an id the markup never had).
 *  4. Lookups are scoped to the NotificationX wrapper, because other
 *     WPDeveloper plugins print the same `#wpinsights-goodbye-form` id.
 *
 * See docs/features/deactivation-feedback-form.md.
 *
 * @package Notificationx
 */

use NotificationX\Admin\PluginInsights;

/**
 * @group insights
 * @group deactivation-form
 */
class Test_Deactivation_Feedback_Form extends WP_UnitTestCase {

	/**
	 * z-index of #wpadminbar in wp-includes/css/admin-bar.css.
	 */
	const ADMIN_BAR_Z_INDEX = 99999;

	/**
	 * @var PluginInsights
	 */
	protected $insights;

	public function setUp(): void {
		parent::setUp();

		$property = new ReflectionProperty( PluginInsights::class, '_instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );

		$this->insights = PluginInsights::get_instance(
			NOTIFICATIONX_FILE,
			[
				'opt_in'       => true,
				'goodbye_form' => true,
				'item_id'      => '6ba8d30bc0beaddb2540',
			]
		);
	}

	protected function style() {
		ob_start();
		$this->insights->deactivate_reasons_form_style();
		return ob_get_clean();
	}

	protected function script() {
		ob_start();
		$this->insights->deactivate_reasons_form_script();
		return ob_get_clean();
	}

	protected function action_links() {
		return $this->insights->deactivate_action_links(
			[
				'settings'   => '<a href="https://example.test/settings">Settings</a>',
				'deactivate' => '<a href="https://example.test/deactivate">Deactivate</a>',
			]
		);
	}

	/**
	 * Declarations of the first CSS rule whose selector list ends with $selector.
	 *
	 * @param string $css
	 * @param string $selector
	 * @return string
	 */
	protected function rule( $css, $selector ) {
		$pattern = '/(?:^|})\s*([^{}]*?' . preg_quote( $selector, '/' ) . ')\s*{([^}]*)}/s';
		$this->assertSame( 1, preg_match( $pattern, $css, $match ), "No CSS rule for `$selector`." );
		return $match[2];
	}

	public function test_open_modal_stacks_above_admin_bar_and_menu() {
		$rule = $this->rule( $this->style(), '.wpinsights-form-active-notificationx .wpinsights-goodbye-form-wrapper-notificationx' );

		$this->assertMatchesRegularExpression( '/position:\s*fixed/', $rule );
		$this->assertSame( 1, preg_match( '/z-index:\s*(\d+)/', $rule, $z ), 'The open wrapper must set a z-index.' );
		$this->assertGreaterThan( self::ADMIN_BAR_Z_INDEX, (int) $z[1], 'The modal must sit above #wpadminbar and #adminmenuwrap.' );
	}

	public function test_overlay_and_dialog_stack_inside_wrapper() {
		$css     = $this->style();
		$overlay = $this->rule( $css, '.wpinsights-goodbye-form-wrapper-notificationx .wpinsights-goodbye-form-bg' );
		$dialog  = $this->rule( $css, '.wpinsights-form-active-notificationx .wpinsights-goodbye-form-wrapper-notificationx .wpinsights-goodbye-form' );

		$this->assertSame( 1, preg_match( '/z-index:\s*(\d+)/', $overlay, $overlay_z ) );
		$this->assertSame( 1, preg_match( '/z-index:\s*(\d+)/', $dialog, $dialog_z ) );
		$this->assertGreaterThan( (int) $overlay_z[1], (int) $dialog_z[1], 'The dialog must paint above its overlay.' );
	}

	public function test_dialog_fits_small_screens() {
		$dialog = $this->rule( $this->style(), '.wpinsights-form-active-notificationx .wpinsights-goodbye-form-wrapper-notificationx .wpinsights-goodbye-form' );

		$this->assertMatchesRegularExpression( '/max-height:/', $dialog );
		$this->assertMatchesRegularExpression( '/overflow-y:\s*auto/', $dialog );
	}

	public function test_page_does_not_scroll_behind_open_modal() {
		$rule = $this->rule( $this->style(), '.wpinsights-form-active-notificationx' );

		$this->assertMatchesRegularExpression( '/overflow:\s*hidden/', $rule );
	}

	public function test_every_footer_link_class_has_a_style_rule() {
		$script = $this->script();
		$css    = $this->style();

		$this->assertStringNotContainsString( 'wpsp-put-deactivate-btn', $script, 'Unstyled legacy class is back.' );
		$this->assertStringContainsString( 'class="wpinsights-submit-btn"', $script );
		$this->assertStringContainsString( 'class="wpinsights-deactivate-btn"', $script );

		$this->rule( $css, '.wpinsights-goodbye-form-footer .wpinsights-submit-btn' );
		$this->rule( $css, '.wpinsights-goodbye-form-footer .wpinsights-deactivate-btn' );
	}

	public function test_submit_handler_targets_elements_that_exist() {
		$script = $this->script();

		$this->assertStringNotContainsString( '#wpinsights-goodbye-form-notificationx', $script, 'That id is not in the markup, so the spinner never showed.' );

		foreach ( [ 'wpinsights-goodbye-form-body', 'wpinsights-goodbye-form-footer', 'deactivating-spinner' ] as $class ) {
			$this->assertStringContainsString( 'class="' . $class . '"', $script, "The form markup must contain .$class." );
			$this->assertMatchesRegularExpression( '/\$form\.find\([^)]*\.' . preg_quote( $class, '/' ) . '/', $script, ".$class must be looked up inside the NotificationX form." );
		}
	}

	public function test_lookups_are_scoped_to_notificationx_wrapper() {
		$script = $this->script();

		$this->assertStringContainsString( 'var $wrapper = $(".wpinsights-goodbye-form-wrapper-notificationx")', $script );
		$this->assertStringNotContainsString( "$('.wpinsights-goodbye-form-bg')", $script, 'Unscoped overlay lookup also matches other plugins\' forms.' );
		$this->assertStringNotContainsString( '$("#wpinsights-goodbye-form")', $script, 'Unscoped id lookup can return another plugin\'s form.' );
		$this->assertStringNotContainsString( "$('#wpinsights-goodbye-options", $script );
	}

	public function test_overlay_click_handler_is_bound_once() {
		$script = $this->script();

		$link_handler = strpos( $script, '$("#wpinsights-goodbye-link-notificationx").on("click"' );
		$bg_handler   = strpos( $script, ".find('.wpinsights-goodbye-form-bg').on('click'" );

		$this->assertNotFalse( $link_handler );
		$this->assertNotFalse( $bg_handler );
		$this->assertLessThan( $link_handler, $bg_handler, 'Binding inside the link handler adds one more handler on every open.' );
	}

	public function test_submit_redirects_even_when_request_fails() {
		$this->assertStringContainsString( '$.post( ajaxurl, data ).always(', $this->script() );
	}

	public function test_action_link_carries_form_wrapper() {
		$links = $this->action_links();

		$this->assertStringContainsString( '<div class="wpinsights-goodbye-form-wrapper-notificationx">', $links['deactivate'] );
		$this->assertStringContainsString( '<div class="wpinsights-goodbye-form-bg"></div>', $links['deactivate'] );
		$this->assertStringContainsString( 'class="wpinsights-goodbye-form"', $links['deactivate'] );
		$this->assertStringContainsString( 'id="wpinsights-goodbye-link-notificationx"', $links['deactivate'] );
		$this->assertStringContainsString( 'href="https://example.test/deactivate"', $links['deactivate'] );
		$this->assertSame( '<a href="https://example.test/settings">Settings</a>', $links['settings'] );
	}

	public function test_action_link_untouched_when_form_disabled() {
		$property = new ReflectionProperty( PluginInsights::class, '_instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
		$this->insights = PluginInsights::get_instance( NOTIFICATIONX_FILE, [ 'goodbye_form' => false ] );

		$links = $this->action_links();

		$this->assertSame( '<a href="https://example.test/deactivate">Deactivate</a>', $links['deactivate'] );
	}

	public function test_reason_labels_are_escaped() {
		add_filter(
			'wpins_form_text_notificationx',
			function ( $form ) {
				$form['options'][] = '<img src=x onerror=alert(1)>';
				return $form;
			}
		);

		$script = $this->script();

		$this->assertStringNotContainsString( '<img src=x', $script );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $script );
	}
}
