import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { installHooks } from '../helpers';

/**
 * Exit Intent themes two, five, six and seven are NotificationX Pro code
 * (WordPress.org Guideline 5, issue #184). Free renders them only through the
 * `nx_exit_intent_theme` filter. See docs/api/frontend-js-hooks.md.
 */

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	state: { is_pro: false },
};

let container;
beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
} );

const render = ( theme ) => {
	const nxExitIntent = {
		id: 7,
		config: { nx_id: 7, source: 'exit_intent_custom', themes: `exit_intent_custom_${ theme }` },
	};
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ frontendContext }>
				<ExitIntentPopup nxExitIntent={ nxExitIntent } rest={ frontendContext.rest } dispatch={ () => {} } />
			</NotificationProvider>,
			container
		);
	} );
	return container;
};

describe( 'Exit Intent Pro themes', () => {
	it.each( [ 'theme-two', 'theme-five', 'theme-six', 'theme-seven' ] )(
		'free has no markup of its own for %s',
		( theme ) => {
			render( theme );
			expect( container.querySelector( `.nx-exit-intent-${ theme }` ) ).toBeNull();
			expect( container.querySelector( 'input[type="email"]' ) ).toBeNull();
		}
	);

	it( 'nx_exit_intent_theme renders the add-on element once, with the shared helpers', () => {
		const filter = jest.fn( ( node, props ) =>
			props.theme === 'theme-seven' ? <div className="pro-theme-seven" /> : node
		);
		installHooks().addFilter( 'nx_exit_intent_theme', 'test', filter );
		render( 'theme-seven' );

		expect( container.querySelectorAll( '.pro-theme-seven' ) ).toHaveLength( 1 );
		const props = filter.mock.calls[ 0 ][ 1 ];
		expect( props.theme ).toBe( 'theme-seven' );
		expect( typeof props.handleClose ).toBe( 'function' );
		expect( typeof props.renderCta ).toBe( 'function' );
		expect( typeof props.submitEntry ).toBe( 'function' );
	} );

	it( 'a theme the filter does not claim keeps the built-in rendering', () => {
		installHooks().addFilter( 'nx_exit_intent_theme', 'test', ( node ) => node );
		render( 'theme-one' );
		expect( container.querySelector( '.nx-exit-intent-theme-one' ) ).not.toBeNull();
	} );
} );
