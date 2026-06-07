/**
 * NotificationX Elementor Form — submission handler.
 *
 * Intercepts the form submit, posts Name / Email / Message to the
 * notificationx/v1/popup-submit REST endpoint and shows a success or
 * error message. The selected NX campaign ID is read from data-nx-id.
 */
( function () {
    'use strict';

    function init( form ) {
        if ( ! form || form.__nxBound ) return;
        form.__nxBound = true;

        var msgEl = form.querySelector( '.nx-form-message' );
        var btn   = form.querySelector( '.nx-form-submit' );

        form.addEventListener( 'submit', function ( ev ) {
            ev.preventDefault();

            if ( msgEl ) {
                msgEl.textContent = '';
                msgEl.classList.remove( 'is-success', 'is-error' );
            }

            // Native required-field validation.
            if ( typeof form.checkValidity === 'function' && ! form.checkValidity() ) {
                form.reportValidity();
                return;
            }

            var nxId = form.getAttribute( 'data-nx-id' );
            var url  = form.getAttribute( 'data-rest-url' );
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

            var headers = { 'Content-Type': 'application/json' };
            var nonce = form.getAttribute( 'data-rest-nonce' );
            if ( nonce ) headers[ 'X-WP-Nonce' ] = nonce;

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
                if ( ! msgEl ) return;
                if ( result.ok ) {
                    msgEl.classList.add( 'is-success' );
                    msgEl.textContent = form.getAttribute( 'data-success' ) || 'Submitted.';
                    form.reset();
                } else {
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
                if ( btn ) btn.disabled = false;
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
