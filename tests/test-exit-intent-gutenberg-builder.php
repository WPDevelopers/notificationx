<?php
/**
 * Exit Intent "Build With Gutenberg" in the builder: the Custom tab fields,
 * one builder at a time, hidden content, the Build With AI note, saving.
 *
 * The builder rules are pinned in tests/js/fixtures/exit-intent-builder-rules.json,
 * which tests/js/admin/exit-intent-builder-rules.test.js evaluates with
 * quickbuilder's own rule engine for each state (nothing linked, Elementor,
 * Gutenberg, no block editor, no Elementor). To rewrite the fixture after an
 * intended change: NX_WRITE_EXIT_INTENT_FIXTURE=1 and run this class.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\Core\QuickBuild;
use NotificationX\Extensions\ExitIntent\ExitIntentNotification;
use NotificationX\Extensions\ExitIntent\GutenbergDesign;
use NotificationX\NotificationX;

class Test_Exit_Intent_Gutenberg_Builder extends WP_UnitTestCase {

	const FIXTURE = '/js/fixtures/exit-intent-builder-rules.json';

	/** Fields whose rules the JS test evaluates, by the section that holds them. */
	const PINNED = array(
		'exit_intent_custom_tab' => array(
			'elementor_edit_link',
			'nx-exit-intent_with_elementor-remove',
			'nx-exit-intent_with_elementor',
			'nx-exit-intent_with_elementor_install',
			'gutenberg_edit_link',
			'nx-exit-intent_gutenberg_no_edit',
			'nx-exit-intent_with_gutenberg-remove',
			'nx-exit-intent_with_gutenberg',
		),
		'exit_intent_ai_tab'     => array(
			'exit_intent_ai_tab_fields',
			'exit_intent_ai_gutenberg_note',
		),
	);

	public function set_up() {
		parent::set_up();
		delete_transient( 'nx_builder_fields' );
	}

	public function tear_down() {
		delete_transient( 'nx_builder_fields' );
		remove_all_filters( 'use_block_editor_for_post_type' );
		parent::tear_down();
	}

	/**
	 * Every field of this name in the builder, with its enclosing section.
	 */
	protected function find_fields( $fields, $name, $section = '', $found = array() ) {
		if ( ! is_array( $fields ) ) {
			return $found;
		}
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$is_section = isset( $field['type'] ) && 'section' === $field['type'];
			if ( isset( $field['name'] ) && $name === $field['name'] && ! $is_section ) {
				$found[] = array( 'section' => $section, 'field' => $field );
				continue;
			}
			$inner = $is_section && isset( $field['name'] ) ? $field['name'] : $section;
			foreach ( array( 'fields', 'tabs' ) as $child ) {
				if ( isset( $field[ $child ] ) ) {
					$found = $this->find_fields( $field[ $child ], $name, $inner, $found );
				}
			}
		}
		return $found;
	}

	protected function field_in( $name, $section ) {
		$tabs = NotificationX::get_instance()->get_tab();
		foreach ( $this->find_fields( $tabs['tabs'], $name ) as $hit ) {
			if ( $section === $hit['section'] ) {
				return $hit['field'];
			}
		}
		$this->fail( "No $name field in the $section section." );
	}

	/** Rules as the builder receives them (JSON). */
	protected function rules_of( $field ) {
		return json_decode( wp_json_encode( isset( $field['rules'] ) ? $field['rules'] : null ), true );
	}

	protected function current_rules() {
		$rules = array();
		foreach ( self::PINNED as $section => $names ) {
			foreach ( $names as $name ) {
				$rules[ $section ][ $name ] = $this->rules_of( $this->field_in( $name, $section ) );
			}
		}
		// A content section and the Content step, through Elementor's filter then
		// the block design's (the order of their priorities, 1001 then 1002).
		$exit    = ExitIntentNotification::get_instance();
		$blocks  = GutenbergDesign::get_instance();
		$content = $blocks->hide_content_sections( $exit->suppress_when_elementor( array( 'exit_intent_theme_one_section' => array( 'name' => 'exit_intent_theme_one_section', 'type' => 'section' ) ) ) );
		$rules['content']['exit_intent_theme_one_section'] = $this->rules_of( $content['exit_intent_theme_one_section'] );
		$steps = $blocks->hide_content_tab( $exit->hide_content_tab_for_elementor( array( 'content_tab' => array( 'id' => 'content_tab' ) ) ) );
		$rules['content']['content_tab'] = $this->rules_of( $steps['content_tab'] );
		return $rules;
	}

	public function test_rules_match_the_shared_fixture() {
		$current = $this->current_rules();
		$file    = dirname( __FILE__ ) . self::FIXTURE;
		if ( getenv( 'NX_WRITE_EXIT_INTENT_FIXTURE' ) ) {
			$current = array( '_comment' => 'Exit Intent Custom-tab, Build With AI and content rules as the PHP builder sends them. tests/test-exit-intent-gutenberg-builder.php checks the PHP fields still produce exactly these; tests/js/admin/exit-intent-builder-rules.test.js evaluates them with quickbuilder\'s rule engine.' ) + $current;
			file_put_contents( $file, wp_json_encode( $current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture writer.
			$this->markTestSkipped( 'Fixture written.' );
		}
		$fixture = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local test fixture.
		unset( $fixture['_comment'] );
		$this->assertSame( wp_json_encode( $fixture ), wp_json_encode( $current ) );
	}

	public function test_gutenberg_controls_sit_in_the_custom_tab() {
		$edit = $this->field_in( 'gutenberg_edit_link', 'exit_intent_custom_tab' );
		$this->assertSame( 'button', $edit['type'] );
		$this->assertSame( -1, $edit['href'] );
		$this->assertSame( '_blank', $edit['target'] );

		$modal = $this->field_in( 'nx-exit-intent_with_gutenberg', 'exit_intent_custom_tab' );
		$this->assertSame( 'Build With Gutenberg', $modal['button']['text'] );
		$this->assertSame( 'Choose your design', $modal['body']['header'] );
		$import = $modal['confirm_button']['fields'][0];
		$this->assertSame( '/notificationx/v1/exit-intent/gutenberg/import', $import['ajax']['api'] );
		$this->assertSame( array( 'theme_id' => '@gutenberg_exit_theme' ), $import['ajax']['data'] );
		$this->assertArrayNotHasKey( 'hideSwal', $import['ajax'], 'A failed import must show its message.' );
		$this->assertSame( 'display_tab', $modal['confirm_button']['fields'][1]['trigger'][0]['action']['config.active'] );

		$options = $modal['body']['fields']['themes']['options'];
		$this->assertSame( array( 'theme-one', 'theme-two', 'theme-three', 'theme-four' ), array_keys( $options ) );
		foreach ( $options as $key => $option ) {
			$this->assertSame( $key, $option['value'] );
			$this->assertStringEndsWith( "exit-intent-gutenberg/{$key}.png", $option['icon'] );
		}

		$remove = $this->field_in( 'nx-exit-intent_with_gutenberg-remove', 'exit_intent_custom_tab' );
		$this->assertSame( '/notificationx/v1/exit-intent/gutenberg/remove', $remove['ajax']['api'] );
		$this->assertSame(
			array( 'gutenberg_id' => false, 'gutenberg_edit_link' => '', 'gutenberg_exit_theme' => 'theme-one', 'is_gb_confirmed' => false, 'themes' => 'exit_intent_custom_theme-one' ),
			$remove['trigger'][0]['action']
		);
	}

	public function test_elementor_controls_keep_their_settings() {
		$modal = $this->field_in( 'nx-exit-intent_with_elementor', 'exit_intent_custom_tab' );
		$this->assertSame( 'Build With Elementor', $modal['button']['text'] );
		$this->assertSame( '/notificationx/v1/exit-intent/elementor/import', $modal['confirm_button']['fields'][0]['ajax']['api'] );
		$this->assertTrue( $modal['confirm_button']['fields'][0]['ajax']['hideSwal'] );
		$this->assertSame( 'elementor_exit_theme', $modal['body']['fields']['themes']['name'] );
		$remove = $this->field_in( 'nx-exit-intent_with_elementor-remove', 'exit_intent_custom_tab' );
		$this->assertSame( '/notificationx/v1/exit-intent/elementor/remove', $remove['ajax']['api'] );
	}

	public function test_hidden_state_defaults() {
		$this->assertTrue( $this->field_in( 'is_gutenberg', 'exit_intent_custom_tab' )['default'] );
		$this->assertFalse( $this->field_in( 'gutenberg_id', 'exit_intent_custom_tab' )['default'] );
		$this->assertFalse( $this->field_in( 'is_gb_confirmed', 'exit_intent_custom_tab' )['default'] );
	}

	public function test_no_gutenberg_button_without_the_block_editor() {
		add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
			return 'nx_exit_intent_eb' === $type ? false : $use;
		}, 10, 2 );
		$this->assertFalse( GutenbergDesign::block_editor_available() );
		$this->assertFalse( $this->field_in( 'is_gutenberg', 'exit_intent_custom_tab' )['default'] );
	}

	public function test_quick_builder_lists_the_new_controls() {
		$ref    = new ReflectionClass( QuickBuild::class );
		$source = file_get_contents( $ref->getFileName() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading the class under test.
		foreach ( array( 'build_with_gutenberg', 'nx-exit-intent_with_gutenberg', 'nx-exit-intent_with_gutenberg-remove', 'nx-exit-intent_gutenberg_no_edit', 'gutenberg_edit_link', 'gutenberg_exit_theme', 'import_gutenberg_exit_theme', 'import_gutenberg_exit_theme_next', 'exit_intent_ai_gutenberg_note' ) as $name ) {
			$this->assertStringContainsString( "'{$name}'", $source, $name );
		}
	}

	public function test_saving_drops_the_builder_flags_for_exit_intent_only() {
		$design = self::factory()->post->create( array( 'post_type' => 'nx_exit_intent_eb', 'post_status' => 'publish', 'post_content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ) );
		$saved  = PostType::get_instance()->save_post(
			array(
				'type'            => 'exit_intent',
				'source'          => 'exit_intent_custom',
				'themes'          => null,
				'enabled'         => false,
				'title'           => 'block design flags',
				'gutenberg_id'    => $design,
				'is_gutenberg'    => true,
				'is_gb_confirmed' => true,
			)
		);
		$row  = PostType::get_instance()->get_post( $saved['nx_id'] );
		$data = maybe_unserialize( $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT data FROM {$GLOBALS['wpdb']->prefix}nx_posts WHERE nx_id = %d", $saved['nx_id'] ) ) );
		$this->assertArrayNotHasKey( 'is_gb_confirmed', $data );
		$this->assertArrayNotHasKey( 'is_gutenberg', $data );
		$this->assertSame( $design, (int) $data['gutenberg_id'] );
		$this->assertSame( $design, (int) $row['gutenberg_id'] );

		$filtered = GutenbergDesign::get_instance()->strip_builder_flags(
			array( 'source' => 'popup_notification', 'data' => array( 'is_gb_confirmed' => true ) ),
			array(),
			0
		);
		$this->assertTrue( $filtered['data']['is_gb_confirmed'], 'Other sources are untouched.' );
	}

	public function test_a_block_design_is_never_skipped_for_a_pro_theme() {
		$theme = NotificationX::PRO_RENDERED_THEMES[0];
		$this->assertFalse( NotificationX::is_theme_unrenderable( array( 'source' => 'exit_intent_custom', 'themes' => $theme, 'gutenberg_id' => 55 ) ) );
	}
}
