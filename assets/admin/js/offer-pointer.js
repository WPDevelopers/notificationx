/**
 * The seasonal offer pointer beside the NotificationX admin menu item
 * (includes/Admin/OfferPointer.php).
 *
 * Opens a WordPress admin pointer with the campaign's title, one line of copy, a button to the
 * campaign page and a Dismiss link. Closing it with Dismiss records the dismissal for this user;
 * following the button leaves it open.
 *
 * The pointer stays beside the menu item while the page scrolls: WordPress places a pointer once,
 * in page coordinates, but the admin menu stays in view, so the pointer is kept in viewport
 * coordinates and moved whenever the menu link moves.
 *
 * Plain JavaScript with no build step: this file is what the browser loads. The helpers are also
 * exported for Jest (tests/js/admin/offer-pointer.test.js). The copy arrives as plain text and
 * every node is built with jQuery's `.text()` / `.attr()`, never by joining strings into HTML.
 */
( function ( root, factory ) {
	var api = factory();

	if ( typeof module === 'object' && module.exports ) {
		module.exports = api;
	}

	if ( root && root.jQuery ) {
		api.boot( root, root.jQuery );
	}
} )( typeof window !== 'undefined' ? window : null, function () {
	/** Added to the pointer so its own rules can reach it. */
	var POINTER_CLASS = 'wp-pointer nx-offer-pointer';

	function read( object, key, fallback ) {
		return object && object[ key ] !== undefined && object[ key ] !== null
			? object[ key ]
			: fallback;
	}

	/**
	 * The pointer's heading and copy, as an HTML string of escaped text.
	 *
	 * @param {Function} $    jQuery.
	 * @param {Object}   data `window.nxOfferPointer`.
	 * @return {string} HTML.
	 */
	function pointerContent( $, data ) {
		return $( '<div>' )
			.append(
				$( '<h3>' ).text( read( data, 'title', '' ) ),
				$( '<p>' ).text( read( data, 'text', '' ) )
			)
			.html();
	}

	/**
	 * The action row: the campaign button, then Dismiss.
	 *
	 * WordPress wraps what `buttons` returns in `.wp-pointer-buttons` with `.wrap()`, which wraps
	 * each element of a set on its own, so this returns one element holding both.
	 *
	 * @param {Function} $       jQuery.
	 * @param {Object}   data    `window.nxOfferPointer`.
	 * @param {Function} onClose Closes the pointer.
	 * @return {Object} jQuery element.
	 */
	function pointerButtons( $, data, onClose ) {
		return $( '<div class="nx-offer-pointer__actions">' ).append(
			$( '<a class="button button-primary">' )
				.attr( {
					href: read( data, 'url', '#' ),
					target: '_blank',
					rel: 'noopener noreferrer',
				} )
				.text( read( data, 'button', '' ) ),
			$( '<a class="close" href="#" role="button">' )
				.text( read( data, 'dismiss', '' ) )
				.on( 'click', function ( event ) {
					event.preventDefault();
					onClose();
				} )
		);
	}

	/**
	 * @param {Function} $         jQuery.
	 * @param {Object}   data      `window.nxOfferPointer`.
	 * @param {Function} onDismiss Records the dismissal.
	 * @return {Object} Options for `$.fn.pointer()`.
	 */
	function pointerOptions( $, data, onDismiss ) {
		return {
			content: pointerContent( $, data ),
			pointerClass: POINTER_CLASS,
			position: {
				edge: read( data, 'edge', 'left' ) === 'right' ? 'right' : 'left',
				align: 'center',
			},
			buttons: function ( event, handoff ) {
				return pointerButtons( $, data, function () {
					handoff.element.pointer( 'close' );
				} );
			},
			// WordPress calls this whenever the pointer closes; Dismiss is the only way to close it.
			close: onDismiss,
		};
	}

	/**
	 * Where the pointer sits relative to the menu link it points at, read once after WordPress
	 * has placed it: the gap from the link's top-left corner to the pointer's.
	 *
	 * @param {{top: number, left: number}} targetRect  The link's viewport box.
	 * @param {{top: number, left: number}} pointerRect The pointer's viewport box.
	 * @return {{top: number, left: number}} Offset.
	 */
	function pointerOffset( targetRect, pointerRect ) {
		return {
			top: read( pointerRect, 'top', 0 ) - read( targetRect, 'top', 0 ),
			left: read( pointerRect, 'left', 0 ) - read( targetRect, 'left', 0 ),
		};
	}

	/**
	 * The pointer's viewport position for where the menu link is now.
	 *
	 * @param {{top: number, left: number}} targetRect The link's viewport box.
	 * @param {{top: number, left: number}} offset     From pointerOffset().
	 * @return {{top: number, left: number}} Position for `position: fixed`, in whole pixels.
	 */
	function pointerPosition( targetRect, offset ) {
		return {
			top: Math.round( read( targetRect, 'top', 0 ) + read( offset, 'top', 0 ) ),
			left: Math.round( read( targetRect, 'left', 0 ) + read( offset, 'left', 0 ) ),
		};
	}

	/**
	 * Whether the menu link is on screen to be pointed at. Under 783px the admin menu is off
	 * canvas until it is opened, and a folded or hidden item has no box.
	 *
	 * @param {{width: number, height: number, right: number}} targetRect The link's viewport box.
	 * @return {boolean} True when the link has a box inside the window's left edge.
	 */
	function isTargetVisible( targetRect ) {
		return (
			read( targetRect, 'width', 0 ) > 0 &&
			read( targetRect, 'height', 0 ) > 0 &&
			read( targetRect, 'right', 0 ) > 0
		);
	}

	/**
	 * Open the pointer and keep it beside the menu link.
	 *
	 * @param {Window}   win The window; reads `win.nxOfferPointer`.
	 * @param {Function} $   jQuery, with WordPress's `wp-pointer` widget.
	 * @return {void}
	 */
	function boot( win, $ ) {
		var data = win.nxOfferPointer;

		if ( ! data || ! $ || ! $.fn || ! $.fn.pointer ) {
			return;
		}

		$( function () {
			var $target = $( data.target );

			// A filtered menu has no item to point at: show nothing.
			if ( ! $target.length ) {
				return;
			}

			var open = true;

			$target
				.pointer(
					pointerOptions( $, data, function () {
						open = false;
						$.post( data.ajaxUrl, {
							action: data.action,
							nonce: data.nonce,
						} );
					} )
				)
				.pointer( 'open' );

			var target = $target[ 0 ];
			var widget = $target.pointer( 'widget' )[ 0 ];

			if ( ! widget ) {
				return;
			}

			// WordPress has just placed it beside the link: remember that gap, then keep it.
			var offset = pointerOffset(
				target.getBoundingClientRect(),
				widget.getBoundingClientRect()
			);

			function place() {
				if ( ! open ) {
					return;
				}

				var rect = target.getBoundingClientRect();

				// No link on screen (the off-canvas menu on a narrow window): nothing to point at.
				if ( ! isTargetVisible( rect ) ) {
					widget.style.display = 'none';
					return;
				}

				var position = pointerPosition( rect, offset );
				widget.style.position = 'fixed';
				widget.style.top = position.top + 'px';
				widget.style.left = position.left + 'px';
				widget.style.display = '';
			}

			var frame = 0;
			function schedule() {
				if ( frame ) {
					return;
				}
				frame = win.requestAnimationFrame( function () {
					frame = 0;
					place();
				} );
			}

			place();
			win.addEventListener( 'scroll', schedule, { passive: true } );
			win.addEventListener( 'resize', schedule );
			// Folding the menu, or opening it on a narrow window, moves the link without a scroll.
			$( win.document ).on( 'wp-collapse-menu wp-menu-state-set', schedule );
			$( '#wp-admin-bar-menu-toggle' ).on( 'click', function () {
				win.setTimeout( schedule, 50 );
			} );
		} );
	}

	return {
		POINTER_CLASS: POINTER_CLASS,
		pointerContent: pointerContent,
		pointerButtons: pointerButtons,
		pointerOptions: pointerOptions,
		pointerOffset: pointerOffset,
		pointerPosition: pointerPosition,
		isTargetVisible: isTargetVisible,
		boot: boot,
	};
} );
