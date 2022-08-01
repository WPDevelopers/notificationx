<?php

namespace PriyoMukul\WPNotice;

use PriyoMukul\WPNotice\Utils\Base;
use PriyoMukul\WPNotice\Utils\Storage;
use PriyoMukul\WPNotice\Utils\Helper;

use function property_exists;

class Notice extends Base {
	use Helper;

	private $app = null;

	private $id      = null;
	private $content = null;
	public  $dismiss = null;

	private $queue = [];

	/**
	 * @var int start
	 * @var int expire
	 * @var int recurrence Meaning this notice will appear after 10, 20 days.
	 * @var string scope
	 * @var array screens
	 * @var string type Notice type
	 * @var string capability
	 * @var bool dismissible
	 */
	private $options = [
		// 'start'       =>  192933, // timestamp
		// 'expire'       => 1029339, // timestamp
		'classes'     => '',
		'recurrence'  => false,
		'scope'       => 'user',
		'screens'     => null,
		'type'        => 'info',
		'capability'  => null,
		'dismissible' => false,
	];

	public function __construct( ...$args ){
		list( $id, $content, $options, $queue, $app ) = $args;

		$this->app     = $app;
		$this->id      = $id;
		$this->content = $content;
		$this->queue   = $queue;
		$this->options = wp_parse_args( $options, $this->options );

		$this->dismiss = new Dismiss( $this->id, $this->options, $this->app );

		if( ! isset( $queue[ $id ] ) ) {
			$queue[ $id ] = [];
			$_eligible_keys = ['start', 'expire', 'recurrence'];
			array_walk($options, function( $value, $key ) use( $id, &$queue, $_eligible_keys ) {
				if( in_array( $key, $_eligible_keys, true ) ) {
					$queue[ $id ][ $key ] = $value;
				}
			});

			$this->queue = $queue;
			$this->app->storage()->save( $queue ); // saved in queue
		} else {
			$this->options = wp_parse_args( $queue[ $id ], $this->options );
		}

		if( isset( $this->options['do_action'] ) ) {
			add_action( 'admin_init', [ $this, 'do_action' ] );
		}
	}

	public function do_action(){
		do_action( $this->options['do_action'], $this );
	}

	private function get_content(){
		if( is_callable( $this->content ) ) {
			ob_start();
			call_user_func( $this->content );
			return ob_get_clean();
		}
		return $this->content;
	}

	public function display( $force = false ){
		if ( ! $force && ! $this->show() ) {
			return;
		}

		$content = $this->get_content();
		if( empty( $content ) ) {
			return; // Return if notice is empty.
		}

		$links = $this->get_links();

		// Print the notice.
		printf(
			'<div style="display: grid; grid-template-columns: 50px 1fr; align-items: center;" id="%1$s" class="%2$s">%3$s<div class="wpnotice-content-wrapper">%4$s%5$s</div></div>',
			'wpnotice-' . esc_attr( $this->app->app ) . '-' . esc_attr( $this->id ), // The ID.
			esc_attr( $this->get_classes() ), // The classes.
			! empty( $content['thumbnail'] ) ? $this->get_thumbnail( $content['thumbnail'] ) : '',
			! empty( $content['html'] ) ? $content['html'] : $content,
			! empty( $links ) ? $this->links( $links ) : ''
		);
	}

	public function get_links(){
		return ! empty( $this->content['links'] ) ? $this->content['links'] : ( ! empty( $this->options['links'] ) ?  $this->options['links'] : []);
	}

	// 'later' => array(
	// 	'link' => 'https://wpdeveloper.com/review-notificationx',
	// 	'target' => '_blank',
	// 	'label' => __( 'Ok, you deserve it!', 'notificationx' ),
	// 	'icon_class' => 'dashicons dashicons-external',
	// ),
	public function links( $links ){
		$_attributes = '';
		$output = '<ul style="display: flex; width: 100%; align-items: center;" class="notice-links '. $this->app->app .'-notice-links">';
		foreach( $links as $_key => $link ) {
			$class  = ! empty( $link['class'] ) ? $link['class'] : '';

			if( ! empty( $link['attributes'] ) ) {
					$link['attributes']['target'] = '_top';
					$_attributes  = $this->attributes( $link['attributes'] );
					$link['link'] = '#';
				}

				$output .= '<li style="margin: 0 15px 0 0;" class="notice-link-item '. $class .'">';
					$output .= ! empty( $link['link'] ) ? '<a href="' . esc_url( $link['link'] ) . '" '. $_attributes .'>' : '';
						if ( isset( $link['icon_class'] ) ) {
							$output .= '<span style="margin-right: 5px" class="' . esc_attr( $link['icon_class'] ) . '"></span>';
						}
						$output .= $link['label'];
					$output .= ! empty( $link['link'] ) ? '</a>' : '';
				$output .= '</li>';
			}

		$output .= '</ul>';

		return $output;
	}

	public function attributes( $params = [] ){
		$_attr = [];
		$classname = 'dismiss-btn ';

		if( ! empty( $params['class'] ) ) {
			$classname .= $params['class'];
			unset( $params['class'] );
		}

		$_attr[] = 'class="' . esc_attr( $classname ) . '"';

		$_attr[] = 'target="_blank"';
		if( ! empty( $params ) ) {
			foreach( $params as $key => $value ) {
				$_attr[] = "$key='$value'";
			}
		}

		return \implode(' ', $_attr);
	}

	public function url( $params = [] ){
		$nonce = wp_create_nonce( 'wpnotice_dismiss_notice_' . $this->id );

		return esc_url( add_query_arg( [
			'action' => 'wpnotice_dismiss_notice',
			'id'     => $this->id,
			'nonce'  => $nonce,
		], admin_url( '/' ) ) );
	}
	/**
	 * Get the notice classes.
	 *
	 * @access public
	 * @since 1.0
	 * @return string
	 */
	public function get_classes() {
		$classes = [ 'wpnotice-wrapper notice', $this->app->app ];

		// Add the class for notice-type.
		$classes[] = $this->options['classes'];
		$classes[] = 'notice-' . $this->options['type'];
		$classes[] = 'notice-' . $this->app->app . '-' . $this->id;

		if( $this->options['dismissible'] ) {
			$classes[] = 'is-dismissible';
		}

		// Combine classes to a string.
		return implode( ' ', $classes );
	}

	/**
	 * Determine if the notice should be shown or not.
	 *
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	public function show() {
		// External Condition Check
		if( isset( $this->options['display_if'] ) && ! $this->options['display_if'] ) {
			return false;
		}
		// Don't show if the user doesn't have the required capability.
		if ( ! is_null( $this->options['capability'] ) && ! current_user_can( $this->options['capability'] ) ) {
			return false;
		}

		// Don't show if we're not on the right screen.
		if ( ! $this->is_screen() ) {
			return false;
		}

		// Don't show if notice has been dismissed.
		if ( $this->dismiss->is_dismissed() ) {
			return false;
		}

		// Start and Expiration Check.
		if( $this->time() <= $this->options['start'] ) {
			return false;
		}

		if( $this->is_expired() ) {
			if( $this->options['recurrence'] ) {
				$_recurrence = intval( $this->options['recurrence'] );
				$this->queue[ $this->id ]['start'] = $this->strtotime( "+$_recurrence days" );
				$this->queue[ $this->id ]['expire'] = $this->strtotime( "+". ($_recurrence + 3) ." days" );
				$this->app->storage()->save( $this->queue );
			}

			return false;
		}

		return true;
	}

	/**
	 * Evaluate if we're on the right place depending on the "screens" argument.
	 *
	 * @access private
	 * @since 1.0
	 * @return bool
	 */
	private function is_screen() {
		// If screen is empty we want this shown on all screens.
		if ( empty( $this->options['screens'] ) ) {
			return true;
		}

		// Make sure the get_current_screen function exists.
		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		/** @var \WP_Screen $current_screen */
		$current_screen = get_current_screen();
		return ( in_array( $current_screen->id, $this->options['screens'], true ) );
	}

	public function is_expired(){
		if( isset( $this->options['expire'] ) && $this->time() >= $this->options['expire'] ) {
			return true;
		}

		return false;
	}

	public function __call( $name, $args ){
		if( property_exists( $this, $name ) ) {
			return $this->{$name}[ $args[0] ];
		}
	}

	public function get_thumbnail( $image ) {
		$output      = '<div style="padding: 10px 10px 10px 0px; box-sizing: border-box;" class="wpnotice-thumbnail-wrapper">';
			$output .= '<img style="max-width: 100%;" src="' . esc_url( $image ) . '">';
		$output     .= '</div>';
        return wp_kses_post( $output );
    }
}