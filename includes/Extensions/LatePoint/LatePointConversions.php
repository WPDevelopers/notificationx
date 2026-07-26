<?php
/**
 * LatePoint Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\LatePoint;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;

/**
 * LatePoint Extension Class
 * @method static LatePointConversions get_instance($args = null)
 */
class LatePointConversions extends Extension {
    use GetInstance;

    public $priority        = 12;
    public $id              = 'latepoint';
    public $doc_link        = 'https://notificationx.com/docs/';
    public $types           = 'conversions';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/latepoint.png';
    public $module          = 'modules_latepoint';
    public $module_priority = 40;
    // 'LatePoint' is declared at file load. LATEPOINT_VERSION is only defined
    // during init, so it is not a safe presence probe.
    public $class           = 'LatePoint';

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __( 'LatePoint', 'notificationx' );
        $this->module_title = __( 'LatePoint', 'notificationx' );
    }

    public function source_error_message( $messages ) {
        if ( ! $this->class_exists() ) {
            $url = admin_url( 'plugin-install.php?s=latepoint&tab=search&type=term' );
            $messages[ $this->id ] = [
                'message' => sprintf(
                    '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'LatePoint', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'html'  => true,
                'type'  => 'error',
                'rules' => Rules::is( 'source', $this->id ),
            ];
        }
        return $messages;
    }

    public function doc() {
        /* translators: %1$s: LatePoint plugin URL, %2$s: documentation URL */
        return sprintf(
            __( '<p>Make sure the <a target="_blank" href="%1$s">LatePoint plugin is installed &amp; configured</a> so NotificationX can read its booking data. For detailed guidelines, check the step-by-step <a target="_blank" href="%2$s">documentation</a>.</p>', 'notificationx' ),
            'https://wordpress.org/plugins/latepoint/',
            'https://notificationx.com/docs/'
        );
    }

    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_latepoint_booking_status', [ $this, 'booking_status_options' ], 11 );
    }

    public function admin_actions() {
        parent::admin_actions();
        add_filter( "nx_can_entry_{$this->id}", [ $this, 'check_booking_eligibility' ], 10, 3 );
    }

    public function booking_status_options( $options ) {
        $statuses = [
            'approved'        => __( 'Approved', 'notificationx' ),
            'completed'       => __( 'Completed', 'notificationx' ),
            'pending'         => __( 'Pending', 'notificationx' ),
            'payment_pending' => __( 'Payment Pending', 'notificationx' ),
            'cancelled'       => __( 'Cancelled', 'notificationx' ),
            'no_show'         => __( 'No Show', 'notificationx' ),
        ];
        return GlobalFields::get_instance()->normalize_fields( $statuses, 'source', $this->id, $options );
    }

    /**
     * Capture-time allowlist. Runs on the write path via nx_can_entry_latepoint,
     * so a rejected booking is never stored.
     */
    public function check_booking_eligibility( $return, $entry, $settings ) {
        $data = ! empty( $entry['data'] ) ? $entry['data'] : [];

        // Status must be explicitly allowed. LatePoint's status set is NOT a closed
        // enum — admins can define custom statuses — so this is an allowlist, never
        // a denylist.
        $allowed = ! empty( $settings['latepoint_booking_status'] )
            ? (array) $settings['latepoint_booking_status']
            : [ 'approved', 'completed' ];
        if ( empty( $data['status'] ) || ! in_array( $data['status'], $allowed, true ) ) {
            return false;
        }

        // A blank name renders as " just booked ". Names are optional in LatePoint —
        // validation is settings-driven and guests are supported.
        if ( empty( $data['name'] ) || '' === trim( $data['name'] ) ) {
            return false;
        }

        // Timestamp sanity. LatePoint silently substitutes "now" for unparseable
        // created_at values, which would otherwise pin a popup to "just booked"
        // permanently.
        if ( empty( $data['timestamp'] ) || ! is_numeric( $data['timestamp'] ) ) {
            return false;
        }
        if ( (int) $data['timestamp'] > ( time() + MINUTE_IN_SECONDS ) ) {
            return false;
        }

        return $return;
    }
}
