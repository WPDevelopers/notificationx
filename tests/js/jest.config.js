/**
 * Jest config for the frontend runtime unit tests.
 *
 * Run: npm run test:js
 */
const path = require( 'path' );
const base = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...base,
	rootDir: path.resolve( __dirname, '../..' ),
	testMatch: [ '<rootDir>/tests/js/**/*.test.[jt]s?(x)' ],
	testPathIgnorePatterns: [ '/node_modules/', '/vendor/', '/nxbuild/' ],
	// Some dependencies ship ES modules only (memize, pulled in by
	// @wordpress/i18n through the admin helpers Analytics.tsx imports).
	transformIgnorePatterns: [ '^(?!.*/node_modules/memize/).*/node_modules/' ],
	setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
};
