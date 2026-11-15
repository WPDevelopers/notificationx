<?php
/**
 * Regression tests for translating cron schedule labels too early.
 *
 * Another plugin can call wp_schedule_event() before `after_setup_theme`
 * (WooCommerce's bundled Jetpack connection does it on wp-cron), which runs
 * every `cron_schedules` callback. Translating a label then trips WordPress'
 * "Translation loading was triggered too early" notice.
 *
 * The fix: labels stay in English until `after_setup_theme` has started.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Cron;

class Test_Cron_Translation extends WP_UnitTestCase {

	/**
	 * Strings translated in the notificationx domain during a test.
	 *
	 * @var array
	 */
	protected $translated = array();

	/**
	 * `after_setup_theme` run count, restored after each test.
	 *
	 * @var int|null
	 */
	protected $setup_theme_runs;

	public function set_up() {
		parent::set_up();
		global $wp_actions;
		$this->setup_theme_runs = isset( $wp_actions['after_setup_theme'] ) ? $wp_actions['after_setup_theme'] : null;
		$this->translated       = array();
		add_filter( 'gettext_notificationx', array( $this, 'record_translation' ), 10, 2 );
	}

	public function tear_down() {
		global $wp_actions;
		$wp_actions['after_setup_theme'] = $this->setup_theme_runs;
		parent::tear_down();
	}

	public function record_translation( $translation, $text ) {
		$this->translated[] = $text;
		return $translation;
	}

	protected function before_setup_theme() {
		global $wp_actions;
		unset( $wp_actions['after_setup_theme'] );
	}

	public function test_labels_cannot_be_translated_before_after_setup_theme() {
		$this->before_setup_theme();
		$this->assertFalse( Cron::can_translate_labels() );
	}

	public function test_labels_can_be_translated_during_after_setup_theme() {
		$this->before_setup_theme();
		$during = null;
		$check  = function () use ( &$during ) {
			$during = Cron::can_translate_labels();
		};
		add_action( 'after_setup_theme', $check );
		do_action( 'after_setup_theme' );
		remove_action( 'after_setup_theme', $check );

		$this->assertTrue( $during );
	}

	public function test_labels_can_be_translated_after_after_setup_theme() {
		$this->assertTrue( Cron::can_translate_labels() );
	}

	public function test_early_cron_schedules_translate_nothing() {
		$this->before_setup_theme();

		$schedules = wp_get_schedules();

		$this->assertSame( array(), $this->translated );
		$this->assertSame( 'Once Weekly', $schedules['weekly']['display'] );
		$this->assertSame( 'Once Daily', $schedules['daily']['display'] );
	}

	public function test_cron_schedules_translate_once_setup_is_done() {
		wp_get_schedules();

		$this->assertContains( 'Once Daily', $this->translated );
	}

	public function test_early_cron_schedules_keep_their_intervals() {
		$later = wp_get_schedules();
		$this->before_setup_theme();
		$early = wp_get_schedules();

		$this->assertSame( array_keys( $later ), array_keys( $early ) );
		$this->assertSame( wp_list_pluck( $later, 'interval' ), wp_list_pluck( $early, 'interval' ) );
	}
}
