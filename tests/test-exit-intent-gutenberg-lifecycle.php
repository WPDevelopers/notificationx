<?php
/**
 * Exit Intent "Build With Gutenberg": the block design belongs to its
 * campaign — deleted with it, dropped when gone, renamed with it, copied for a
 * duplicate, carried by export / import.
 *
 * @package Notificationx
 */

use NotificationX\Admin\ImportExport;
use NotificationX\Core\PostType;
use NotificationX\Extensions\ExitIntent\GutenbergDesign;

class Test_Exit_Intent_Gutenberg_Lifecycle extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	protected function design( $args = array() ) {
		$seed = GutenbergDesign::read_seed( 'theme-four' );
		return self::factory()->post->create(
			array_merge( array( 'post_type' => 'nx_exit_intent_eb', 'post_status' => 'publish', 'post_title' => 'Design for NotificationX Exit Intent - Free shipping announcement', 'post_content' => $seed['content'] ), $args )
		);
	}

	protected function campaign( $gutenberg_id, $extra = array() ) {
		$saved = PostType::get_instance()->save_post(
			array_merge(
				array( 'type' => 'exit_intent', 'source' => 'exit_intent_custom', 'themes' => null, 'enabled' => false, 'title' => 'Spring sale', 'gutenberg_id' => $gutenberg_id ),
				$extra
			)
		);
		return (int) $saved['nx_id'];
	}

	protected function linked( $nx_id ) {
		$post = PostType::get_instance()->get_post( $nx_id );
		return isset( $post['gutenberg_id'] ) ? $post['gutenberg_id'] : null;
	}

	/* --- delete ------------------------------------------------------------ */

	public function test_deleting_the_campaign_deletes_its_design() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design );
		PostType::get_instance()->delete_post( $nx_id );
		$this->assertNull( get_post( $design ) );
	}

	public function test_a_design_another_campaign_links_is_kept() {
		$design = $this->design();
		$this->campaign( $design );
		GutenbergDesign::get_instance()->delete_with_campaign( 999, array( 'source' => 'exit_intent_custom', 'gutenberg_id' => $design ) );
		$this->assertNotNull( get_post( $design ) );
	}

	public function test_deleting_other_campaigns_leaves_designs_alone() {
		$design = $this->design();
		GutenbergDesign::get_instance()->delete_with_campaign( 5, array( 'source' => 'press_bar', 'gutenberg_id' => $design ) );
		$this->assertNotNull( get_post( $design ) );
	}

	/* --- save -------------------------------------------------------------- */

	public function test_an_id_that_is_not_a_design_is_dropped_on_save() {
		$page  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$nx_id = $this->campaign( $page );
		$this->assertFalse( $this->linked( $nx_id ) );
		$this->assertNotNull( get_post( $page ) );
	}

	public function test_a_duplicated_campaign_gets_its_own_copy() {
		$design = $this->design();
		$first  = $this->campaign( $design );
		$second = $this->campaign( $design, array( 'title' => 'Spring sale (copy)' ) );

		$copy = (int) $this->linked( $second );
		$this->assertSame( $design, (int) $this->linked( $first ) );
		$this->assertNotSame( $design, $copy );
		$this->assertSame( 'nx_exit_intent_eb', get_post_type( $copy ) );
		$this->assertSame( 'publish', get_post_status( $copy ) );
		$this->assertSame( get_post( $design )->post_content, get_post( $copy )->post_content );

		PostType::get_instance()->delete_post( $first );
		$this->assertNull( get_post( $design ) );
		$this->assertNotNull( get_post( $copy ), 'Deleting the original leaves the copy.' );
	}

	public function test_saving_the_same_campaign_again_keeps_its_design() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design );
		$this->campaign( $design, array( 'nx_id' => $nx_id, 'title' => 'Spring sale v2' ) );
		$this->assertSame( $design, (int) $this->linked( $nx_id ) );
		$this->assertCount( 1, get_posts( array( 'post_type' => 'nx_exit_intent_eb', 'numberposts' => -1, 'fields' => 'ids' ) ) );
	}

	public function test_the_design_is_named_after_its_campaign() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design, array( 'title' => 'Black Friday' ) );
		$this->assertSame( 'Design for NotificationX Exit Intent - Black Friday', get_post( $design )->post_title );
		$this->campaign( $design, array( 'nx_id' => $nx_id, 'title' => 'Cyber Monday' ) );
		$this->assertSame( 'Design for NotificationX Exit Intent - Cyber Monday', get_post( $design )->post_title );
		$revisions = count( wp_get_post_revisions( $design ) );
		$this->campaign( $design, array( 'nx_id' => $nx_id, 'title' => 'Cyber Monday' ) );
		$this->assertCount( $revisions, wp_get_post_revisions( $design ), 'An unchanged title is not written again.' );
	}

	/* --- load -------------------------------------------------------------- */

	public function test_load_refreshes_the_edit_link_for_the_current_user() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design );
		$this->assertStringContainsString( 'post.php?post=' . $design . '&action=edit', PostType::get_instance()->get_post( $nx_id )['gutenberg_edit_link'] );

		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );
		$this->assertSame( '', PostType::get_instance()->get_post( $nx_id )['gutenberg_edit_link'], 'Someone who cannot edit the design gets no link.' );
	}

	public function test_load_drops_a_deleted_design() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design );
		wp_delete_post( $design, true );
		$post = PostType::get_instance()->get_post( $nx_id );
		$this->assertFalse( $post['gutenberg_id'] );
		$this->assertSame( '', $post['gutenberg_edit_link'] );
	}

	public function test_load_drops_a_trashed_design() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design );
		wp_trash_post( $design );
		$this->assertFalse( PostType::get_instance()->get_post( $nx_id )['gutenberg_id'] );
	}

	/* --- export / import --------------------------------------------------- */

	protected function export( $nx_ids ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/export' );
		$request->set_param( 'export-notification', true );
		$request->set_param( 'export-notification-ids', $nx_ids );
		$result = ImportExport::get_instance()->export( $request );
		return json_decode( wp_json_encode( $result['data']['download'] ), true );
	}

	protected function import( $file ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/import' );
		$request->set_param( 'import', wp_json_encode( $file ) );
		return ImportExport::get_instance()->import( $request );
	}

	public function test_export_carries_the_design_and_import_recreates_it() {
		$design = $this->design();
		$nx_id  = $this->campaign( $design, array( 'title' => 'Exported' ) );
		$file   = $this->export( array( $nx_id ) );
		$this->assertArrayHasKey( (string) $design, $file['exit_intent_designs'] );
		$this->assertSame( get_post( $design )->post_content, $file['exit_intent_designs'][ $design ]['content'] );
		$this->assertSame( array( 'title', 'content' ), array_keys( $file['exit_intent_designs'][ $design ] ) );

		PostType::get_instance()->delete_post( $nx_id );
		$this->assertNull( get_post( $design ) );

		$this->assertSame( 'success', $this->import( $file )['status'] );
		$imported = PostType::get_instance()->get_posts( array( 'source' => 'exit_intent_custom' ) );
		$this->assertCount( 1, $imported );
		$new = (int) $imported[0]['gutenberg_id'];
		$this->assertNotSame( $design, $new );
		$this->assertSame( 'nx_exit_intent_eb', get_post_type( $new ) );
		$this->assertSame( $file['exit_intent_designs'][ $design ]['content'], get_post( $new )->post_content );
	}

	public function test_import_without_the_design_drops_the_link() {
		$file = array( 'notifications' => array( array( 'nx_id' => 1, 'type' => 'exit_intent', 'source' => 'exit_intent_custom', 'themes' => null, 'enabled' => false, 'title' => 'No design', 'gutenberg_id' => 4242 ) ) );
		$this->import( $file );
		$imported = PostType::get_instance()->get_posts( array( 'source' => 'exit_intent_custom' ) );
		$this->assertFalse( $imported[0]['gutenberg_id'] );
	}

	public function test_export_never_includes_other_content() {
		$page    = self::factory()->post->create( array( 'post_type' => 'page', 'post_content' => 'secret page' ) );
		$designs = GutenbergDesign::export_designs( array( array( 'source' => 'exit_intent_custom', 'gutenberg_id' => $page ) ) );
		$this->assertSame( array(), $designs );
	}

	public function test_imported_design_content_goes_through_kses_for_users_without_unfiltered_html() {
		$user = self::factory()->user->create( array( 'role' => 'author' ) );
		( new WP_User( $user ) )->add_cap( 'edit_notificationx' );
		wp_set_current_user( $user );
		$id = GutenbergDesign::get_instance()->import_design( array( 'title' => '<b>T</b>', 'content' => '<!-- wp:paragraph --><p>ok<script>alert(1)</script></p><!-- /wp:paragraph -->' ) );
		$this->assertSame( 'nx_exit_intent_eb', get_post_type( $id ) );
		$this->assertStringNotContainsString( '<script', get_post( $id )->post_content );
		$this->assertSame( 'T', get_post( $id )->post_title );
	}
}
