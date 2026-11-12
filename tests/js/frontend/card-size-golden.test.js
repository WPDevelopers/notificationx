/**
 * Card size presets (84708), Default must stay as it is: the rendered markup
 * of every in-scope theme (card-size-fixtures.js), with the credit on and off.
 *
 * The snapshots were taken on dev before any card-size code. They must never
 * change for a notification without `card_size` or with `card_size: 'default'`.
 */
import { CARD_SIZE_THEMES } from './card-size-fixtures';
import { renderCard } from './card-size-render';

describe( 'card size: Default markup of the in-scope themes', () => {
	it.each( CARD_SIZE_THEMES )( '%s, credit on', ( themes ) => {
		expect( renderCard( themes ) ).toMatchSnapshot();
	} );

	it.each( CARD_SIZE_THEMES )( '%s, credit off', ( themes ) => {
		expect( renderCard( themes, { disable_powered_by: true } ) ).toMatchSnapshot();
	} );
} );
