/**
 * Jest config for the screenshot tool's render step (see README.md). Not part
 * of `npm run test:js`: it only writes the cards' markup for screens.js.
 */
const base = require( '../js/jest.config.js' );

module.exports = {
	...base,
	roots: [ '<rootDir>/tests/visual' ],
	testMatch: [ '<rootDir>/tests/visual/**/*.render.js' ],
};
