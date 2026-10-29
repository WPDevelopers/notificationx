/**
 * Admin components on React 17, the core React of WordPress < 6.2.
 *
 * Jest runs the plugin's React 17.0.2, the same major as that core. React 17
 * throws "Nothing was returned from render" (minified error #152) when a
 * component returns undefined, and without an error boundary it unmounts the
 * whole admin app (WPDevelopers/notificationx#196, notificationx-pro#56).
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import ProWidget from '../../../nxdev/notificationx/components/ProWidget';
import ErrorBoundary from '../../../nxdev/notificationx/components/ErrorBoundary';

let container;
beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
	delete window.notificationxTabs;
	jest.restoreAllMocks();
} );

const render = ( element ) => {
	act( () => {
		ReactDOM.render( element, container );
	} );
	return container;
};

describe( 'ProWidget', () => {
	it( 'renders nothing, without throwing, when Pro is active', () => {
		window.notificationxTabs = { is_pro_active: true };
		expect( () => render( <ProWidget /> ) ).not.toThrow();
		expect( container.innerHTML ).toBe( '' );
	} );

	it( 'renders the upgrade box when Pro is not active', () => {
		window.notificationxTabs = { is_pro_active: false };
		render( <ProWidget /> );
		expect( container.querySelector( '.notificationx-pro-widget' ) ).not.toBeNull();
	} );
} );

describe( 'ErrorBoundary', () => {
	const Broken = () => {
		throw new Error( 'boom' );
	};

	it( 'keeps a render error inside the boundary', () => {
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		render(
			<div>
				<p className="sibling">builder</p>
				<ErrorBoundary>
					<Broken />
				</ErrorBoundary>
			</div>
		);
		expect( container.querySelector( '.sibling' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-error-boundary' ) ).not.toBeNull();
	} );

	it( 'renders its children when nothing throws', () => {
		render(
			<ErrorBoundary>
				<span className="child">ok</span>
			</ErrorBoundary>
		);
		expect( container.querySelector( '.child' ) ).not.toBeNull();
	} );

	it( 'accepts undefined children, e.g. a filter with no callback', () => {
		expect( () => render( <ErrorBoundary>{ undefined }</ErrorBoundary> ) ).not.toThrow();
		expect( container.innerHTML ).toBe( '' );
	} );
} );
