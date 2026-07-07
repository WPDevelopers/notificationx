<?php
/**
 * Integrity tests for the ExtensionFactory.
 *
 * Verifies that the Type <-> Extension wiring is internally consistent:
 *  - the factory is a working singleton and actually registered extensions;
 *  - every registered extension is a real Extension instance;
 *  - every extension declares a $types id that resolves to a real Type in
 *    the TypeFactory (no extension points at an unknown type);
 *  - every extension that shows on a module declares a non-empty $module key;
 *  - the factory's per-type index is consistent with each extension's $types.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\Extension;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Types\TypeFactory;
use NotificationX\Types\Types;

class Test_Extension_Factory extends WP_UnitTestCase {

	/**
	 * @var ExtensionFactory
	 */
	protected $factory;

	/**
	 * @var TypeFactory
	 */
	protected $type_factory;

	public function set_up() {
		parent::set_up();
		$this->factory      = ExtensionFactory::get_instance();
		$this->type_factory = TypeFactory::get_instance();
	}

	/**
	 * get_instance() must return the ExtensionFactory singleton.
	 */
	public function test_get_instance_returns_singleton() {
		$this->assertInstanceOf( ExtensionFactory::class, $this->factory );
		// GetInstance trait: repeated calls return the same object.
		$this->assertSame( $this->factory, ExtensionFactory::get_instance() );
	}

	/**
	 * Registration must have run: get_all() returns a non-empty array of
	 * Extension instances keyed by their own id.
	 */
	public function test_registration_populated_extensions() {
		$extensions = $this->factory->get_all();

		$this->assertIsArray( $extensions );
		$this->assertNotEmpty( $extensions, 'ExtensionFactory registered no extensions.' );

		foreach ( $extensions as $key => $extension ) {
			$this->assertInstanceOf(
				Extension::class,
				$extension,
				"Registered entry '{$key}' is not an Extension."
			);
			// add() indexes extensions by $extension->id.
			$this->assertSame(
				$extension->id,
				$key,
				"Extension keyed as '{$key}' but its id is '{$extension->id}'."
			);
		}
	}

	/**
	 * No registered extension may point at a type id that does not exist in
	 * the TypeFactory. The declared $types must resolve to a real Types object.
	 */
	public function test_every_extension_points_at_a_known_type() {
		$valid_type_ids = array_keys( $this->type_factory->types );
		$this->assertNotEmpty( $valid_type_ids, 'TypeFactory declares no types.' );

		foreach ( $this->factory->get_all() as $key => $extension ) {
			// $types is a single type id string on the Extension base class.
			$this->assertIsString(
				$extension->types,
				"Extension '{$key}' has a non-string \$types."
			);
			$this->assertNotEmpty(
				$extension->types,
				"Extension '{$key}' declares an empty \$types."
			);
			$this->assertContains(
				$extension->types,
				$valid_type_ids,
				"Extension '{$key}' points at unknown type '{$extension->types}'."
			);
			// The type id must resolve to a concrete Types instance.
			$this->assertInstanceOf(
				Types::class,
				$this->type_factory->get( $extension->types ),
				"Type '{$extension->types}' for extension '{$key}' did not resolve."
			);
		}
	}

	/**
	 * Every extension that shows on a module must declare a non-empty $module
	 * settings key (extensions with show_on_module=false are intentionally
	 * exempt, e.g. Vimeo/Wistia/CCPA video & notice sources).
	 */
	public function test_module_extensions_declare_non_empty_module() {
		foreach ( $this->factory->get_all() as $key => $extension ) {
			if ( ! $extension->show_on_module ) {
				continue;
			}
			$this->assertIsString(
				$extension->module,
				"Extension '{$key}' has a non-string \$module."
			);
			$this->assertNotEmpty(
				$extension->module,
				"Module-visible extension '{$key}' declares an empty \$module."
			);
		}
	}

	/**
	 * The factory's per-type index must be consistent: each bucket key is a
	 * known type id, and every extension filed under it declares that type.
	 */
	public function test_type_index_is_consistent() {
		$valid_type_ids = array_keys( $this->type_factory->types );
		$all            = $this->factory->get_all();

		$this->assertIsArray( $this->factory->types );

		foreach ( $this->factory->types as $type_id => $bucket ) {
			$this->assertContains(
				$type_id,
				$valid_type_ids,
				"ExtensionFactory indexed unknown type '{$type_id}'."
			);
			$this->assertIsArray( $bucket );

			foreach ( $bucket as $ext_id => $extension ) {
				$this->assertInstanceOf( Extension::class, $extension );
				$this->assertSame(
					$type_id,
					$extension->types,
					"Extension '{$ext_id}' filed under '{$type_id}' but declares '{$extension->types}'."
				);
				// The same object must be reachable from get_all().
				$this->assertArrayHasKey( $ext_id, $all );
				$this->assertSame( $extension, $all[ $ext_id ] );
			}
		}
	}
}
