/**
 * When the builder shows the "After a form submit" fields.
 *
 * The rules come from tests/js/fixtures/submit-reappearance-rules.json, which
 * tests/test-submit-reappearance-fields.php keeps identical to what the PHP
 * fields send. They are evaluated with quickbuilder's own rule engine, the one
 * the builder uses.
 */
import RULES from '../fixtures/submit-reappearance-rules.json';

// quickbuilder's CommonJS build requires lodash-es, which is ESM only; the
// CommonJS lodash has the same API. Its stylesheet import is not needed here.
jest.mock( 'lodash-es', () => jest.requireActual( 'lodash' ) );
jest.mock( 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css', () => ( {} ), { virtual: true } );

const { when } = require( 'quickbuilder/dist/dev/quickbuilder.cjs.js' );

const shown = ( type, field, values ) => when( RULES[ type ][ field ], values );

describe( 'Popup', () => {
	it.each( [ 'four', 'five', 'six', 'seven' ] )( 'shows the select on form theme %s', ( n ) => {
		expect( shown( 'popup', 'submit_reappearance', { themes: `popup_notification_theme-${ n }` } ) ).toBe( true );
	} );

	it.each( [ 'one', 'two', 'three', 'eight', 'eleven' ] )( 'hides it on theme %s, which has no form', ( n ) => {
		expect( shown( 'popup', 'submit_reappearance', { themes: `popup_notification_theme-${ n }` } ) ).toBe( false );
	} );
} );

describe( 'Exit Intent', () => {
	it.each( [ 'one', 'seven' ] )( 'shows the select on form theme %s', ( n ) => {
		expect( shown( 'exit_intent', 'submit_reappearance', { themes: `exit_intent_custom_theme-${ n }`, elementor_id: false } ) ).toBe( true );
	} );

	it.each( [ 'two', 'three', 'four', 'five', 'six' ] )( 'hides it on theme %s, which has no form', ( n ) => {
		expect( shown( 'exit_intent', 'submit_reappearance', { themes: `exit_intent_custom_theme-${ n }` } ) ).toBe( false );
	} );

	it( 'shows it for an Elementor design, whatever the theme', () => {
		expect( shown( 'exit_intent', 'submit_reappearance', { themes: 'exit_intent_custom_theme-three', elementor_id: 412 } ) ).toBe( true );
	} );

	it.each( [ [ 'false', false ], [ 'missing', undefined ], [ 'an empty string', '' ] ] )( 'treats an elementor_id that is %s as no design', ( label, id ) => {
		expect( shown( 'exit_intent', 'submit_reappearance', { themes: 'exit_intent_custom_theme-three', elementor_id: id } ) ).toBe( false );
	} );
} );

describe( 'Notification Bar', () => {
	it( 'shows the select on a bar built with Elementor', () => {
		expect( shown( 'bar', 'submit_reappearance', { source: 'press_bar', elementor_id: 87 } ) ).toBe( true );
	} );

	// elementor_id is a hidden field with default false, and "Remove" sets it back to false.
	it.each( [ [ 'false', false ], [ '0', 0 ], [ 'an empty string', '' ] ] )( 'hides it when elementor_id is %s', ( label, id ) => {
		expect( shown( 'bar', 'submit_reappearance', { source: 'press_bar', elementor_id: id } ) ).toBe( false );
	} );

	it( 'hides it for every other source', () => {
		expect( shown( 'bar', 'submit_reappearance', { source: 'popup_notification', elementor_id: 87 } ) ).toBe( false );
	} );
} );

describe( 'the days field', () => {
	const formCase = {
		popup: { themes: 'popup_notification_theme-five' },
		exit_intent: { themes: 'exit_intent_custom_theme-one' },
		bar: { source: 'press_bar', elementor_id: 87 },
	};

	it.each( Object.keys( formCase ) )( 'shows only with "a number of days" (%s)', ( type ) => {
		expect( shown( type, 'submit_hide_days', { ...formCase[ type ], submit_reappearance: 'days' } ) ).toBe( true );
		expect( shown( type, 'submit_hide_days', { ...formCase[ type ], submit_reappearance: 'never' } ) ).toBe( false );
		expect( shown( type, 'submit_hide_days', { ...formCase[ type ], submit_reappearance: 'same_as_close' } ) ).toBe( false );
	} );
} );
