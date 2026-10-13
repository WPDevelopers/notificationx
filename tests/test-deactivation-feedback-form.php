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
 *  3. Submitting has no loading state: no spinner markup, no fade-out, and a
 *     repeat click does not send a second request.
 *  4. Lookups are scoped to the NotificationX wrapper, because other
 *     WPDeveloper plugins print the same `#wpinsights-goodbye-form` id.
 *  5. The look (8px button radius, purple radios) matches the Better Payment
 *     feedback form; the submit button uses the NotificationX brand #6A4BFF.
 *  6. Admin::plugin_usage_insights() turns the form off while NotificationX
 *     Pro is active. Tracking starts on Pro sites too since 3.2.10, and the
 *     form leaked onto them with it.
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

	public function test_submit_has_no_loading_state() {
		$script = $this->script();
		$css    = $this->style();

		$this->assertStringNotContainsString( 'deactivating-spinner', $script );
		$this->assertStringNotContainsString( 'Submitting form', $script );
		$this->assertStringNotContainsString( 'deactivating-spinner', $css );
		$this->assertStringNotContainsString( 'fadeOut', $this->submit_handler( $script ), 'Submitting must not hide the form.' );
	}

	public function test_repeat_submit_clicks_are_ignored() {
		$handler = $this->submit_handler( $this->script() );

		$this->assertStringContainsString( "if ( $(this).data('submitting') ) {", $handler );
		$this->assertStringContainsString( "$(this).data('submitting', true);", $handler );
		$this->assertLessThan( strpos( $handler, '$.post(' ), strpos( $handler, "$(this).data('submitting', true);" ), 'The guard must be set before the request is sent.' );
	}

	public function test_look_matches_better_payment_form() {
		$css = $this->style();

		$submit = $this->rule( $css, '.wpinsights-goodbye-form-footer .wpinsights-submit-btn' );
		$this->assertMatchesRegularExpression( '/background-color:\s*#6A4BFF/i', $submit, 'Submit button uses the NotificationX brand colour.' );
		$this->assertMatchesRegularExpression( '/(?<!-)border-radius:\s*8px/', $submit );
		$this->assertMatchesRegularExpression( '/padding:\s*12px 18px/', $submit );

		$radio = $this->rule( $css, '#wpinsights-goodbye-options input[type=radio]' );
		$this->assertMatchesRegularExpression( '/border-color:\s*#6B59EE/i', $radio );

		$checked = $this->rule( $css, '#wpinsights-goodbye-options input[type=radio]:checked::before' );
		$this->assertMatchesRegularExpression( '/background-color:\s*#6B59EE/i', $checked );

		$deactivate = $this->rule( $css, '.wpinsights-goodbye-form-footer .wpinsights-deactivate-btn' );
		$this->assertMatchesRegularExpression( '/color:\s*#a4afb7/i', $deactivate );
	}

	/**
	 * Body of the "Submit and Deactivate" click handler.
	 *
	 * @param string $script
	 * @return string
	 */
	protected function submit_handler( $script ) {
		$start = strpos( $script, "$('#wpinsights-submit-form-notificationx').on('click'" );
		$end   = strpos( $script, '$form.find(\'#wpinsights-goodbye-options > ul\')', $start );
		$this->assertNotFalse( $start, 'Submit handler not found.' );
		$this->assertNotFalse( $end );
		return substr( $script, $start, $end - $start );
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

	/**
	 * Build the insights instance through Admin, the way the plugin does.
	 *
	 * @return PluginInsights
	 */
	protected function admin_insights() {
		$singleton = new ReflectionProperty( PluginInsights::class, '_instance' );
		$singleton->setAccessible( true );
		$singleton->setValue( null, null );

		$admin = \NotificationX\Admin\Admin::get_instance();
		$cached = new ReflectionProperty( $admin, 'insights' );
		$cached->setAccessible( true );
		$cached->setValue( $admin, null );

		return $admin->plugin_usage_insights();
	}

	public function test_admin_enables_form_without_pro() {
		$this->assertFalse( \NotificationX\NotificationX::is_pro(), 'This case needs Pro to be absent.' );

		$this->insights = $this->admin_insights();
		$links          = $this->action_links();

		$this->assertStringContainsString( 'id="wpinsights-goodbye-link-notificationx"', $links['deactivate'] );
	}

	/**
	 * Loading a stand-in for the Pro main class cannot be undone, so this case
	 * runs in its own process.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_admin_disables_form_when_pro_active() {
		eval( 'namespace NotificationXPro; class NotificationX {}' );
		$this->assertTrue( \NotificationX\NotificationX::is_pro() );

		$this->insights = $this->admin_insights();
		$links          = $this->action_links();

		$this->assertSame( '<a href="https://example.test/deactivate">Deactivate</a>', $links['deactivate'], 'No feedback form while Pro is active.' );
		$this->assertNotFalse( has_action( 'put_do_weekly_action', [ $this->insights, 'do_tracking' ] ), 'Tracking must still run on Pro sites.' );
	}
}
