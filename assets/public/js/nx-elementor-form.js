/**
 * NotificationX Elementor Form — submission handler.
 *
 * Intercepts the form submit, posts Name / Email / Message to the
 * notificationx/v1/popup-submit REST endpoint and shows a success or
 * error message. The selected NX campaign ID is read from data-nx-id.
 */
( function () {
    'use strict';

    // ── Per-session submission guard ──────────────────────────────────────
    // A visitor may submit a given campaign's form only once per browser
    // session; the flag is stored in sessionStorage keyed by campaign id.
    function sessionStore() {
        try { return window.sessionStorage; } catch ( e ) { return null; }
    }
    function submittedKey( nxId ) {
        return 'notificationx_form_submitted_' + nxId;
    }
    function hasSubmitted( nxId ) {
        var store = sessionStore();
        try { return !! ( store && store.getItem( submittedKey( nxId ) ) ); }
        catch ( e ) { return false; }
    }
    function markSubmitted( nxId ) {
        var store = sessionStore();
        try { if ( store ) store.setItem( submittedKey( nxId ), '1' ); }
        catch ( e ) {}
    }

    // Disable the form's submit button and show an "already submitted" notice.
    function lockForm( form, btn, msgEl ) {
        if ( btn ) btn.disabled = true;
        if ( msgEl && ! msgEl.textContent ) {
            // `is-locked` tells the notification around the form that this is
            // not a new submit (watchFormSuccess.ts).
            msgEl.classList.add( 'is-success', 'is-locked' );
            msgEl.textContent = form.getAttribute( 'data-already' )
                || 'You have already submitted this form.';
        }
    }

    // If the form is rendered inside an Exit Intent popup, ask the popup to
    // close itself (handled in ExitIntentPopup.tsx) shortly after a success.
    function closeExitIntent( form, nxId ) {
        if ( ! form.closest || ! form.closest( '.nx-exit-intent-overlay' ) ) return;
        setTimeout( function () {
            document.dispatchEvent( new CustomEvent( 'nx:exit-intent-close', {
                detail: { nxId: nxId },
            } ) );
        }, 1600 );
    }

    function init( form ) {
        if ( ! form || form.__nxBound ) return;
        form.__nxBound = true;

        var msgEl = form.querySelector( '.nx-form-message' );
        var btn   = form.querySelector( '.nx-form-submit' );
        var nxId  = form.getAttribute( 'data-nx-id' );

        // Already submitted this session? Lock the form up-front.
        if ( nxId && hasSubmitted( nxId ) ) {
            lockForm( form, btn, msgEl );
        }

        form.addEventListener( 'submit', function ( ev ) {
            ev.preventDefault();

            // Guard against repeat submissions within the same session.
            if ( nxId && hasSubmitted( nxId ) ) {
                lockForm( form, btn, msgEl );
                return;
            }

            if ( msgEl ) {
                msgEl.textContent = '';
                msgEl.classList.remove( 'is-success', 'is-error', 'is-locked' );
            }

            // Native required-field validation.
            if ( typeof form.checkValidity === 'function' && ! form.checkValidity() ) {
                form.reportValidity();
                return;
            }

            var url = form.getAttribute( 'data-rest-url' );
            if ( ! nxId || ! url ) return;

            var payload = {
                nx_id:     nxId,
                timestamp: Math.floor( Date.now() / 1000 ),
            };

            var nameEl    = form.querySelector( '[name="name"]' );
            var emailEl   = form.querySelector( '[name="email"]' );
            var messageEl = form.querySelector( '[name="message"]' );

            if ( nameEl )    payload.name    = nameEl.value;
            if ( emailEl )   payload.email   = emailEl.value;
            if ( messageEl ) payload.message = messageEl.value;

            if ( btn ) btn.disabled = true;

            // popup-submit is a public endpoint (permission_callback => __return_true)
            // and never verifies a nonce server-side. We deliberately do NOT send an
            // X-WP-Nonce header: a baked-in wp_rest nonce goes stale in the Elementor
            // builder and on cached pages, and WordPress core would then reject the
            // request with "Cookie check failed" (rest_cookie_invalid_nonce). With no
            // nonce, core treats the submission as anonymous and lets it through.
            var headers = { 'Content-Type': 'application/json' };

            fetch( url, {
                method:  'POST',
                headers: headers,
                body:    JSON.stringify( payload ),
            } )
            .then( function ( res ) {
                return res.json().then( function ( body ) {
                    return { ok: res.ok && body && body.success, body: body };
                } ).catch( function () {
                    return { ok: res.ok, body: {} };
                } );
            } )
            .then( function ( result ) {
                if ( result.ok ) {
                    // Remember the submission for the rest of the session and
                    // keep the form locked so it can't be sent again.
                    markSubmitted( nxId );
                    if ( btn ) btn.disabled = true;
                    if ( msgEl ) {
                        msgEl.classList.add( 'is-success' );
                        msgEl.textContent = form.getAttribute( 'data-success' ) || 'Submitted.';
                    }
                    form.reset();
                    // When the form lives inside an Exit Intent popup, dismiss it
                    // after the success message has had a moment to register.
                    closeExitIntent( form, nxId );
                } else if ( msgEl ) {
                    msgEl.classList.add( 'is-error' );
                    msgEl.textContent = ( result.body && result.body.message )
                        || form.getAttribute( 'data-error' )
                        || 'Something went wrong.';
                }
            } )
            .catch( function () {
                if ( ! msgEl ) return;
                msgEl.classList.add( 'is-error' );
                msgEl.textContent = form.getAttribute( 'data-error' ) || 'Something went wrong.';
            } )
            .finally( function () {
                // Leave the button disabled once a submission has gone through;
                // only re-enable it after a failed attempt.
                if ( btn && ! ( nxId && hasSubmitted( nxId ) ) ) btn.disabled = false;
            } );
        } );
    }

    function bindAll( root ) {
        ( root || document ).querySelectorAll( '.nx-form' ).forEach( init );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', function () { bindAll(); } );
    } else {
        bindAll();
    }

    // Re-bind in the Elementor editor preview when widgets re-render.
    if ( window.jQuery ) {
        window.jQuery( window ).on( 'elementor/frontend/init', function () {
            if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
                window.elementorFrontend.hooks.addAction(
                    'frontend/element_ready/nx-form.default',
                    function ( $scope ) { bindAll( $scope[ 0 ] ); }
                );
            }
        } );
    }
} )();
