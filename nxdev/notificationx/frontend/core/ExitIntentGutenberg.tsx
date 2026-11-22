import React, { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { loadAssets } from './LoadAssets';

/**
 * Exit Intent popup designed with the block editor ("Build With Gutenberg").
 *
 * Renders only for `mode === 'gutenberg'`; Elementor and built-in popups never
 * reach this component, so their markup and behaviour are unchanged.
 *
 * - The design's block CSS/JS: the page load normally puts them on the page
 *   (body class `nx-block-assets-{id}`, GutenbergDesign::enqueue_assets()).
 *   Otherwise (cached page, builder preview, share link) they are copied from
 *   the design's permalink first, and the popup stays hidden until then (8 s at
 *   most), so it never shows unstyled. Same fallback as the Gutenberg bar.
 * - A dialog: role="dialog", aria-modal, labelled; focus moves in on open and
 *   back on close; Tab stays inside; Escape closes.
 * - A link to `#nx-close` inside the design closes the popup like the ×.
 * - The wheel scrolls a long design inside the popup and never the page behind.
 * - Width and corner radius come from the design's outer Group block.
 *
 * @since x.x.x
 */

export const ASSETS_TIMEOUT = 8000;

export const blockAssetsOnPage = ( gutenbergId: any ): boolean => {
    const id = parseInt( gutenbergId, 10 );
    return !! id && typeof document !== 'undefined' && document.body.classList.contains( `nx-block-assets-${ id }` );
};

const FOCUSABLE = 'a[href], area[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"]), [contenteditable="true"]';

type Props = {
    settings: any;
    showClose: boolean;
    closeStyle: React.CSSProperties;
    overlayClass: string;
    onClose: () => void;
};

const ExitIntentGutenberg = ( { settings: s, showClose, closeStyle, overlayClass, onClose }: Props ) => {
    const [ ready, setReady ] = useState( () => ! s.gutenberg_url || blockAssetsOnPage( s.gutenberg_id ) );
    const dialogRef = useRef<HTMLDivElement | null>( null );
    const bodyRef = useRef<HTMLDivElement | null>( null );
    const closeRef = useRef( onClose );
    closeRef.current = onClose;

    // Load the design's block assets when this page doesn't have them.
    useEffect( () => {
        if ( ready ) return;
        let done = false;
        const finish = () => {
            if ( ! done ) {
                done = true;
                setReady( true );
            }
        };
        const timer = setTimeout( finish, ASSETS_TIMEOUT );
        loadAssets( s.gutenberg_url ).catch( () => {} ).then( finish );
        return () => {
            done = true;
            clearTimeout( timer );
        };
    }, [] );

    // Essential Blocks countdowns start through their own global, as in bars.
    useEffect( () => {
        if ( ! ready ) return;
        // @ts-ignore
        if ( typeof window.ebRunCountDown === 'function' ) window.ebRunCountDown();
    }, [ ready ] );

    // Focus in on open, back on close; Escape closes; Tab stays inside.
    useEffect( () => {
        if ( ! ready ) return;
        const dialog = dialogRef.current;
        if ( ! dialog ) return;
        const previous = document.activeElement as HTMLElement | null;
        const first = ( dialog.querySelector( '.nx-exit-intent-close' ) as HTMLElement ) || dialog;
        first.focus( { preventScroll: true } );

        const onKey = ( e: KeyboardEvent ) => {
            if ( e.key === 'Escape' || e.key === 'Esc' ) {
                e.preventDefault();
                closeRef.current();
                return;
            }
            if ( e.key !== 'Tab' ) return;
            const items = Array.from( dialog.querySelectorAll( FOCUSABLE ) ) as HTMLElement[];
            if ( ! items.length ) {
                e.preventDefault();
                dialog.focus();
                return;
            }
            const firstItem = items[ 0 ];
            const lastItem = items[ items.length - 1 ];
            if ( e.shiftKey && ( document.activeElement === firstItem || document.activeElement === dialog ) ) {
                e.preventDefault();
                lastItem.focus();
            } else if ( ! e.shiftKey && document.activeElement === lastItem ) {
                e.preventDefault();
                firstItem.focus();
            }
        };
        document.addEventListener( 'keydown', onKey );
        return () => {
            document.removeEventListener( 'keydown', onKey );
            if ( previous && typeof previous.focus === 'function' && document.contains( previous ) ) {
                previous.focus( { preventScroll: true } );
            }
        };
    }, [ ready ] );

    // The wheel scrolls the design, never the page behind the popup. Some host
    // pages stop the browser from wheel-scrolling a box inside a fixed overlay
    // (seen on an Elementor site), so the popup scrolls its body itself.
    // Non-passive, so it can keep the page still.
    const overlayRef = useRef<HTMLDivElement | null>( null );
    useEffect( () => {
        const overlay = overlayRef.current;
        if ( ! overlay ) return;
        const onWheel = ( e: WheelEvent ) => {
            if ( e.ctrlKey ) return; // pinch-zoom
            const body = bodyRef.current;
            const inBody = !! body && body.contains( e.target as Node );
            const max = body ? body.scrollHeight - body.clientHeight : 0;
            const step = e.deltaMode === 1 ? 16 : e.deltaMode === 2 && body ? body.clientHeight : 1;
            const scroll = () => {
                if ( body ) body.scrollTop = Math.max( 0, Math.min( max, body.scrollTop + e.deltaY * step ) );
            };
            if ( e.cancelable ) {
                // Keep the page still and scroll the design ourselves.
                e.preventDefault();
                if ( inBody && max > 0 ) scroll();
                return;
            }
            // Some pages send wheel events the popup can't cancel. Scroll the
            // design only if the browser didn't, so it never scrolls twice.
            if ( ! inBody || max <= 0 ) return;
            const before = body.scrollTop;
            requestAnimationFrame( () => {
                if ( body.scrollTop === before ) scroll();
            } );
        };
        overlay.addEventListener( 'wheel', onWheel, { passive: false } );
        return () => overlay.removeEventListener( 'wheel', onWheel );
    }, [] );

    const onBodyClick = ( e: React.MouseEvent ) => {
        const target = e.target as HTMLElement;
        const link = target && typeof target.closest === 'function' ? target.closest( 'a[href="#nx-close"]' ) : null;
        if ( link ) {
            e.preventDefault();
            onClose();
        }
    };

    const layout = s.gutenberg_layout || {};
    const wrapStyle: React.CSSProperties = {};
    if ( layout.width ) ( wrapStyle as any )[ '--nx-exit-width' ] = layout.width;
    if ( layout.radius ) ( wrapStyle as any )[ '--nx-exit-radius' ] = layout.radius;
    if ( ! ready ) wrapStyle.visibility = 'hidden';

    const overlayStyle: React.CSSProperties = {
        background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.5)',
    };
    if ( ! ready ) overlayStyle.visibility = 'hidden';

    return (
        <div
            ref={ overlayRef }
            className={ `${ overlayClass } nx-exit-intent-has-gutenberg` }
            style={ overlayStyle }
            onClick={ ( e ) => { if ( e.target === e.currentTarget ) onClose(); } }
        >
            <div
                ref={ dialogRef }
                className="nx-exit-intent-gutenberg"
                style={ wrapStyle }
                role="dialog"
                aria-modal="true"
                aria-label={ s.title || __( 'Popup', 'notificationx' ) }
                tabIndex={ -1 }
                aria-busy={ ready ? undefined : true }
            >
                { showClose && (
                    <button
                        type="button"
                        className="nx-exit-intent-close"
                        style={ closeStyle }
                        onClick={ onClose }
                        aria-label={ __( 'Close', 'notificationx' ) }
                    >
                        ×
                    </button>
                ) }
                <div
                    ref={ bodyRef }
                    className="nx-exit-intent-gutenberg-body"
                    onClick={ onBodyClick }
                    dangerouslySetInnerHTML={ { __html: s.gutenberg_html } }
                />
            </div>
        </div>
    );
};

export default ExitIntentGutenberg;
