/**
 * Exit Intent Custom tab: when the Elementor and Gutenberg controls show.
 *
 * The rules come from tests/js/fixtures/exit-intent-builder-rules.json, which
 * tests/test-exit-intent-gutenberg-builder.php keeps identical to what the PHP
 * builder sends. They are evaluated with quickbuilder's own rule engine.
 */
import RULES from '../fixtures/exit-intent-builder-rules.json';

jest.mock( 'lodash-es', () => jest.requireActual( 'lodash' ) );
jest.mock( 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css', () => ( {} ), { virtual: true } );

const { when } = require( 'quickbuilder/dist/dev/quickbuilder.cjs.js' );

const TAB = RULES.exit_intent_custom_tab;
const AI = RULES.exit_intent_ai_tab;
const SOURCE = 'exit_intent_custom';
const LINK = 'http://example.com/wp-admin/post.php?post=9&action=edit';

// The builder states a campaign can be in.
const STATES = {
	nothing: { source: SOURCE, is_elementor: true, is_gutenberg: true, elementor_id: false, gutenberg_id: false, is_confirmed: false, is_gb_confirmed: false },
	elementor: { source: SOURCE, is_elementor: true, is_gutenberg: true, elementor_id: 41, elementor_edit_link: LINK, gutenberg_id: false, is_confirmed: false, is_gb_confirmed: false },
	gutenberg: { source: SOURCE, is_elementor: true, is_gutenberg: true, elementor_id: false, gutenberg_id: 9, gutenberg_edit_link: LINK, is_confirmed: false, is_gb_confirmed: false },
	gutenbergNoEdit: { source: SOURCE, is_elementor: true, is_gutenberg: true, elementor_id: false, gutenberg_id: 9, gutenberg_edit_link: '', is_confirmed: false, is_gb_confirmed: false },
	noBlockEditor: { source: SOURCE, is_elementor: true, is_gutenberg: false, elementor_id: false, gutenberg_id: false, is_confirmed: false, is_gb_confirmed: false },
	noElementor: { source: SOURCE, is_elementor: false, is_gutenberg: true, elementor_id: false, gutenberg_id: false, is_confirmed: false, is_gb_confirmed: false },
	noElementorGutenberg: { source: SOURCE, is_elementor: false, is_gutenberg: true, elementor_id: false, gutenberg_id: 9, gutenberg_edit_link: LINK, is_confirmed: false, is_gb_confirmed: false },
	justImportedGutenberg: { source: SOURCE, is_elementor: true, is_gutenberg: true, elementor_id: false, gutenberg_id: false, is_confirmed: false, is_gb_confirmed: true },
};

const shown = ( rule, state ) => when( rule, STATES[ state ] );

describe( 'Build With Gutenberg', () => {
	it.each( [ 'nothing', 'noElementor' ] )( 'shows with no design linked (%s)', ( state ) => {
		expect( shown( TAB[ 'nx-exit-intent_with_gutenberg' ], state ) ).toBe( true );
	} );
	it.each( [ 'elementor', 'gutenberg', 'noBlockEditor', 'justImportedGutenberg' ] )( 'hides in state %s', ( state ) => {
		expect( shown( TAB[ 'nx-exit-intent_with_gutenberg' ], state ) ).toBe( false );
	} );
	it( 'Edit With Gutenberg and Remove show once a block design is linked', () => {
		expect( shown( TAB.gutenberg_edit_link, 'gutenberg' ) ).toBe( true );
		expect( shown( TAB[ 'nx-exit-intent_with_gutenberg-remove' ], 'gutenberg' ) ).toBe( true );
		expect( shown( TAB[ 'nx-exit-intent_gutenberg_no_edit' ], 'gutenberg' ) ).toBe( false );
	} );
	it( 'a user who cannot edit the design gets a note instead of the edit link', () => {
		expect( shown( TAB.gutenberg_edit_link, 'gutenbergNoEdit' ) ).toBe( false );
		expect( shown( TAB[ 'nx-exit-intent_gutenberg_no_edit' ], 'gutenbergNoEdit' ) ).toBe( true );
		expect( shown( TAB[ 'nx-exit-intent_with_gutenberg-remove' ], 'gutenbergNoEdit' ) ).toBe( true );
	} );
	it.each( [ 'nothing', 'elementor', 'noBlockEditor' ] )( 'no Gutenberg edit, note or remove in state %s', ( state ) => {
		expect( shown( TAB.gutenberg_edit_link, state ) ).toBe( false );
		expect( shown( TAB[ 'nx-exit-intent_gutenberg_no_edit' ], state ) ).toBe( false );
		expect( shown( TAB[ 'nx-exit-intent_with_gutenberg-remove' ], state ) ).toBe( false );
	} );
} );

describe( 'Elementor controls', () => {
	it( 'Build With Elementor and Install Elementor hide while a block design is linked', () => {
		expect( shown( TAB[ 'nx-exit-intent_with_elementor' ], 'gutenberg' ) ).toBe( false );
		expect( shown( TAB[ 'nx-exit-intent_with_elementor_install' ], 'noElementorGutenberg' ) ).toBe( false );
	} );

	// dev's rules for the two Elementor controls that gained "no block design".
	const DEV = {
		'nx-exit-intent_with_elementor': [ 'and', [ 'is', 'elementor_id', false ], [ 'is', 'is_elementor', true ], [ '!is', 'is_confirmed', true ], [ 'is', 'source', SOURCE ] ],
		'nx-exit-intent_with_elementor_install': [ 'and', [ 'is', 'is_elementor', false ], [ 'is', 'source', SOURCE ] ],
	};
	const noBlockDesign = Object.keys( STATES ).filter( ( s ) => STATES[ s ].gutenberg_id === false );
	it.each( Object.keys( DEV ) )( '%s behaves exactly as on dev whenever no block design is linked', ( name ) => {
		noBlockDesign.forEach( ( state ) => {
			expect( [ state, shown( TAB[ name ], state ) ] ).toEqual( [ state, when( DEV[ name ], STATES[ state ] ) ] );
		} );
	} );

	it( 'Edit With Elementor and Remove are unchanged', () => {
		expect( TAB.elementor_edit_link ).toEqual( [ 'and', [ '!is', 'elementor_edit_link', false ], [ 'isOfType', 'elementor_edit_link', 'string' ], [ '!is', 'elementor_id', false ] ] );
		expect( TAB[ 'nx-exit-intent_with_elementor-remove' ] ).toEqual( [ 'and', [ '!is', 'elementor_id', false ], [ 'is', 'is_elementor', true ], [ 'is', 'source', SOURCE ] ] );
		expect( shown( TAB.elementor_edit_link, 'elementor' ) ).toBe( true );
		expect( shown( TAB[ 'nx-exit-intent_with_elementor-remove' ], 'elementor' ) ).toBe( true );
	} );
} );

describe( 'Build With AI tab', () => {
	it( 'shows the AI builder unless a block design is linked', () => {
		expect( shown( AI.exit_intent_ai_tab_fields, 'nothing' ) ).toBe( true );
		expect( shown( AI.exit_intent_ai_tab_fields, 'elementor' ) ).toBe( true );
		expect( shown( AI.exit_intent_ai_tab_fields, 'gutenberg' ) ).toBe( false );
		expect( shown( AI.exit_intent_ai_gutenberg_note, 'gutenberg' ) ).toBe( true );
		expect( shown( AI.exit_intent_ai_gutenberg_note, 'nothing' ) ).toBe( false );
	} );
} );

describe( 'Content', () => {
	it( 'content sections and the Content step hide for either builder', () => {
		[ 'exit_intent_theme_one_section', 'content_tab' ].forEach( ( name ) => {
			expect( shown( RULES.content[ name ], 'nothing' ) ).toBe( true );
			expect( shown( RULES.content[ name ], 'elementor' ) ).toBe( false );
			expect( shown( RULES.content[ name ], 'gutenberg' ) ).toBe( false );
		} );
	} );
	it( 'the Content step of other sources is untouched', () => {
		expect( when( RULES.content.content_tab, { source: 'press_bar', gutenberg_id: 7, elementor_id: 8 } ) ).toBe( true );
	} );
} );
