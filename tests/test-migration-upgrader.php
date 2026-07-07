<?php
/**
 * Version + upgrade invariant tests for NotificationX.
 *
 * Verifies that the plugin's version constant is present, that the
 * Upgrader / Migration / Database singletons instantiate through the
 * shared GetInstance trait, and that the DB version/table constants are
 * internally consistent. It deliberately does NOT trigger a live DB
 * migration.
 *
 * @package Notificationx
 */

use NotificationX\Core\Database;
use NotificationX\Core\Migration;
use NotificationX\Core\Upgrader;

/**
 * @group migration
 * @group upgrader
 */
class Test_Migration_Upgrader extends WP_UnitTestCase {

	/**
	 * The plugin version constant must be defined and non-empty.
	 */
	public function test_version_constant_is_defined_and_non_empty() {
		$this->assertTrue( defined( 'NOTIFICATIONX_VERSION' ), 'NOTIFICATIONX_VERSION should be defined.' );
		$this->assertNotEmpty( NOTIFICATIONX_VERSION, 'NOTIFICATIONX_VERSION should not be empty.' );
		$this->assertIsString( NOTIFICATIONX_VERSION, 'NOTIFICATIONX_VERSION should be a string.' );
	}

	/**
	 * NOTIFICATIONX_VERSION must be a valid, comparable version string.
	 *
	 * Upgrader::__construct() relies on version_compare( NOTIFICATIONX_VERSION, ... ),
	 * so the constant has to be parseable as a version.
	 */
	public function test_version_constant_is_comparable() {
		// A well-formed version compares as >= to 0, and equal to itself.
		$this->assertGreaterThanOrEqual( 0, version_compare( NOTIFICATIONX_VERSION, '0' ) );
		$this->assertSame( 0, version_compare( NOTIFICATIONX_VERSION, NOTIFICATIONX_VERSION ) );
	}

	/**
	 * The core path/file constants used by the plugin must be defined.
	 */
	public function test_core_plugin_constants_are_defined() {
		$this->assertTrue( defined( 'NOTIFICATIONX_FILE' ), 'NOTIFICATIONX_FILE should be defined.' );
		$this->assertTrue( defined( 'NOTIFICATIONX_PATH' ), 'NOTIFICATIONX_PATH should be defined.' );
		$this->assertTrue( defined( 'NOTIFICATIONX_INCLUDES' ), 'NOTIFICATIONX_INCLUDES should be defined.' );
		$this->assertNotEmpty( NOTIFICATIONX_PATH );
	}

	/**
	 * The Database singleton must instantiate through the GetInstance trait.
	 */
	public function test_database_singleton_instantiates() {
		$database = Database::get_instance();
		$this->assertInstanceOf( Database::class, $database );

		// GetInstance must return the very same object on repeated calls.
		$this->assertSame( $database, Database::get_instance(), 'Database::get_instance() must be a singleton.' );
	}

	/**
	 * Database::$version is the schema version constant used to gate migrations.
	 */
	public function test_database_version_is_non_empty_string() {
		// Ensure the class (and thus its static props) is loaded.
		Database::get_instance();

		$this->assertIsString( Database::$version, 'Database::$version should be a string.' );
		$this->assertNotEmpty( Database::$version, 'Database::$version should not be empty.' );
		$this->assertGreaterThanOrEqual( 0, version_compare( Database::$version, '0' ), 'Database::$version should be a valid version string.' );
	}

	/**
	 * The static table-name constants must be initialized and use the wpdb prefix.
	 *
	 * These are populated in Database::__construct() from $wpdb->prefix, so they
	 * must exist and point at the expected nx_* tables once the singleton is built.
	 */
	public function test_database_table_names_are_prefixed_and_consistent() {
		global $wpdb;

		// Constructing the singleton is what populates the static table names.
		Database::get_instance();

		$this->assertSame( $wpdb->prefix . 'nx_entries', Database::$table_entries );
		$this->assertSame( $wpdb->prefix . 'nx_posts', Database::$table_posts );
		$this->assertSame( $wpdb->prefix . 'nx_stats', Database::$table_stats );

		// The three table names must be distinct.
		$tables = array( Database::$table_entries, Database::$table_posts, Database::$table_stats );
		$this->assertCount( 3, array_unique( $tables ), 'The nx_* table names must be distinct.' );
	}

	/**
	 * The Upgrader singleton must instantiate through the GetInstance trait.
	 *
	 * Upgrader::get_instance() is already invoked during plugin init
	 * (NotificationX::init -> Upgrader::get_instance()), so this returns the
	 * existing singleton without re-running its constructor / DB creation.
	 */
	public function test_upgrader_singleton_instantiates() {
		$upgrader = Upgrader::get_instance();
		$this->assertInstanceOf( Upgrader::class, $upgrader );
		$this->assertSame( $upgrader, Upgrader::get_instance(), 'Upgrader::get_instance() must be a singleton.' );
	}

	/**
	 * The Migration singleton must instantiate through the GetInstance trait.
	 *
	 * Migration::__construct() only registers a 'plugins_loaded' action; it does
	 * not run any migration on instantiation, so building the singleton is safe.
	 */
	public function test_migration_singleton_instantiates() {
		$migration = Migration::get_instance();
		$this->assertInstanceOf( Migration::class, $migration );
		$this->assertSame( $migration, Migration::get_instance(), 'Migration::get_instance() must be a singleton.' );
	}
}
