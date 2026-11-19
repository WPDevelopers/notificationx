/**
 * Exit Intent Custom tab preview: the Elementor preview is unchanged; a block
 * design shows its starting design's preview.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';

let mockValues = {};
jest.mock( 'quickbuilder', () => ( { useBuilderContext: () => ( { values: mockValues } ) } ) );
jest.mock( '../../../nxdev/notificationx/hooks', () => ( { useNotificationXContext: () => ( { assets: { admin: 'http://example.com/assets/admin/' } } ) } ) );

// eslint-disable-next-line import/first
import ExitIntentPreset from '../../../nxdev/notificationx/fields/ExitIntentPreset';

const THEMES = 'http://example.com/assets/admin/images/extensions/themes';
let container;
beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
} );
const render = ( values ) => {
	mockValues = values;
	act( () => {
		ReactDOM.render( <ExitIntentPreset />, container );
	} );
	return container;
};

describe( 'ExitIntentPreset', () => {
	it( 'Elementor design: the same preview as before', () => {
		render( { is_elementor: true, elementor_id: 41, elementor_exit_theme: 'theme-three', is_gutenberg: true, gutenberg_id: false } );
		expect( container.querySelector( 'img' ).getAttribute( 'src' ) ).toBe( `${ THEMES }/exit-intent/exit-intent-theme-three.png` );
		expect( container.querySelector( '.nxbar-presets-empty-state' ) ).toBeNull();
	} );

	it( 'block design: the starting design preview', () => {
		render( { is_elementor: true, elementor_id: false, is_gutenberg: true, gutenberg_id: 9, gutenberg_exit_theme: 'theme-two' } );
		expect( container.querySelector( '.nxbar-selected-presets img' ).getAttribute( 'src' ) ).toBe( `${ THEMES }/exit-intent-gutenberg/theme-two.png` );
	} );

	it( 'nothing linked, block editor available: both builders offered', () => {
		render( { is_elementor: true, elementor_id: false, is_gutenberg: true, gutenberg_id: false } );
		expect( container.querySelector( '.nxbar-presets-empty-state h4' ).textContent ).toBe( 'Create with Your Preferred Builder/Editor' );
	} );

	it( 'nothing linked, no block editor: the Elementor wording as before', () => {
		render( { is_elementor: true, elementor_id: false, is_gutenberg: false, gutenberg_id: false } );
		expect( container.querySelector( '.nxbar-presets-empty-state h4' ).textContent ).toBe( 'Create your Exit Intent Popup with Elementor' );
	} );
} );
