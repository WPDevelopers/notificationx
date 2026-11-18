<?php
/**
 * "Why isn't it showing?": the reasons a notification may not appear on the
 * site, each with a fix and a link to the setting.
 *
 * Read-only and admin-only. Every check reads the notification's saved
 * settings, its entries and the site's setup; nothing is written and no
 * visitor data is read. The checks follow the rules the front end applies
 * (FrontEnd::get_notifications_ids(), get_notifications(), the `/notice`
 * pipeline, the close markers in nxdev/notificationx/frontend/core/).
 *
 * @package NotificationX\Core
 */

namespace NotificationX\Core;

use NotificationX\Admin\Entries;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;
use NotificationX\NotificationX;

/**
 * @method static WhyNotShowing get_instance($args = null)
 *
 * @since x.x.x
 */
class WhyNotShowing {
    use GetInstance;

    /** It can't show anywhere until this is fixed. */
    const BLOCKED = 'blocked';
    /** It shows only for some visitors, pages, devices or times. */
    const LIMITED = 'limited';
    /** Worth knowing; not a reason by itself. */
    const NOTE = 'note';
    /** Checked, nothing wrong. */
    const OK = 'ok';

    /**
     * Sources that render without entries.
     */
    const WITHOUT_ENTRIES = [ 'press_bar', 'gdpr_notification', 'popup_notification', 'exit_intent_custom', 'flashing_tab' ];

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route(
            REST::_namespace(),
            '/why-not-showing/(?P<id>[\d]+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest' ],
                'permission_callback' => function () {
                    return current_user_can( 'edit_notificationx' );
                },
                'args'                => [
                    'id' => [
                        'validate_callback' => function ( $value ) {
                            return is_numeric( $value ) && (int) $value > 0;
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function rest( $request ) {
        $post = PostType::get_instance()->get_post( (int) $request['id'] );
        if ( empty( $post ) ) {
            return new \WP_Error( 'nx_not_found', __( 'Notification not found.', 'notificationx' ), [ 'status' => 404 ] );
        }
        $response = rest_ensure_response(
            [
                'id'      => (int) $post['nx_id'],
                'title'   => isset( $post['title'] ) ? (string) $post['title'] : '',
                'reasons' => $this->reasons( $post ),
                'browser' => $this->browser_keys( $post ),
            ]
        );
        $response->header( 'Cache-Control', 'no-store' );
        return $response;
    }

    /**
     * All reasons for one notification, most serious first.
     *
     * @param array $post Notification (PostType::get_post()).
     * @return array[] Each: key, level, title, detail, fix {label, url} or null.
     */
    public function reasons( $post ) {
        $reasons = [];
        foreach ( [ 'enabled', 'pro', 'module', 'plugin', 'schedule', 'data', 'pages', 'audience', 'devices', 'seen', 'timing', 'slot', 'cache' ] as $check ) {
            foreach ( (array) $this->{"check_$check"}( $post ) as $reason ) {
                $reasons[] = $reason;
            }
        }

        /**
         * Reasons from add-ons (NotificationX Pro) for their own sources and features.
         *
         * @since x.x.x
         * @param array[] $reasons Reasons (key, level, title, detail, fix).
         * @param array   $post    Notification.
         */
        $reasons = apply_filters( 'nx_why_not_showing_reasons', $reasons, $post );

        $order = [ self::BLOCKED => 0, self::LIMITED => 1, self::NOTE => 2, self::OK => 3 ];
        $clean = [];
        foreach ( (array) $reasons as $index => $reason ) {
            if ( empty( $reason['key'] ) || empty( $reason['title'] ) || ! isset( $order[ $reason['level'] ?? '' ] ) ) {
                continue;
            }
            $fix     = isset( $reason['fix']['url'], $reason['fix']['label'] ) ? [
                'label' => (string) $reason['fix']['label'],
                'url'   => esc_url_raw( $reason['fix']['url'] ),
            ] : null;
            $clean[] = [
                'key'    => (string) $reason['key'],
                'level'  => $reason['level'],
                'title'  => (string) $reason['title'],
                'detail' => isset( $reason['detail'] ) ? (string) $reason['detail'] : '',
                'fix'    => $fix,
                '_i'     => $index,
            ];
        }
        usort(
            $clean,
            function ( $a, $b ) use ( $order ) {
                return [ $order[ $a['level'] ], $a['_i'] ] <=> [ $order[ $b['level'] ], $b['_i'] ];
            }
        );
        return array_map(
            function ( $reason ) {
                unset( $reason['_i'] );
                return $reason;
            },
            $clean
        );
    }

    /**
     * A reason.
     */
    public static function reason( $key, $level, $title, $detail = '', $fix = null ) {
        return compact( 'key', 'level', 'title', 'detail', 'fix' );
    }

    /**
     * A fix that opens the builder on a tab.
     */
    public static function builder_fix( $post, $tab, $label ) {
        return [
            'label' => $label,
            'url'   => admin_url( 'admin.php?page=nx-edit&id=' . (int) $post['nx_id'] . '&tab=' . rawurlencode( $tab ) ),
        ];
    }

    protected function is_bar( $post ) {
        return 'press_bar' === ( $post['source'] ?? '' );
    }

    protected function extension( $post ) {
        $source  = $post['source'] ?? '';
        $factory = ExtensionFactory::get_instance();
        // ExtensionFactory::get() warns on an unknown key; ask only for known ones.
        if ( ! is_string( $source ) || '' === $source || ! isset( $factory->extension_classes[ $source ] ) ) {
            return null;
        }
        $ext = $factory->get( $source );
        return is_object( $ext ) ? $ext : null;
    }

    public function check_enabled( $post ) {
        if ( ! empty( $post['enabled'] ) ) {
            return [ self::reason( 'enabled', self::OK, __( 'Switched on', 'notificationx' ) ) ];
        }
        return [
            self::reason(
                'enabled',
                self::BLOCKED,
                __( 'It’s switched off', 'notificationx' ),
                __( 'Disabled notifications never show. Turn it on with the switch in its row.', 'notificationx' ),
                [
                    'label' => __( 'Open disabled notifications', 'notificationx' ),
                    'url'   => admin_url( 'admin.php?page=nx-admin&status=disabled' ),
                ]
            ),
        ];
    }

    public function check_pro( $post ) {
        $ext = $this->extension( $post );
        if ( ! $ext ) {
            return [
                self::reason(
                    'pro',
                    self::BLOCKED,
                    __( 'Its source isn’t available on this site', 'notificationx' ),
                    /* translators: %s: source id, e.g. live_visitors. */
                    sprintf( __( 'Its source (%s) isn’t registered here: it comes from NotificationX Pro, a newer version of it, or a feature that’s off. Install or update NotificationX Pro.', 'notificationx' ), (string) ( $post['source'] ?? '' ) ),
                    [
                        'label' => __( 'Open Plugins', 'notificationx' ),
                        'url'   => admin_url( 'plugins.php' ),
                    ]
                ),
            ];
        }
        if ( ! NotificationX::is_pro() && $ext && ! empty( $ext->is_pro ) ) {
            return [
                self::reason(
                    'pro',
                    self::BLOCKED,
                    __( 'It needs NotificationX Pro', 'notificationx' ),
                    /* translators: %s: source name, e.g. Zapier. */
                    sprintf( __( '%s is a Pro source, and NotificationX Pro isn’t active.', 'notificationx' ), $ext->title ),
                    [
                        'label' => __( 'Open Plugins', 'notificationx' ),
                        'url'   => admin_url( 'plugins.php' ),
                    ]
                ),
            ];
        }
        if ( NotificationX::is_type_unrenderable( $post['type'] ?? '' ) || NotificationX::is_theme_unrenderable( $post ) ) {
            return [
                self::reason(
                    'pro',
                    self::BLOCKED,
                    __( 'It needs NotificationX Pro, or a newer version of it', 'notificationx' ),
                    __( 'Its type or theme is drawn by NotificationX Pro, which is missing or too old on this site.', 'notificationx' ),
                    [
                        'label' => __( 'Open Plugins', 'notificationx' ),
                        'url'   => admin_url( 'plugins.php' ),
                    ]
                ),
            ];
        }
        return [ self::reason( 'pro', self::OK, __( 'Everything it needs from NotificationX is here', 'notificationx' ) ) ];
    }

    public function check_module( $post ) {
        $ext = $this->extension( $post );
        if ( $ext && ! empty( $ext->module ) && ! Modules::get_instance()->is_enabled( $ext->module ) ) {
            $title = $ext->module_title ?: $ext->title;
            return [
                self::reason(
                    'module',
                    self::BLOCKED,
                    /* translators: %s: module name, e.g. WooCommerce. */
                    sprintf( __( 'The %s module is off', 'notificationx' ), $title ),
                    __( 'Turn the module on in NotificationX → Settings → General → Modules.', 'notificationx' ),
                    [
                        'label' => __( 'Open Modules', 'notificationx' ),
                        'url'   => admin_url( 'admin.php?page=nx-settings&tab=tab-general' ),
                    ]
                ),
            ];
        }
        return [];
    }

    public function check_plugin( $post ) {
        $ext = $this->extension( $post );
        if ( $ext && ! $ext->class_exists() ) {
            return [
                self::reason(
                    'plugin',
                    self::BLOCKED,
                    /* translators: %s: plugin name, e.g. WooCommerce. */
                    sprintf( __( '%s isn’t active', 'notificationx' ), $ext->module_title ?: $ext->title ),
                    __( 'This notification gets its data from that plugin. Install and activate it.', 'notificationx' ),
                    [
                        'label' => __( 'Open Plugins', 'notificationx' ),
                        'url'   => admin_url( 'plugins.php' ),
                    ]
                ),
            ];
        }
        return [];
    }

    public function check_schedule( $post ) {
        $reasons = [];
        $now     = time();
        if ( ! empty( $post['updated_at'] ) && strtotime( $post['updated_at'] . ' UTC' ) > $now + 60 ) {
            $reasons[] = self::reason(
                'schedule',
                self::BLOCKED,
                __( 'Its start time is in the future', 'notificationx' ),
                /* translators: %s: date and time. */
                sprintf( __( 'It was saved with a time in the future and starts at %s. Save it again to start now.', 'notificationx' ), $this->date( strtotime( $post['updated_at'] . ' UTC' ) ) ),
                self::builder_fix( $post, 'source_tab', __( 'Open the notification', 'notificationx' ) )
            );
        }
        if ( $this->is_bar( $post ) ) {
            if ( ! empty( $post['schedule_type'] ) && class_exists( '\NotificationX\Types\NotificationBar' ) && \NotificationX\Types\NotificationBar::get_instance()->show_on_exclude( false, array_diff_key( $post, [ 'bar_reappearance' => 1 ] ) ) ) {
                $reasons[] = self::reason(
                    'schedule',
                    self::BLOCKED,
                    __( 'It’s outside its schedule right now', 'notificationx' ),
                    $this->schedule_words( $post ),
                    self::builder_fix( $post, 'customize_tab', __( 'Change the schedule', 'notificationx' ) )
                );
            } elseif ( ! empty( $post['schedule_type'] ) ) {
                $reasons[] = self::reason( 'schedule', self::LIMITED, __( 'It shows only during its schedule', 'notificationx' ), $this->schedule_words( $post ), self::builder_fix( $post, 'customize_tab', __( 'Change the schedule', 'notificationx' ) ) );
            }
            if ( ! empty( $post['enable_countdown'] ) && empty( $post['evergreen_timer'] ) && ! empty( $post['close_after_expire'] ) && ! empty( $post['countdown_end_date'] ) ) {
                $end = strtotime( (string) $post['countdown_end_date'] );
                if ( $end && $end < $now ) {
                    $reasons[] = self::reason(
                        'countdown',
                        self::BLOCKED,
                        __( 'Its countdown has ended', 'notificationx' ),
                        /* translators: %s: date and time. */
                        sprintf( __( 'The countdown ended on %s, and the bar closes when it ends.', 'notificationx' ), $this->date( $end ) ),
                        self::builder_fix( $post, 'content_tab', __( 'Change the countdown', 'notificationx' ) )
                    );
                }
            }
        }
        if ( empty( $reasons ) ) {
            $reasons[] = self::reason( 'schedule', self::OK, __( 'No schedule keeps it hidden now', 'notificationx' ) );
        }
        return $reasons;
    }

    protected function schedule_words( $post ) {
        $type = $post['schedule_type'];
        if ( 'daily' === $type ) {
            /* translators: 1: start time, 2: end time. */
            return sprintf( __( 'Every day from %1$s to %2$s (UTC).', 'notificationx' ), $this->time( $post['daily_from_time'] ?? '' ), $this->time( $post['daily_to_time'] ?? '' ) );
        }
        if ( 'weekly' === $type ) {
            $days = implode( ', ', array_map( 'ucfirst', (array) ( $post['weekly_days'] ?? [] ) ) );
            /* translators: 1: weekdays, 2: start time, 3: end time. */
            return sprintf( __( 'On %1$s from %2$s to %3$s (UTC).', 'notificationx' ), $days, $this->time( $post['weekly_from_time'] ?? '' ), $this->time( $post['weekly_to_time'] ?? '' ) );
        }
        $start = substr( (string) ( $post['custom_schedule']['startDate'] ?? '' ), 0, 10 );
        $end   = substr( (string) ( $post['custom_schedule']['endDate'] ?? '' ), 0, 10 );
        /* translators: 1: first date, 2: last date, 3: start time, 4: end time. */
        return sprintf( __( 'From %1$s to %2$s, %3$s to %4$s (UTC).', 'notificationx' ), $start, $end, $this->time( $post['custom_from_time'] ?? '' ), $this->time( $post['custom_to_time'] ?? '' ) );
    }

    protected function time( $value ) {
        $ts = strtotime( (string) $value );
        return $ts ? gmdate( 'H:i', $ts ) : '—';
    }

    protected function date( $timestamp ) {
        return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
    }

    /**
     * No data to show: the same pipeline as the public `notice` request.
     */
    public function check_data( $post ) {
        $source = $post['source'] ?? '';
        /**
         * Sources that render without entries (no "no data" check).
         *
         * @since x.x.x
         * @param string[] $sources Source ids.
         */
        $without = (array) apply_filters( 'nx_why_not_showing_without_entries', self::WITHOUT_ENTRIES );
        if ( in_array( $source, $without, true ) ) {
            return [];
        }

        if ( $this->has_blocker( $post ) ) {
            // Pro, its module or its plugin is missing: the data can't be judged.
            return [];
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- A count and a date, read-only, admin-only.
        $row   = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(*) AS total, MAX(created_at) AS newest FROM ' . Database::$table_entries . ' WHERE nx_id = %d', (int) $post['nx_id'] ), ARRAY_A );
        $total = (int) ( $row['total'] ?? 0 );
        $fix   = self::builder_fix( $post, 'source_tab', __( 'Check the source', 'notificationx' ) );

        if ( 0 === $total ) {
            return [
                self::reason(
                    'data',
                    self::BLOCKED,
                    __( 'There’s no data to show yet', 'notificationx' ),
                    __( 'NotificationX has nothing saved for it yet (for example, no sales, sign-ups or reviews from its source). It appears once there is. Regenerate it from the row menu to fetch past data, where the source allows it.', 'notificationx' ),
                    $fix
                ),
            ];
        }

        if ( empty( $post['enabled'] ) ) {
            return [
                self::reason(
                    'data',
                    self::OK,
                    /* translators: %d: number of entries. */
                    sprintf( _n( '%d entry saved', '%d entries saved', $total, 'notificationx' ), $total )
                ),
            ];
        }

        $shown = $this->shown_entries( $post );
        if ( $shown > 0 ) {
            /* translators: %d: number of entries. */
            return [ self::reason( 'data', self::OK, sprintf( _n( '%d entry to show', '%d entries to show', $shown, 'notificationx' ), $shown ) ) ];
        }

        $from = Helper::generate_time_string( $post );
        $newest = ! empty( $row['newest'] ) ? strtotime( $row['newest'] . ' UTC' ) : 0;
        if ( $from < time() - 30 && $newest && $newest < $from ) {
            return [
                self::reason(
                    'data',
                    self::BLOCKED,
                    __( 'Nothing in the chosen period', 'notificationx' ),
                    sprintf(
                        /* translators: 1: number of entries, 2: date of the newest entry, 3: start of the period. */
                        _n( 'It has %1$d entry, from %2$s, but it shows only entries since %3$s (“Display From The Last”).', 'It has %1$d entries, the newest from %2$s, but it shows only entries since %3$s (“Display From The Last”).', $total, 'notificationx' ),
                        $total,
                        $this->date( $newest ),
                        $this->date( $from )
                    ),
                    self::builder_fix( $post, 'customize_tab', __( 'Change “Display From The Last”', 'notificationx' ) )
                ),
            ];
        }

        return [
            self::reason(
                'data',
                self::BLOCKED,
                __( 'None of its data can be shown', 'notificationx' ),
                /* translators: %d: number of entries. */
                sprintf( _n( 'It has %d entry, but its settings filter it out (for example, the products, forms or period it shows).', 'It has %d entries, but its settings filter them all out (for example, the products, forms or period it shows).', $total, 'notificationx' ), $total ),
                $fix
            ),
        ];
    }

    /**
     * Entries the public `notice` request would send for this notification.
     */
    protected function shown_entries( $post ) {
        $id     = (int) $post['nx_id'];
        $result = FrontEnd::get_instance()->get_notifications_data( [ 'active' => [ $id ] ] );
        $count  = 0;
        foreach ( [ 'active', 'global' ] as $group ) {
            if ( ! empty( $result[ $group ][ $id ]['entries'] ) && is_array( $result[ $group ][ $id ]['entries'] ) ) {
                $count += count( $result[ $group ][ $id ]['entries'] );
            }
        }
        return $count;
    }

    protected function has_blocker( $post ) {
        foreach ( [ 'pro', 'module', 'plugin' ] as $check ) {
            foreach ( (array) $this->{"check_$check"}( $post ) as $reason ) {
                if ( self::BLOCKED === $reason['level'] ) {
                    return true;
                }
            }
        }
        return false;
    }

    public function check_pages( $post ) {
        $show_on   = $post['show_on'] ?? 'everywhere';
        $locations = array_values( array_filter( (array) ( $post['all_locations'] ?? [] ) ) );
        $fix       = self::builder_fix( $post, 'display_tab', __( 'Change “Show On”', 'notificationx' ) );
        if ( 'only_shortcode' === $show_on ) {
            return [ self::reason( 'pages', self::LIMITED, __( 'It shows only where its shortcode is', 'notificationx' ), __( '“Show On” is set to shortcode only, so it appears only on pages that contain its shortcode.', 'notificationx' ), $fix ) ];
        }
        if ( 'on_selected' === $show_on && empty( $locations ) ) {
            return [ self::reason( 'pages', self::BLOCKED, __( 'It’s set to show on selected pages, but none are selected', 'notificationx' ), __( 'Pick the locations, or set “Show On” to Show Everywhere.', 'notificationx' ), $fix ) ];
        }
        if ( in_array( $show_on, [ 'on_selected', 'hide_on_selected' ], true ) && ! empty( $locations ) ) {
            $names = $this->location_names( $locations, $post );
            return [
                'on_selected' === $show_on
                    /* translators: %s: list of locations. */
                    ? self::reason( 'pages', self::LIMITED, __( 'It shows only on selected pages', 'notificationx' ), sprintf( __( 'Only on: %s.', 'notificationx' ), $names ), $fix )
                    /* translators: %s: list of locations. */
                    : self::reason( 'pages', self::LIMITED, __( 'It’s hidden on some pages', 'notificationx' ), sprintf( __( 'Not on: %s.', 'notificationx' ), $names ), $fix ),
            ];
        }
        return [ self::reason( 'pages', self::OK, __( 'Shows on every page', 'notificationx' ) ) ];
    }

    protected function location_names( $locations, $post ) {
        $labels = Locations::get_instance()->get_locations();
        $names  = [];
        foreach ( $locations as $location ) {
            $label = $labels[ $location ] ?? $location;
            if ( 'is_custom' === $location && ! empty( $post['custom_ids'] ) ) {
                /* translators: %s: post IDs. */
                $label = sprintf( __( 'posts or pages %s', 'notificationx' ), is_array( $post['custom_ids'] ) ? implode( ', ', $post['custom_ids'] ) : (string) $post['custom_ids'] );
            }
            $names[] = wp_strip_all_tags( (string) $label );
        }
        return implode( ', ', $names );
    }

    public function check_audience( $post ) {
        $reasons = [];
        $display = $post['show_on_display'] ?? 'always';
        if ( 'logged_in_user' === $display ) {
            $reasons[] = self::reason( 'audience', self::LIMITED, __( 'Only logged-in visitors see it', 'notificationx' ), __( '“Display For” is Logged In User: visitors who aren’t logged in, and you in a private window, don’t see it.', 'notificationx' ), self::builder_fix( $post, 'display_tab', __( 'Change “Display For”', 'notificationx' ) ) );
        } elseif ( 'logged_out_user' === $display ) {
            $reasons[] = self::reason( 'audience', self::LIMITED, __( 'Only logged-out visitors see it', 'notificationx' ), __( '“Display For” is Logged Out User: you don’t see it while logged in. Check in a private window.', 'notificationx' ), self::builder_fix( $post, 'display_tab', __( 'Change “Display For”', 'notificationx' ) ) );
        }

        $roles = array_values( array_filter( (array) ( $post['targeting_user_roles'] ?? [] ), 'is_string' ) );
        if ( ! empty( $roles ) && ! in_array( 'all_users', $roles, true ) ) {
            $names = [];
            $all   = wp_roles()->role_names;
            foreach ( $roles as $role ) {
                $names[] = 'guest' === $role ? __( 'logged-out visitors', 'notificationx' ) : translate_user_role( $all[ $role ] ?? $role );
            }
            /* translators: %s: list of user roles. */
            $reasons[] = self::reason( 'roles', self::LIMITED, __( 'Only some user roles see it', 'notificationx' ), sprintf( __( 'Target audience: %s.', 'notificationx' ), implode( ', ', $names ) ), self::builder_fix( $post, 'customize_tab', __( 'Change the target audience', 'notificationx' ) ) );
        }

        if ( ! empty( $post['country_targeting'] ) && is_array( $post['country_targeting'] ) ) {
            $countries = array_column( $post['country_targeting'], 'label', 'value' );
            if ( empty( $countries ) ) {
                $countries = array_combine( array_filter( $post['country_targeting'], 'is_string' ), array_filter( $post['country_targeting'], 'is_string' ) );
            }
            $upper = array_map( 'strtoupper', array_keys( (array) $countries ) );
            if ( ! empty( $upper ) && ! in_array( 'ALL', $upper, true ) ) {
                /* translators: %s: list of countries. */
                $reasons[] = self::reason( 'countries', self::LIMITED, __( 'Only visitors from some countries see it', 'notificationx' ), sprintf( __( 'Countries: %s. Visitors whose country can’t be found still see it.', 'notificationx' ), implode( ', ', array_map( 'wp_strip_all_tags', array_values( (array) $countries ) ) ) ), self::builder_fix( $post, 'customize_tab', __( 'Change the countries', 'notificationx' ) ) );
            }
        }

        if ( empty( $reasons ) ) {
            $reasons[] = self::reason( 'audience', self::OK, __( 'Every visitor can see it', 'notificationx' ) );
        }
        return $reasons;
    }

    public function check_devices( $post ) {
        // FrontEnd::get_notifications(): an empty `hide_on_<device>` means hidden there.
        $devices = [
            'desktop' => __( 'desktop', 'notificationx' ),
            'tab'     => __( 'tablet', 'notificationx' ),
            'mobile'  => __( 'mobile', 'notificationx' ),
        ];
        $hidden = [];
        foreach ( $devices as $key => $label ) {
            if ( empty( $post[ "hide_on_$key" ] ) ) {
                $hidden[] = $label;
            }
        }
        $fix = self::builder_fix( $post, 'customize_tab', __( 'Change “Notification Visibility”', 'notificationx' ) );
        if ( count( $hidden ) === count( $devices ) ) {
            return [ self::reason( 'devices', self::BLOCKED, __( 'It’s hidden on every device', 'notificationx' ), __( 'Desktop, Tablet and Mobile are all off under Notification Visibility.', 'notificationx' ), $fix ) ];
        }
        if ( ! empty( $hidden ) ) {
            /* translators: %s: list of devices. */
            return [ self::reason( 'devices', self::LIMITED, __( 'It’s hidden on some devices', 'notificationx' ), sprintf( __( 'Not on %s.', 'notificationx' ), implode( ', ', $hidden ) ), $fix ) ];
        }
        return [ self::reason( 'devices', self::OK, __( 'Shows on desktop, tablet and mobile', 'notificationx' ) ) ];
    }

    public function check_seen( $post ) {
        $source = $post['source'] ?? '';
        if ( 'popup_notification' === $source ) {
            return [ self::reason( 'seen', self::LIMITED, __( 'Once closed, it stays closed until the browser is closed', 'notificationx' ), __( 'A visitor who closed this popup doesn’t see it again in the same browser session.', 'notificationx' ) ) ];
        }
        if ( 'exit_intent_custom' === $source ) {
            $days = max( 1, (int) ( $post['exit_intent_cookie_duration'] ?? 7 ) );
            /* translators: %d: number of days. */
            return [ self::reason( 'seen', self::LIMITED, __( 'It shows once, then waits', 'notificationx' ), sprintf( _n( 'After it shows, the same visitor doesn’t see it again for %d day. It also needs the visitor to move to leave the page.', 'After it shows, the same visitor doesn’t see it again for %d days. It also needs the visitor to move to leave the page.', $days, 'notificationx' ), $days ), self::builder_fix( $post, 'customize_tab', __( 'Change how often it shows', 'notificationx' ) ) ) ];
        }

        $bar          = $this->is_bar( $post );
        $reappearance = $bar ? ( $post['bar_reappearance'] ?? 'show_welcomebar_every_page' ) : ( $post['notification_reappearance'] ?? 'show_notification_next_visit' );
        $tab          = $bar ? 'display_tab' : 'customize_tab';
        $fix          = self::builder_fix( $post, $tab, $bar ? __( 'Change “Bar Reappearance”', 'notificationx' ) : __( 'Change “Notification Reappearance”', 'notificationx' ) );
        if ( in_array( $reappearance, [ 'dont_show_welcomebar', 'dont_show_notification' ], true ) ) {
            $days = max( 1, (int) ( $bar ? ( $post['bar_cache_duration_for_dont_show'] ?? 10 ) : ( $post['cache_duration_for_dont_show'] ?? 10 ) ) );
            /* translators: %d: number of days. */
            return [ self::reason( 'seen', self::LIMITED, __( 'Once closed, it stays hidden for that visitor', 'notificationx' ), sprintf( _n( 'A visitor who closes it doesn’t see it again in that browser for %d day.', 'A visitor who closes it doesn’t see it again in that browser for %d days.', $days, 'notificationx' ), $days ), $fix ) ];
        }
        if ( in_array( $reappearance, [ 'show_welcomebar_next_visit', 'show_notification_next_visit' ], true ) ) {
            return [ self::reason( 'seen', self::NOTE, __( 'Once closed, it comes back on the next visit', 'notificationx' ), __( 'A visitor who closes it doesn’t see it again until they close the browser and come back.', 'notificationx' ), $fix ) ];
        }
        return [ self::reason( 'seen', self::OK, __( 'Closing it hides it only on that page', 'notificationx' ) ) ];
    }

    /**
     * Keys the front end writes when a visitor closes this notification, so
     * the modal can check the admin's own browser (it reads them only).
     */
    public function browser_keys( $post ) {
        $id   = (int) $post['nx_id'];
        $rand = ! empty( $post['countdown_rand'] ) ? '-' . $post['countdown_rand'] : '';
        $key  = "notificationx_{$id}{$rand}";
        return [
            'cookies' => array_values( array_unique( [ $key, 'nx_bar_' . $id, 'nx_exit_intent_' . $id ] ) ),
            'local'   => [ $key ],
            'session' => array_values( array_unique( [ $key, 'notificationx_popup_' . $id ] ) ),
        ];
    }

    public function check_timing( $post ) {
        if ( in_array( $post['source'] ?? '', self::WITHOUT_ENTRIES, true ) ) {
            return [];
        }
        $reasons = [];
        $delay   = (int) ( $post['delay_before'] ?? 0 );
        if ( $delay > 30 ) {
            /* translators: %d: seconds. */
            $reasons[] = self::reason( 'timing', self::LIMITED, __( 'It waits a while before appearing', 'notificationx' ), sprintf( __( 'The first notification appears %d seconds after the page loads.', 'notificationx' ), $delay ), self::builder_fix( $post, 'customize_tab', __( 'Change the timing', 'notificationx' ) ) );
        }
        if ( isset( $post['loop'] ) && ! $post['loop'] ) {
            $reasons[] = self::reason( 'loop', self::NOTE, __( 'It doesn’t loop', 'notificationx' ), __( 'Each entry shows once per page view, then it stops until the next page.', 'notificationx' ), self::builder_fix( $post, 'customize_tab', __( 'Change “Loop Notification”', 'notificationx' ) ) );
        }
        if ( empty( $reasons ) ) {
            $reasons[] = self::reason( 'timing', self::OK, __( 'Appears soon after the page loads', 'notificationx' ) );
        }
        return $reasons;
    }

    /**
     * Other notifications at the same place, which take turns with this one.
     */
    public function check_slot( $post ) {
        if ( in_array( $post['source'] ?? '', [ 'gdpr_notification', 'flashing_tab' ], true ) ) {
            return [];
        }
        $id       = (int) $post['nx_id'];
        $position = $post['position'] ?? '';
        $queue    = ! empty( $post['global_queue'] );
        $others   = [];
        foreach ( PostType::get_instance()->get_posts( [ 'enabled' => true ] ) as $other ) {
            if ( (int) $other['nx_id'] === $id || in_array( $other['source'] ?? '', [ 'gdpr_notification', 'flashing_tab' ], true ) ) {
                continue;
            }
            $same = $queue ? ! empty( $other['global_queue'] ) : ( empty( $other['global_queue'] ) && '' !== $position && ( $other['position'] ?? '' ) === $position && $this->is_bar( $other ) === $this->is_bar( $post ) );
            if ( $same ) {
                $name     = trim( wp_strip_all_tags( (string) ( $other['title'] ?? '' ) ) );
                $others[] = '' === $name ? '#' . (int) $other['nx_id'] : '“' . $name . '” (#' . (int) $other['nx_id'] . ')';
            }
        }
        if ( empty( $others ) ) {
            return [ self::reason( 'slot', self::OK, __( 'No other notification shares its place', 'notificationx' ) ) ];
        }
        $list = implode( ', ', array_slice( $others, 0, 5 ) ) . ( count( $others ) > 5 ? ', …' : '' );
        return [
            self::reason(
                'slot',
                self::NOTE,
                $queue ? __( 'It shares the global queue', 'notificationx' ) : __( 'Other notifications share its place', 'notificationx' ),
                /* translators: %s: list of notifications. */
                $queue ? sprintf( __( 'Notifications in the global queue take turns, so it may appear later: %s.', 'notificationx' ), $list ) : sprintf( __( 'They take turns or stack at the same position, so it may appear later or under another one: %s.', 'notificationx' ), $list ),
                self::builder_fix( $post, 'display_tab', __( 'Change the position', 'notificationx' ) )
            ),
        ];
    }

    public function check_cache( $post ) {
        if ( ( defined( 'WP_CACHE' ) && WP_CACHE ) || file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
            return [ self::reason( 'cache', self::NOTE, __( 'A page cache is on', 'notificationx' ), __( 'Cached pages can keep the old set of notifications until the cache refreshes. After a change, clear the page cache or check in a private window.', 'notificationx' ) ) ];
        }
        return [];
    }
}
