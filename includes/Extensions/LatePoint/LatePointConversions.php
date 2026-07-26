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
use NotificationX\Core\PostType;
use NotificationX\Core\Modules;
use NotificationX\Admin\Entries;

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

    /**
     * Booking statuses captured when a campaign has not chosen its own.
     *
     * Mirrored by the 'default' of the latepoint_booking_status field in
     * GlobalFields — keep the two in sync.
     */
    const DEFAULT_STATUSES = [ 'approved', 'completed' ];

    /**
     * Booking IDs captured during this request, awaiting flush.
     *
     * @var array<int, \OsBookingModel>
     */
    protected $buffer = [];

    public function __construct() {
        parent::__construct();

        // initialize() only calls init()/admin_actions()/public_actions() when
        // is_active(false) is true, which requires class_exists('LatePoint') —
        // so once LatePoint is deactivated nothing would ever (re)hook
        // nx_latepoint_reconcile, and its recurring cron event would linger in
        // the cron option forever. Registering here instead mirrors
        // initialize()'s OWN gate (Modules::is_enabled), which does not depend
        // on LatePoint being active, so reconcile() keeps firing — and its
        // ! class_exists() guard stays reachable to unschedule itself — for as
        // long as the module is turned on, active LatePoint or not.
        if ( Modules::get_instance()->is_enabled( $this->module ) ) {
            $this->schedule_reconciliation();
        }
    }

    public function init_extension() {
        $this->title        = __( 'LatePoint', 'notificationx' );
        $this->module_title = __( 'LatePoint', 'notificationx' );
    }

    public function init() {
        parent::init();
        // Priority 20: core registers at 10 (activities), 12 (process jobs) and
        // 15 (analytics). Let the booking row settle first.
        add_action( 'latepoint_booking_created', [ $this, 'buffer_booking' ], 20, 1 );
        add_action( 'latepoint_order_created', [ $this, 'flush_order' ], 20, 1 );
        // Editing an EXISTING order routes through latepoint_order_updated instead
        // of latepoint_order_created (see lib/controllers/orders_controller.php) —
        // without this, a booking added to an existing order is buffered and never
        // flushed. flush_order( $order = null ) already tolerates the 2-arg signature
        // of this hook because only 1 arg is requested here.
        add_action( 'latepoint_order_updated', [ $this, 'flush_order' ], 20, 1 );
        add_action( 'latepoint_booking_updated', [ $this, 'handle_booking_updated' ], 20, 2 );
        add_action( 'latepoint_booking_will_be_deleted', [ $this, 'handle_booking_deleted' ], 20, 1 );
        // Last-resort flush. Bundle scheduling fires latepoint_booking_created
        // from steps_helper::prepare_step_confirmation() inside a branch that
        // returns before convert_to_order() is ever reached, so no order hook
        // fires and the buffer would otherwise be filled and thrown away. The
        // empty-buffer guard in flush_order() keeps this a no-op on every
        // normal request, and flush_order() empties the buffer before writing,
        // so it cannot double-write after an order flush has already run.
        add_action( 'shutdown', [ $this, 'flush_order' ], 20 );
    }

    /**
     * Buffer rather than write.
     *
     * One recurring or cart checkout fires latepoint_booking_created N times
     * synchronously with no cap — a weekly-for-a-year recurrence is ~52 events in
     * a single request. Writing here would emit 52 popups.
     */
    public function buffer_booking( $booking ) {
        if ( empty( $booking ) || empty( $booking->id ) ) {
            return;
        }
        $this->buffer[ (int) $booking->id ] = $booking;
    }

    /**
     * Flush the request buffer as ONE notification.
     *
     * latepoint_order_created fires exactly once per checkout, always after every
     * booking_created for that order. It also fires for orders containing zero
     * bookings, hence the empty guard.
     */
    public function flush_order( $order = null ) {
        if ( empty( $this->buffer ) ) {
            return;
        }
        $bookings     = $this->buffer;
        $this->buffer = [];

        // Announce the first ELIGIBLE booking, not simply the first — the earliest
        // one may be on a hidden service, or have no usable customer name. Stop at
        // that one: build_entry_data() costs ~3 queries per booking and nothing
        // downstream can render a count of the rest (FrontEnd::filtered_data()
        // only forwards keys named by the campaign's notification template).
        foreach ( $bookings as $booking ) {
            $data = $this->build_entry_data( $booking );
            if ( false === $data ) {
                continue;
            }
            $this->store_entry( $data );
            return;
        }
    }

    /**
     * React to a status change.
     *
     * Signature is defensive: latepoint_booking_updated is fired with 2 args at
     * every call site, but activities_helper registers its own listener with 3,
     * so a third may appear.
     *
     * @param \OsBookingModel      $booking
     * @param \OsBookingModel|null $old_booking
     */
    public function handle_booking_updated( $booking, $old_booking = null, $initiated_by = '' ) {
        if ( empty( $booking ) || empty( $booking->id ) ) {
            return;
        }
        $old_status = ( ! empty( $old_booking ) && ! empty( $old_booking->status ) ) ? $old_booking->status : null;
        if ( null !== $old_status && $old_status === $booking->status ) {
            return;
        }

        $data = $this->build_entry_data( $booking );
        if ( false === $data ) {
            // No longer representable at all — drop whatever is on screen.
            $this->retract_booking( (int) $booking->id );
            return;
        }
        // store_entry() retracts before writing, so if nx_can_entry_latepoint
        // rejects the new status the stale entry is still removed.
        $this->store_entry( $data );
    }

    /**
     * Remove a booking's notifications.
     *
     * Hooked to latepoint_booking_will_be_deleted rather than
     * latepoint_booking_deleted: deleting an ORDER fires will_be_deleted for each
     * of its bookings but never fires deleted, so listening only to the latter
     * leaves orphaned popups advertising bookings that no longer exist.
     *
     * @param int $booking_id
     */
    public function handle_booking_deleted( $booking_id ) {
        $this->retract_booking( (int) $booking_id );
    }

    /**
     * Delete every entry for one booking, across all campaigns using this source.
     *
     * delete_notification() is always called with BOTH arguments because its
     * source predicate is commented out upstream (see issue #142) — passing an
     * entry_key alone would delete matching keys belonging to other sources.
     *
     * @param int $booking_id
     */
    protected function retract_booking( $booking_id ) {
        if ( empty( $booking_id ) ) {
            return;
        }
        $key   = 'latepoint_' . (int) $booking_id;
        $posts = PostType::get_instance()->get_posts([ 'source' => $this->id ]);
        if ( empty( $posts ) ) {
            return;
        }
        foreach ( $posts as $post ) {
            if ( empty( $post['nx_id'] ) ) {
                continue;
            }
            $this->delete_notification( $key, $post['nx_id'] );
        }
    }

    /**
     * Write one entry, replacing any existing entry for the same booking.
     *
     * NotificationX has no upsert: writes are INSERT-only and there is no unique
     * constraint, so re-saving would duplicate. Delete first, then re-insert —
     * the pattern SureCart and FluentCart use. This makes the write idempotent,
     * which is what keeps a booking that is both buffered and status-changed in
     * one request from producing two rows.
     */
    protected function store_entry( array $data ) {
        $booking_id = (int) $data['booking_id'];
        $this->retract_booking( $booking_id );

        $this->save([
            'source'    => $this->id,
            'entry_key' => 'latepoint_' . $booking_id,
            'data'      => $data,
        ], true );
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
        add_filter( 'nx_link_types', [ $this, 'link_types' ] );
    }

    /**
     * Display-time filters.
     *
     * nx_before_metabox_load (where init_fields() is hooked, see initialize())
     * is fired solely by GlobalFields::tabs() while building the admin
     * builder's field schema — it never fires on the public site. Registering
     * booking_link() and mask_service_name() there left them dead on the
     * frontend: a booking's real service name still leaked through the
     * "Hide Service Name" toggle, and the configured booking-page link was
     * never applied. public_actions() runs on every request once the module
     * is active (see Extension::initialize()), admin or not, so it is the
     * correct place for filters that affect display output.
     */
    public function public_actions() {
        parent::public_actions();
        add_filter( "nx_notification_link_{$this->id}", [ $this, 'booking_link' ], 10, 3 );
        add_filter( "nx_filtered_entry_{$this->id}", [ $this, 'mask_service_name' ], 10, 2 );
    }

    /**
     * Apply the per-campaign "Hide Service Name" toggle at display time.
     *
     * This cannot happen at capture time: Extension::save() writes one payload
     * shared by every campaign using this source, so two campaigns with different
     * toggle settings must both be served from the same stored entry.
     */
    public function mask_service_name( $entry, $settings ) {
        if ( ! empty( $settings['latepoint_hide_service_name'] ) ) {
            $entry['title'] = __( 'an appointment', 'notificationx' );
        }
        return $entry;
    }

    /**
     * Notification thumbnail.
     *
     * Both candidate URLs are resolved once at capture time by
     * build_entry_data(), never here: this filter runs per entry over up to
     * cache_limit (100) rows on every frontend pageview, BEFORE display_last
     * slicing, and LatePoint's models are uncached — re-deriving the service and
     * customer here cost hundreds of queries per pageview. SureCart and
     * FluentCart store the URL on the entry for the same reason.
     *
     * Order: service image, then the customer avatar. Mutates and returns
     * $image_data rather than replacing it, so FrontEnd::get_image_url()'s
     * already-populated 'alt' (the customer name) and 'classes' survive —
     * returning a fresh array dropped the alt attribute from the rendered <img>.
     * If neither tier qualifies, fall through so that method's own
     * default→gravatar chain still runs.
     */
    public function notification_image( $image_data, $data, $settings ) {
        // "Show Default Image" means the site owner picked one image for every
        // popup and get_image_url() has already resolved it. Peers never
        // override that choice.
        if ( ! empty( $settings['show_default_image'] ) ) {
            return $image_data;
        }

        // "Hide Service Name" is a privacy toggle, not a text tweak: publishing
        // the service's own artwork identifies it just as plainly as its name
        // would. Fall back to the customer avatar instead.
        $hide_service = ! empty( $settings['latepoint_hide_service_name'] );

        if ( ! $hide_service && ! empty( $data['service_image'] ) ) {
            $image_data['url'] = $data['service_image'];
        } elseif ( ! empty( $data['customer_avatar'] ) ) {
            $image_data['url'] = $data['customer_avatar'];
        }

        return $image_data;
    }

    /**
     * Adds an option to the Link Type field in the Content tab.
     *
     * Without this the dropdown offers only "None" for this source, so the
     * campaign can never opt in to the booking-page link below.
     *
     * @param array $options
     * @return array
     */
    public function link_types( $options ) {
        return GlobalFields::get_instance()->normalize_fields([
            'booking_page' => __( 'Booking Page', 'notificationx' ),
        ], 'source', $this->id, $options );
    }

    /**
     * Notification link.
     *
     * LatePoint stores services in custom tables rather than as posts, so there
     * is no per-service permalink. The site owner supplies the page hosting the
     * booking form. Only applied when the campaign actually selected the
     * Booking Page link type — FrontEnd::link_url() blanks the link for
     * link_type 'none' immediately before firing this filter, and restoring it
     * unconditionally overrode the setting.
     */
    public function booking_link( $link, $post, $entry ) {
        if ( ! empty( $post['latepoint_booking_page_url'] ) && ! empty( $post['link_type'] ) && 'booking_page' === $post['link_type'] ) {
            return esc_url_raw( $post['latepoint_booking_page_url'] );
        }
        return $link;
    }

    public function fallback_data( $data, $entry ) {
        $data['name']            = __( 'Someone', 'notificationx' );
        $data['first_name']      = __( 'Someone', 'notificationx' );
        $data['last_name']       = '';
        $data['anonymous_title'] = __( 'an appointment', 'notificationx' );
        if ( empty( $data['title'] ) ) {
            $data['title'] = __( 'an appointment', 'notificationx' );
        }
        return $data;
    }

    public function admin_actions() {
        parent::admin_actions();
        add_filter( "nx_can_entry_{$this->id}", [ $this, 'check_booking_eligibility' ], 10, 3 );
    }

    /**
     * Ensure the daily reconciliation event — and its handler — exist.
     *
     * Called once, unconditionally, from __construct() (see the module-enabled
     * gate there); WordPress dedupes identical hook+callback+priority
     * registrations, so this is safe to call more than once.
     *
     * The handler is registered unconditionally but the event is only (re)created
     * while LatePoint is actually present. Doing both unconditionally made
     * reconcile()'s wp_clear_scheduled_hook() pointless: with LatePoint
     * uninstalled the cron fired, cleared itself, and the very next pageload
     * scheduled it again, forever.
     */
    public function schedule_reconciliation() {
        add_action( 'nx_latepoint_reconcile', [ $this, 'reconcile' ] );
        if ( $this->class_exists() && ! wp_next_scheduled( 'nx_latepoint_reconcile' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'nx_latepoint_reconcile' );
        }
    }

    /**
     * Drop entries whose booking is gone or no longer eligible.
     *
     * Required, not defensive polish. It is the only thing covering:
     *  - customer deletion, which cascades to the customer's bookings with NO
     *    hooks at all (the GDPR erasure path — lib/models/customer_model.php)
     *  - the abilities/MCP-AI layer, which deletes bookings and changes their
     *    status directly with no hooks at all (e.g.
     *    lib/abilities/bookings/delete-booking.php calls $booking->delete())
     *  - a general backstop for any booking whose status drifted out of the
     *    allowlist without a hook firing
     *
     * Order deletion is NOT one of these gaps: it fires
     * latepoint_booking_will_be_deleted for every booking on the order
     * (lib/models/order_model.php), and init() already hooks exactly that via
     * handle_booking_deleted() — that path is covered in realtime.
     */
    public function reconcile() {
        if ( ! $this->class_exists() ) {
            // No per-module-disable cron cleanup pattern exists elsewhere in
            // includes/ to follow, so this stops an uninstalled LatePoint
            // from rescheduling itself.
            wp_clear_scheduled_hook( 'nx_latepoint_reconcile' );
            return;
        }
        $posts = PostType::get_instance()->get_posts([ 'source' => $this->id ]);
        if ( empty( $posts ) ) {
            return;
        }

        foreach ( $posts as $post ) {
            if ( empty( $post['nx_id'] ) ) {
                continue;
            }
            $entries = Entries::get_instance()->get_entries([
                'nx_id'  => $post['nx_id'],
                'source' => $this->id,
            ]);
            if ( empty( $entries ) ) {
                continue;
            }

            $allowed = ! empty( $post['latepoint_booking_status'] )
                ? (array) $post['latepoint_booking_status']
                : self::DEFAULT_STATUSES;

            foreach ( $entries as $entry ) {
                if ( empty( $entry['booking_id'] ) ) {
                    continue;
                }
                $booking_id = (int) $entry['booking_id'];
                $booking    = new \OsBookingModel( $booking_id );

                // Gone entirely — deleted booking, deleted order, or erased customer.
                if ( empty( $booking->id ) ) {
                    $this->delete_notification( 'latepoint_' . $booking_id, $post['nx_id'] );
                    continue;
                }
                // Still present but no longer displayable.
                if ( empty( $booking->status ) || ! in_array( $booking->status, $allowed, true ) ) {
                    $this->delete_notification( 'latepoint_' . $booking_id, $post['nx_id'] );
                    continue;
                }
                // Customer erased while the booking row survives.
                $customer = $booking->customer;
                if ( empty( $customer ) || empty( $customer->id ) ) {
                    $this->delete_notification( 'latepoint_' . $booking_id, $post['nx_id'] );
                    continue;
                }
                // Service deleted, or hidden since capture. Mirrors the
                // capture-time rejection in build_entry_data(): an admin who
                // hides a service to stop advertising it must also stop the
                // popups that are already advertising it.
                $service = $booking->service;
                if ( empty( $service ) || empty( $service->id )
                    || ( method_exists( $service, 'is_hidden' ) && $service->is_hidden() ) ) {
                    $this->delete_notification( 'latepoint_' . $booking_id, $post['nx_id'] );
                }
            }
        }
    }

    public function saved_post( $post, $data, $nx_id ) {
        $this->delete_notification( null, $nx_id );
        $this->get_notification_ready( $data );
    }

    public function get_notification_ready( $post = [] ) {
        $bookings = $this->get_bookings( $post );
        if ( empty( $bookings ) ) {
            return;
        }
        $entries = [];
        foreach ( $bookings as $booking ) {
            $data = $this->build_entry_data( $booking );
            if ( false === $data ) {
                continue;
            }
            $entries[] = [
                'nx_id'     => $post['nx_id'],
                'source'    => $this->id,
                'entry_key' => 'latepoint_' . $data['booking_id'],
                'data'      => $data,
            ];
        }
        if ( ! empty( $entries ) ) {
            $this->update_notifications( $entries );
        }
    }

    /**
     * Recent bookings for backfill.
     *
     * Bounded by display_last, following SureCart and FluentCart — NOT Tutor's
     * unbounded -1, which pulls every row only for Limiter to truncate it
     * non-deterministically at cache_limit.
     *
     * Uses OsModel's query-builder API (verified against
     * lib/models/model.php): where() merges conditions by key, so an array
     * value (e.g. 'status' => [...]) is rendered as an escaped IN (...) list
     * and a ' >' operator suffix on the key (e.g. 'created_at >') is parsed
     * into a parameterized comparison — both used below. OsModel has no
     * limit() method though; only set_limit() exists, so that is used in
     * place of the assumed limit().
     */
    protected function get_bookings( $post = [] ) {
        if ( ! $this->class_exists() ) {
            return [];
        }
        $limit   = ! empty( $post['display_last'] ) ? (int) $post['display_last'] : 10;
        $allowed = ! empty( $post['latepoint_booking_status'] )
            ? (array) $post['latepoint_booking_status']
            : self::DEFAULT_STATUSES;

        $model = new \OsBookingModel();
        $model->where( [ 'status' => $allowed ] );
        if ( ! empty( $post['display_from'] ) ) {
            $from = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $post['display_from'] . ' days' ) );
            $model->where( [ 'created_at >' => $from ] );
        }
        $model->order_by( 'created_at desc' );
        $model->set_limit( $limit );

        $results = $model->get_results_as_models();
        // get_results_as_models() UN-ARRAYS its own return when the limit is 1
        // (lib/models/model.php: `if ( $this->limit == 1 && isset( $models[0] ) )
        // { $models = $models[0]; }`), so a bare is_array() test discarded the
        // single row and made display_last = 1 backfill nothing at all.
        if ( is_array( $results ) ) {
            return $results;
        }
        return $results ? [ $results ] : [];
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
            : self::DEFAULT_STATUSES;
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
            'name'       => trim( $first_name . ' ' . ( '' !== $last_name ? mb_substr( $last_name, 0, 1 ) . '.' : '' ) ),
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => ! empty( $customer->email ) ? $customer->email : '',
            // Always the real service name. Masking is per-campaign, so it happens
            // at display time in mask_service_name(), not here.
            'title'      => ! empty( $service->name ) ? $service->name : __( 'an appointment', 'notificationx' ),
            'service_id' => (int) $service->id,
            'booking_id' => (int) $booking->id,
            'status'     => ! empty( $booking->status ) ? $booking->status : '',
            'timestamp'  => $timestamp,
        ];

        // Resolve the thumbnail candidates HERE, once per booking, because both
        // models are already loaded — notification_image() then reads them off
        // the entry instead of re-querying LatePoint for every cached row on
        // every frontend pageview. See notification_image().
        if ( method_exists( $service, 'get_selection_image_url' ) ) {
            $service_image = $service->get_selection_image_url();
            // get_selection_image_url() falls back to LatePoint's own bundled
            // images/service-image.png placeholder, which reads as broken art.
            if ( ! empty( $service_image ) && false === strpos( $service_image, 'service-image.png' ) ) {
                $data['service_image'] = $service_image;
            }
        }
        if ( method_exists( $customer, 'get_avatar_url' ) ) {
            $avatar = $customer->get_avatar_url();
            // Same story: OsCustomerHelper::get_avatar_url() never returns empty,
            // it returns images/default-avatar.jpg — the same grey silhouette on
            // every popup, which reads as fake.
            if ( ! empty( $avatar ) && false === strpos( $avatar, 'default-avatar.jpg' ) ) {
                $data['customer_avatar'] = $avatar;
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
}
