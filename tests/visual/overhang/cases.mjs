/**
 * Cases for the overhang visual tests.
 *
 * Single cards: every corner theme with the Button off and on, at 1400px and 390px, plus
 * every Pro mobile card at 390px. Stacks: three cards in one corner.
 */
export const DESKTOP = { width: 1400, height: 1000 };
export const PHONE = { width: 390, height: 844 };
export const CORNERS = [ 'bottom_left', 'bottom_right', 'top_left', 'top_right' ];

// The sources whose types stack in the corners. Reviews has three sets of themes.
const SOURCES = {
	form: 'cf7', email_subscription: 'mailchimp', custom: 'custom_notification', conversions: 'woocommerce',
	offer_announcement: 'announcements', reviews: [ 'wp_reviews', 'google_reviews', 'woo_reviews' ], download_stats: 'wp_stats',
	donation: 'give', page_analytics: 'google', elearning: 'tutor', woocommerce_sales: 'woocommerce_sales',
	woocommerce_cart_peek: 'woocommerce_cart_peek', comments: 'wp_comments', video: 'youtube',
};

export function singleCases( list ) {
	const cases = [];
	for ( const [ type, sources ] of Object.entries( SOURCES ) ) {
		[].concat( sources ).forEach( ( source, k ) => {
			const keys = Object.keys( list[ source ]?.themes || {} );
			for ( const themes of keys ) {
				for ( const button of [ false, true ] ) {
					cases.push( { type, source, themes, button, view: 'desktop', ...DESKTOP } );
					cases.push( { type, source, themes, button, view: 'phone', ...PHONE } );
				}
			}
			if ( k === 0 ) {
				for ( const res of list[ source ]?.res || [] ) {
					for ( const button of [ false, true ] ) {
						cases.push( { type, source, themes: keys[ 0 ], res, button, view: 'phone-res', ...PHONE } );
					}
				}
			}
		} );
	}
	cases.forEach( ( c ) => {
		c.id = `${ c.view }-${ c.res || c.themes }${ c.button ? '-btn' : '' }`;
	} );
	return cases;
}

const card = ( type, source, themes, extra = {} ) => ( { type, source, themes, ...extra } );

// Three cards per stack, top to bottom in the order they mount.
export const STACK_SETS = {
	// Nothing sticks out: must stay pixel-identical.
	plain: [ card( 'conversions', 'woocommerce', 'conversions_theme-two' ), card( 'comments', 'wp_comments', 'comments_theme-one' ), card( 'donation', 'give', 'donation_theme-one' ) ],
	// Button tab (35px), theme 13 button over the edge (30px), map image (top and bottom).
	free: [ card( 'conversions', 'woocommerce', 'conversions_theme-one', { button: true } ), card( 'conversions', 'woocommerce', 'conversions_conv-theme-thirteen', { button: true } ), card( 'conversions', 'woocommerce', 'conversions_maps_theme' ) ],
	// Discount Alert theme-15 (branding below), Cart Peek, sales theme 7 badge (Pro CSS on phones).
	pro: [ card( 'offer_announcement', 'announcements', 'announcements_theme-15' ), card( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_theme-one' ), card( 'conversions', 'woocommerce', 'conversions_conv-theme-seven', { button: true } ) ],
	// Pro mobile cards (390px, Mobile Responsive on).
	'mobile-plain': [ card( 'form', 'cf7', 'form_theme-one', { res: 'form_res-theme-one' } ), card( 'conversions', 'woocommerce', 'conversions_theme-one', { res: 'conversions_res-theme-two' } ), card( 'comments', 'wp_comments', 'comments_theme-one', { res: 'comments_res-theme-one' } ) ],
	'mobile-mixed': [ card( 'conversions', 'woocommerce', 'conversions_theme-one', { res: 'conversions_res-theme-eight', button: true } ), card( 'offer_announcement', 'announcements', 'announcements_theme-1', { res: 'announcements_res-theme-one' } ), card( 'conversions', 'woocommerce', 'conversions_theme-one', { res: 'conversions_res-theme-three' } ) ],
};

// Every stack configuration: set x corner x width x spacing mode, plus the admin bar in
// the top corners. Mobile-card sets only at 390px.
export function stackCases() {
	const out = [];
	for ( const [ set, cards ] of Object.entries( STACK_SETS ) ) {
		const views = set.startsWith( 'mobile' ) ? [ PHONE ] : [ DESKTOP, PHONE ];
		for ( const view of views ) {
			for ( const position of CORNERS ) {
				for ( const spacingV2 of [ true, false ] ) {
					for ( const adminBar of position.startsWith( 'top' ) ? [ false, true ] : [ false ] ) {
						out.push( { set, cards, position, spacingV2, adminBar, ...view,
							id: `${ set }-${ view.width }-${ position }-${ spacingV2 ? 'v2' : 'legacy' }${ adminBar ? '-adminbar' : '' }` } );
					}
				}
			}
		}
	}
	return out;
}

// The plain set of the same width and kind: the reference for gaps and edge spacing.
export const referenceOf = ( c ) => `${ c.set.startsWith( 'mobile' ) ? 'mobile-plain' : 'plain' }-${ c.width }-${ c.position }-${ c.spacingV2 ? 'v2' : 'legacy' }${ c.adminBar ? '-adminbar' : '' }`;
