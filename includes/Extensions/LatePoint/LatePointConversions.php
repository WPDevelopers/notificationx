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

    /**
     * Build the notification payload for one booking.
     *
     * Explicitly hand-written. Never use $booking->get_data_vars() or
     * get_first_level_data_vars() — they include customer email, phone, the
     * free-text customer_comment, and manage_booking_for_{customer,agent} URLs,
     * which are bearer credentials allowing anyone to view, reschedule, or
     * cancel the booking with no login.
     *
     * @param \OsBookingModel $booking
     * @return array|false
     */
    protected function build_entry_data( $booking ) {
        if ( empty( $booking ) || empty( $booking->id ) ) {
            return false;
        }

        $customer = $booking->customer;
        $service  = $booking->service;

        // LatePoint returns an EMPTY MODEL, never null, when the related row is
        // missing — so check a property, not the object.
        if ( empty( $customer ) || empty( $customer->id ) ) {
            return false;
        }
        if ( empty( $service ) || empty( $service->id ) ) {
            return false;
        }

        // Hidden or inactive services still generate bookings via admin and the
        // abilities API. Publicly advertising a deliberately hidden service
        // ("VIP Private Consultation") is a real failure, not a cosmetic one.
        if ( method_exists( $service, 'is_hidden' ) && $service->is_hidden() ) {
            return false;
        }

        $first_name = ! empty( $customer->first_name ) ? trim( $customer->first_name ) : '';
        $last_name  = ! empty( $customer->last_name ) ? trim( $customer->last_name ) : '';
        if ( '' === $first_name && '' === $last_name ) {
            return false;
        }

        $timestamp = $this->parse_created_at( $booking );
        if ( false === $timestamp ) {
            return false;
        }

        $data = [
            'name'        => trim( $first_name . ' ' . ( '' !== $last_name ? mb_substr( $last_name, 0, 1 ) . '.' : '' ) ),
            'first_name'  => $first_name,
            'last_name'   => $last_name,
            'email'       => ! empty( $customer->email ) ? $customer->email : '',
            // Always the real service name. Masking is per-campaign, so it happens
            // at display time (Task 7), not here.
            'title'       => ! empty( $service->name ) ? $service->name : __( 'an appointment', 'notificationx' ),
            'service_id'  => (int) $service->id,
            'booking_id'  => (int) $booking->id,
            'status'      => ! empty( $booking->status ) ? $booking->status : '',
            'timestamp'   => $timestamp,
            'attendees'   => ! empty( $booking->total_attendees ) ? (int) $booking->total_attendees : 1,
            'dedupe_hash' => $this->dedupe_hash( $booking ),
        ];

        // LATEPOINT_ANY_AGENT makes get_agent_full_name() return the literal
        // string "Any Available Agent", which reads as broken in a popup.
        if ( ! empty( $booking->agent_id ) && ( ! defined( 'LATEPOINT_ANY_AGENT' ) || LATEPOINT_ANY_AGENT !== $booking->agent_id ) ) {
            $agent_name = $booking->get_agent_full_name();
            if ( ! empty( $agent_name ) ) {
                $data['agent_name'] = $agent_name;
            }
        }

        return $data;
    }

    /**
     * Booking creation time as a UTC unix timestamp.
     *
     * Uses the raw created_at column, which OsModel writes via
     * now_datetime_in_format() with the timezone hard-forced to UTC.
     * Deliberately avoids get_nice_created_at(), which fatals on PHP 8 when
     * created_at is null, and format_created_datetime_rfc3339(), which silently
     * substitutes "now" for bad data.
     *
     * @param \OsBookingModel $booking
     * @return int|false
     */
    protected function parse_created_at( $booking ) {
        if ( empty( $booking->created_at ) ) {
            return false;
        }
        $dt = \DateTime::createFromFormat( 'Y-m-d H:i:s', $booking->created_at, new \DateTimeZone( 'UTC' ) );
        if ( ! $dt ) {
            return false;
        }
        return $dt->getTimestamp();
    }

    /**
     * Identity of the appointment itself, independent of its booking row id.
     *
     * The payment-gateway return and the provider webhook race on the same order
     * intent and can each produce a DISTINCT booking row for the same
     * appointment, so deduping on booking id alone is not sufficient.
     *
     * @param \OsBookingModel $booking
     * @return string
     */
    protected function dedupe_hash( $booking ) {
        $start = '';
        if ( method_exists( $booking, 'get_start_datetime' ) ) {
            $dt = $booking->get_start_datetime( 'UTC' );
            if ( $dt instanceof \DateTimeInterface ) {
                $start = $dt->format( 'Y-m-d H:i:s' );
            }
        }
        // Fall back to the raw columns. start_datetime_utc is NULL for midnight
        // bookings, because start_time is minutes-from-midnight and empty(0) is true.
        if ( '' === $start ) {
            $start = (string) $booking->start_date . '|' . (string) $booking->start_time;
        }
        return md5( implode( '|', [
            (string) $booking->customer_id,
            (string) $booking->service_id,
            $start,
        ] ) );
    }
}
