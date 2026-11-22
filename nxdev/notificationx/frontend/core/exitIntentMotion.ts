import { nxApplyFilters } from './hooks';

/**
 * Exit Intent Show / Hide animations (NotificationX Pro, like the Animation
 * section of the other types).
 *
 * The campaign stores `exit_intent_animation_show` / `exit_intent_animation_hide`
 * with the same values as the other types' `animation_notification_show` /
 * `animation_notification_hide`; `default` is shown as "None" and means no
 * animation, which is how every Exit Intent popup looked before. The classes
 * come from Pro's `nx_frontend_animation` filter and stylesheet (animate.css
 * and the nx-anim-* presets); without Pro, or with "None" for both, nothing
 * changes. Free adds the backdrop fade (`nx-exit-intent-anim-in` /
 * `nx-exit-intent-anim-out`) and keeps the popup mounted while it leaves.
 *
 * Only cards Free renders take the classes: the built-in themes one, three
 * and four, and Elementor and Gutenberg designs. Pro's own themes are left as
 * they are (the builder hides the options for them).
 *
 * @since x.x.x
 */

// animate.css's `animate__faster` (0.5s); the nx-anim-* exits are shorter.
export const EXIT_INTENT_EXIT_MS = 500;

const FREE_THEMES = [ 'theme-one', 'theme-three', 'theme-four' ];

// Popups that are leaving, by notice ID, with the time their exit ends. Kept
// outside the component: NotificationContainer renders the position groups by
// index, so another notification arriving can re-mount the popup during its
// exit, and the new instance must finish leaving instead of showing again.
const leaving = new Map<any, number>();

export const exitIntentLeaving = {
    start: ( id: any, ms: number ): void => {
        leaving.set( id, Date.now() + ms );
    },
    // Milliseconds left of the exit, or null when the popup isn't leaving.
    // An exit that ended over a second ago (its popup went away for good
    // before finishing) no longer counts.
    remaining: ( id: any ): number | null => {
        const end = leaving.get( id );
        if ( end === undefined ) return null;
        const left = end - Date.now();
        if ( left < -1000 ) {
            leaving.delete( id );
            return null;
        }
        return Math.max( 0, left );
    },
    done: ( id: any ): void => {
        leaving.delete( id );
    },
};

export const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    !! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

/**
 * Whether Free renders this popup's card (the same tests as ExitIntentPopup).
 */
export const freeRendersCard = ( settings: any, theme: string ): boolean => {
    if ( settings?.mode === 'elementor' && typeof settings?.elementor_html === 'string' && settings.elementor_html ) return true;
    if ( settings?.mode === 'gutenberg' && typeof settings?.gutenberg_html === 'string' && settings.gutenberg_html ) return true;
    return FREE_THEMES.includes( theme );
};

const option = ( value: any ): string => ( typeof value === 'string' && value ? value : 'default' );

// Popups shown in this page view, by notice ID. A popup re-mounted while open
// (the same container behaviour as above) doesn't play its entrance again.
const shown = new Set<any>();

export const exitIntentShown = {
    has: ( id: any ): boolean => shown.has( id ),
    add: ( id: any ): void => {
        shown.add( id );
    },
    forget: ( id: any ): void => {
        shown.delete( id );
    },
};

export type ExitIntentMotion = {
    cardClass: string;
    cardStyle: Record<string, any>;
    animatesIn: boolean;
    animatesOut: boolean;
};

/**
 * Classes and style for the card in its current state, or null when the popup
 * doesn't animate (as before). With `skipEntrance` (a popup already shown,
 * re-mounted) the open state has no animation classes; the exit still plays.
 */
export const exitIntentMotion = ( settings: any, isPro: boolean, hiding: boolean, skipEntrance = false ): ExitIntentMotion | null => {
    const show = option( settings?.exit_intent_animation_show );
    const hide = option( settings?.exit_intent_animation_hide );
    if ( show === 'default' && hide === 'default' ) {
        return null;
    }
    if ( skipEntrance && ! hiding ) {
        const answer = nxApplyFilters( 'nx_frontend_animation', undefined, {
            settings: { ...settings, animation_notification_show: show, animation_notification_hide: hide },
            animation: true,
            animationStyle: undefined,
            is_pro: isPro,
        } );
        // No answer (no Pro): no animation at all, as below.
        return answer ? { cardClass: '', cardStyle: {}, animatesIn: false, animatesOut: hide !== 'default' } : null;
    }
    const result: any = nxApplyFilters( 'nx_frontend_animation', undefined, {
        settings: { ...settings, animation_notification_show: show, animation_notification_hide: hide },
        animation: hiding,
        animationStyle: undefined,
        is_pro: isPro,
    } );
    if ( ! result || ! Array.isArray( result.classNames ) ) {
        return null;
    }
    const cardStyle: Record<string, any> = {};
    if ( result.style && typeof result.style === 'object' ) {
        Object.keys( result.style ).forEach( ( key ) => {
            if ( result.style[ key ] !== undefined && result.style[ key ] !== null && result.style[ key ] !== '' ) {
                cardStyle[ key ] = result.style[ key ];
            }
        } );
    }
    return {
        cardClass: result.classNames.filter( ( c: any ) => typeof c === 'string' && c ).join( ' ' ),
        cardStyle,
        animatesIn: show !== 'default',
        animatesOut: hide !== 'default',
    };
};

/**
 * Backdrop classes: it fades in with an entrance and out with an exit.
 */
export const exitIntentBackdropClass = ( motion: ExitIntentMotion | null, hiding: boolean ): string => {
    if ( ! motion ) return '';
    if ( hiding ) return motion.animatesOut ? 'nx-exit-intent-anim-out' : '';
    return motion.animatesIn ? 'nx-exit-intent-anim-in' : '';
};
