<?php
/**
 * TypeFactory integrity tests.
 *
 * Verifies that every id declared in TypeFactory::$types maps to a real,
 * instantiable Type class whose own ->id round-trips back to the map key,
 * and that register_types()/get()/types_enabled gating behave as coded.
 *
 * @package Notificationx
 */

use NotificationX\Types\TypeFactory;
use NotificationX\Types\Types;

/**
 * @group types
 */
class Test_Types_Factory extends WP_UnitTestCase {

	/**
	 * A fresh, isolated factory so the shared GetInstance singleton's
	 * accumulated types_enabled state does not leak into gating assertions.
	 *
	 * @var TypeFactory
	 */
	protected $factory_under_test;

	public function set_up() {
		parent::set_up();
		// Constructor applies the nx_types_classes filter; a plain new is
		// intentional here to get a clean types_enabled map per test.
		$this->factory_under_test = new TypeFactory();
	}

	/**
	 * The map must be a non-empty associative array of id => class-string.
	 */
	public function test_types_map_is_populated() {
		$this->assertIsArray( $this->factory_under_test->types );
		$this->assertNotEmpty( $this->factory_under_test->types );
	}

	/**
	 * For every id in the map: the target class exists, register_types()
	 * returns an instance of that class (an instance of the Types base),
	 * and the instance's own id round-trips back to the map key.
	 */
	public function test_every_type_id_resolves_and_round_trips() {
		foreach ( $this->factory_under_test->types as $id => $class ) {
			$this->assertTrue(
				class_exists( $class ),
				"Mapped class for id '{$id}' does not exist: {$class}"
			);

			$instance = $this->factory_under_test->register_types( $id );

			$this->assertInstanceOf(
				$class,
				$instance,
				"register_types('{$id}') did not return an instance of {$class}"
			);
			$this->assertInstanceOf(
				Types::class,
				$instance,
				"Instance for id '{$id}' is not a Types subclass"
			);

			// The map key must equal the instance's declared id. This is the
			// invariant register_types()/add() rely on, since add() keys
			// types_enabled by $type->id while register_types() gates on $id.
			$this->assertSame(
				$id,
				$instance->id,
				"Map key '{$id}' does not match instance->id '{$instance->id}'"
			);
		}
	}

	/**
	 * register_types() registers into types_enabled and is idempotent:
	 * a second call for an already-enabled id short-circuits to false.
	 */
	public function test_register_types_gates_on_types_enabled() {
		$id = 'conversions';

		$this->assertArrayNotHasKey( $id, $this->factory_under_test->types_enabled );

		$first = $this->factory_under_test->register_types( $id );
		$this->assertInstanceOf( $this->factory_under_test->types[ $id ], $first );
		$this->assertArrayHasKey( $id, $this->factory_under_test->types_enabled );

		// Already enabled -> second registration returns false.
		$second = $this->factory_under_test->register_types( $id );
		$this->assertFalse( $second );
	}

	/**
	 * An unknown id is not in the map and register_types() returns false.
	 */
	public function test_register_types_rejects_unknown_id() {
		$this->assertArrayNotHasKey( 'not_a_real_type', $this->factory_under_test->types );
		$this->assertFalse( $this->factory_under_test->register_types( 'not_a_real_type' ) );
	}

	/**
	 * get_all() exposes exactly what has been registered via register_types().
	 */
	public function test_get_all_returns_enabled_types() {
		$this->assertSame( array(), $this->factory_under_test->get_all() );

		$instance = $this->factory_under_test->register_types( 'popup' );

		$all = $this->factory_under_test->get_all();
		$this->assertArrayHasKey( 'popup', $all );
		$this->assertSame( $instance, $all['popup'] );
	}

	/**
	 * get() returns the already-registered instance when enabled, and
	 * otherwise falls back to instantiating the mapped class.
	 */
	public function test_get_returns_enabled_or_falls_back() {
		// Fallback path: not yet enabled, still resolves to the mapped class.
		$fallback = $this->factory_under_test->get( 'reviews' );
		$this->assertInstanceOf( $this->factory_under_test->types['reviews'], $fallback );

		// Enabled path: get() returns the exact stored instance.
		$registered = $this->factory_under_test->register_types( 'reviews' );
		$this->assertSame( $registered, $this->factory_under_test->get( 'reviews' ) );
	}

	/**
	 * get() with an empty key returns false (guard clause).
	 */
	public function test_get_with_empty_key_returns_false() {
		$this->assertFalse( $this->factory_under_test->get( '' ) );
	}
}
