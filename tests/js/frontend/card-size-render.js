/**
 * Card size presets (84708): renders one in-scope notification with the real
 * frontend `Notification` and returns its markup. Shared by the golden
 * snapshots, the card-size unit tests and the screenshot tool (tests/visual/).
 *
 * Not a test file (jest runs only *.test.js).
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import { cardCase } from './card-size-fixtures';

// A fixed time, so the markup never depends on when the test runs.
const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	getTime: () => ( { fromNow: ( withoutSuffix ) => ( withoutSuffix ? '2 hours' : '2 hours ago' ) } ),
	state: { is_pro: false },
};

const matchMedia = ( query ) => ( {
	matches: false,
	media: query,
	addEventListener() {},
	removeEventListener() {},
	addListener() {},
	removeListener() {},
} );

/**
 * Renders a theme key's typical notification and returns `container.innerHTML`.
 * `extra` is merged into the settings (e.g. { card_size: 'compact' }).
 */
export const renderCard = ( themes, extra = {} ) => {
	const { config, data } = cardCase( themes, extra );
	config.template = GetTemplate( config );

	const hadMatchMedia = Object.prototype.hasOwnProperty.call( window, 'matchMedia' );
	const previousMatchMedia = window.matchMedia;
	window.matchMedia = matchMedia;
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	try {
		act( () => {
			ReactDOM.render(
				<NotificationProvider value={ frontendContext }>
					<Notification config={ config } data={ data } id={ 1 } dispatch={ () => {} } assets={ {} } />
				</NotificationProvider>,
				container
			);
		} );
		return container.innerHTML;
	} finally {
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		container.remove();
		if ( hadMatchMedia ) {
			window.matchMedia = previousMatchMedia;
		} else {
			delete window.matchMedia;
		}
	}
};
