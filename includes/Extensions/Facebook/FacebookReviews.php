<?php
/**
 * FacebookReviews Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;

use NotificationX\GetInstance;
use NotificationX\NotificationX;
use NotificationX\Admin\Settings;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * Facebook Page reviews via the managed NotificationX API.
 *
 * The API owns the Meta app and the Page access token; this site only stores a
 * connection id + page id/name in the campaign. Two kinds of data reach us:
 *
 *  - Page summary (overall star rating + rating count) pulled from the API on
 *    save and on cron — the only review data Meta still exposes (the Page
 *    Recommendations API was deprecated in Graph API v22.0).
 *  - Individual reviews pushed by the API over the signed webhook
 *    POST /wp-json/notificationx/v1/social-review whenever a provider that can
 *    deliver them is connected (see Core\Rest\FacebookReviews).
 *
 * @method static FacebookReviews get_instance($args = null)
 */
class FacebookReviews extends Extension {
    use GetInstance;

    public $priority        = 6;
    public $id              = 'facebook_reviews';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/facebook-reviews.svg';
    public $doc_link        = 'https://notificationx.com/docs/facebook-reviews-with-notificationx/';
    public $types           = 'reviews';
    public $module          = 'modules_facebook_reviews';
    public $module_priority = 21;
    public $default_theme   = 'facebook_reviews_total-rated';
    public $is_pro          = false;
    public $link_type       = 'review_page';
    public $cron_schedule   = 'nx_facebook_reviews_interval';

    /** Refresh interval (minutes) for the page summary. Pro makes it configurable. */
    const FREE_REFRESH_MINUTES = 720;
    const PRO_MIN_REFRESH_MINUTES = 30;
    /** Campaign field holding ['connection_id', 'page_id', 'page_name']. */
    const FIELD_CONNECTION = 'facebook_reviews_connection';

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __('Facebook Reviews', 'notificationx');
        $this->module_title = __('Facebook Reviews', 'notificationx');

        // Theme keys reuse the shared review suffixes so the existing review CSS applies.
        // $default_theme must be the FIRST entry (the builder falls back to the first theme).
        $this->themes = [
            'total-rated'      => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-rated.png',
                'image_shape'             => 'square',
                'show_notification_image' => 'fbreview_icon',
                'template'  => [
                    'first_param'         => 'tag_rated',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('people rated', 'notificationx'),
                    'third_param'         => 'tag_place_name',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'review-comment'   => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment.jpg',
                'image_shape'             => 'rounded',
                'show_notification_image' => 'fbreview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_review',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'review-comment-2' => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-2.jpg',
                'image_shape'             => 'circle',
                'show_notification_image' => 'fbreview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_review',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => '',
                ],
            ],
            'review-comment-3' => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-3.jpg',
                'image_shape'             => 'circle',
                'show_notification_image' => 'fbreview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_review',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'reviewed'         => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/reviewed.png',
                'image_shape'             => 'circle',
                'show_notification_image' => 'fbreview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_name',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    // Not tag_rating: today's Facebook reviews are Recommends /
                    // Doesn't recommend and carry no stars, so a star tag would
                    // render blank on most Pages. The star tag is still offered
                    // for the older reviews that do have one.
                    'fourth_param'        => 'tag_recommendation',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
        ];

        $this->templates = [
            "{$this->id}_template_new" => [
                'first_param'  => [
                    'tag_username' => __('Username', 'notificationx'),
                    'tag_rated'    => __('Rating Count', 'notificationx'),
                ],
                'third_param'  => [
                    'tag_place_name'   => __('Page Name', 'notificationx'),
                    'tag_place_review' => __('Review', 'notificationx'),
                    'tag_owner_reply'  => __('Page Reply', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_recommendation' => __('Recommendation', 'notificationx'),
                    'tag_rating'         => __('Rating', 'notificationx'),
                    'tag_time'           => __('Definite Time', 'notificationx'),
                    'tag_tags'           => __('Review Tags', 'notificationx'),
                ],
                '_themes' => [
                    "{$this->id}_total-rated",
                    "{$this->id}_review-comment",
                    "{$this->id}_review-comment-2",
                    "{$this->id}_review-comment-3",
                    "{$this->id}_reviewed",
                ],
            ],
        ];
    }

    public function init() {
        parent::init();
        add_filter('nx_cron_schedules', [$this, 'cron_schedules']);
    }

    public function admin_actions() {
        parent::admin_actions();
        add_action("nx_cron_update_data_{$this->id}", [$this, 'update_data'], 10, 2);
    }

    public function public_actions() {
        parent::public_actions();
        add_filter("nx_notification_link_{$this->id}", [$this, 'product_link'], 10, 3);
        add_filter("nx_filtered_entry_{$this->id}", [$this, 'conversion_data'], 11, 2);
        // Never window reviews out by age. The "display from the last N days"
        // rule suits a sales feed, where an old event is genuinely stale; a
        // review is not. Most Pages collect a handful a year, so applying the
        // window here would routinely render nothing at all — which is why the
        // Google Reviews source does the same. The controls are hidden from the
        // builder too (see customize_fields) so the setting cannot mislead.
        add_filter("nx_entry_display_{$this->id}", '__return_false');
    }

    public function init_fields() {
        parent::init_fields();
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
        add_filter('nx_display_fields', [$this, 'display_fields']);
        add_filter('nx_content_fields', [$this, 'content_fields'], 20);
        add_filter('nx_content_trim_length_dependency', [$this, 'content_trim_length_dependency']);
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        add_filter('nx_settings_tab', [$this, 'settings_tab']);
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    public function content_trim_length_dependency($dependency) {
        $dependency[] = "{$this->id}_review-comment";
        $dependency[] = "{$this->id}_review-comment-2";
        $dependency[] = "{$this->id}_review-comment-3";
        return $dependency;
    }

    /**
     * Content step: the Facebook Page connection picker.
     * Rendered by nxdev/notificationx/fields/FacebookReviewsConnection.tsx.
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];

        $content_fields[self::FIELD_CONNECTION] = [
            'label'       => __('Facebook Page', 'notificationx'),
            'name'        => self::FIELD_CONNECTION,
            'type'        => 'facebook-reviews-connection',
            'mode'        => 'builder',
            'priority'    => 10,
            'source'      => $this->id,
            'description' => __('Connect a Facebook Page you manage. The NotificationX API handles the Facebook login — no app setup or tokens needed on your site.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        if (!NotificationX::get_instance()->is_pro()) {
            $content_fields = $this->pro_preview_fields($content_fields);
        }

        return $fields;
    }

    /**
     * Pro-only review filters, shown locked in the free plugin. Same keys and
     * defaults as NotificationXPro\Extensions\Facebook\FacebookReviews::content_fields_pro()
     * so the builder looks identical on both plans; Pro re-registers them unlocked.
     */
    protected function pro_preview_fields($content_fields) {
        foreach (self::pro_filter_fields($this->id, true) as $key => $field) {
            $content_fields[$key] = $field;
        }
        return $content_fields;
    }

    /**
     * Definition of the Pro review filters. $locked renders the crown badge and
     * blocks changes in the free plugin.
     */
    public static function pro_filter_fields($source, $locked) {
        return [
            // Recommendation comes first because it is the filter that applies
            // to today's Facebook reviews; the star filter below only bites on
            // the older star-rated ones that some Pages still carry.
            'facebook_reviews_recommendation' => [
                'label'    => __('Recommendation', 'notificationx'),
                'name'     => 'facebook_reviews_recommendation',
                'type'     => 'select',
                'priority' => 39,
                'default'  => 'all',
                'is_pro'   => $locked,
                'options'  => GlobalFields::get_instance()->normalize_fields([
                    'all'         => __('Show all reviews', 'notificationx'),
                    'recommended' => __('Only "Recommends"', 'notificationx'),
                ]),
                'description' => __('Facebook reviews are Recommends or Doesn\'t recommend rather than star ratings.', 'notificationx'),
                'rules'    => Rules::is('source', $source),
            ],
            'facebook_reviews_min_rating' => [
                'label'    => __('Minimum Star Rating', 'notificationx'),
                'name'     => 'facebook_reviews_min_rating',
                'type'     => 'select',
                'priority' => 40,
                'default'  => '1',
                'is_pro'   => $locked,
                'options'  => GlobalFields::get_instance()->normalize_fields([
                    '1' => __('Show all reviews', 'notificationx'),
                    '4' => __('4 stars and up', 'notificationx'),
                    '5' => __('5 stars only', 'notificationx'),
                ]),
                'description' => __('Only applies to older reviews that carry a star rating. Recommendations have no stars and are filtered above.', 'notificationx'),
                'rules'    => Rules::is('source', $source),
            ],
            'facebook_reviews_text_only' => [
                'label'    => __('Only Reviews With Text', 'notificationx'),
                'name'     => 'facebook_reviews_text_only',
                'type'     => 'toggle',
                'priority' => 41,
                'default'  => false,
                'is_pro'   => $locked,
                'rules'    => Rules::is('source', $source),
            ],
            'facebook_reviews_min_length' => [
                'label'       => __('Minimum Review Length', 'notificationx'),
                'name'        => 'facebook_reviews_min_length',
                'type'        => 'number',
                'priority'    => 42,
                'default'     => 0,
                'min'         => 0,
                'is_pro'      => $locked,
                'description' => __('Skip reviews shorter than this many characters. 0 keeps them all.', 'notificationx'),
                'rules'       => Rules::is('source', $source),
            ],
        ];
    }

    /**
     * The "API Integrations" tab is created by notificationx-pro; this source is
     * functional in the free plugin, so stand the tab up when Pro is absent.
     */
    public function settings_tab($tabs) {
        if (isset($tabs['api_integrations_tab'])) {
            return $tabs;
        }
        $tabs['api_integrations_tab'] = [
            'id'       => 'tab-api-integrations',
            'label'    => __('API Integrations', 'notificationx'),
            'classes'  => 'tab-api-integrations',
            'priority' => 90,
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            'fields'   => apply_filters('nx_settings_tab_api_integration', []),
        ];
        return $tabs;
    }

    public function api_integration_settings($sections) {
        $sections['facebook_reviews_settings_section'] = [
            'name'     => 'facebook_reviews_settings_section',
            'type'     => 'section',
            'label'    => __('Facebook Reviews Settings', 'notificationx'),
            'modules'  => $this->module,
            'priority' => 81,
            'rules'    => Rules::is('modules.' . $this->module, true),
            'fields'   => [
                'facebook_reviews_managed_connection' => [
                    'name'   => 'facebook_reviews_managed_connection',
                    'type'   => 'facebook-reviews-connection',
                    'mode'   => 'settings',
                    'label'  => __('Connected Pages', 'notificationx'),
                    'source' => $this->id,
                ],
            ] + $this->refresh_interval_fields(),
        ];
        return $sections;
    }

    /**
     * Refresh-interval control (locked on free). Pro overrides to unlock.
     */
    protected function refresh_interval_fields() {
        return [
            'facebook_reviews_refresh_interval' => [
                'name'        => 'facebook_reviews_refresh_interval',
                'type'        => 'number',
                'label'       => __('Refresh Interval', 'notificationx'),
                'default'     => self::FREE_REFRESH_MINUTES,
                'min'         => self::FREE_REFRESH_MINUTES,
                'disabled'    => true,
                'is_pro'      => true,
                'description' => __('Minutes between page rating refreshes. Fixed at 12 hours on the free plan. Upgrade to PRO to refresh more often.', 'notificationx'),
            ],
        ];
    }

    /** Effective refresh interval in minutes, floored per plan. */
    public function get_refresh_interval() {
        $is_pro = NotificationX::get_instance()->is_pro();
        $floor  = $is_pro ? self::PRO_MIN_REFRESH_MINUTES : self::FREE_REFRESH_MINUTES;
        $value  = $is_pro ? Settings::get_instance()->get('settings.facebook_reviews_refresh_interval', 360) : $floor;
        return max($floor, absint($value));
    }

    public function cron_schedules($schedules) {
        $minutes = $this->get_refresh_interval();
        $schedules[$this->cron_schedule] = [
            'interval' => MINUTE_IN_SECONDS * $minutes,
            /* translators: %d: number of minutes */
            'display'  => sprintf(_n('Every %d minute', 'Every %d minutes', $minutes, 'notificationx'), $minutes),
        ];
        return $schedules;
    }

    public function source_error_message($messages) {
        if (!FacebookReviewsManaged::is_connected()) {
            $messages[$this->id] = [
                'message' => __('Click "Connect Facebook Page" below to link a Facebook Page. Your site is registered with the NotificationX API on first use.', 'notificationx'),
                'html'    => false,
                'type'    => 'warning',
                'rules'   => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    /**
     * Settings-page handler (`/notificationx/v1/api-connect`, action=connect|disconnect):
     * registers/revokes this site with the NotificationX API.
     */
    public function connect($params) {
        $action = isset($params['action']) ? sanitize_key($params['action']) : 'connect';
        if ('disconnect' === $action) {
            FacebookReviewsManaged::disconnect();
            return ['status' => 'success', 'message' => __('Disconnected from the NotificationX API.', 'notificationx'), 'site' => FacebookReviewsManaged::site_status()];
        }
        $result = FacebookReviewsManaged::connect();
        if (empty($result['ok'])) {
            return ['status' => 'error', 'message' => $result['message']];
        }
        return ['status' => 'success', 'message' => __('Connected to the NotificationX API.', 'notificationx'), 'site' => FacebookReviewsManaged::site_status()];
    }

    /**
     * Hide the age-based display controls for this source.
     *
     * "Display from the last N days" and "Display last N" describe a stream of
     * recent events. Reviews are not that: a Page may collect a few a year, and
     * the good ones stay worth showing for as long as they stand. Leaving the
     * controls visible would offer a setting that silently empties the
     * notification — so they are removed here, exactly as Google Reviews does.
     */
    public function customize_fields($fields) {
        foreach (['display_from', 'display_last'] as $field) {
            if (isset($fields['behaviour']['fields'][$field])) {
                $fields['behaviour']['fields'][$field] = Rules::is('source', $this->id, true, $fields['behaviour']['fields'][$field]);
            }
        }
        return $fields;
    }

    public function display_fields($fields) {
        $show_image = &$fields['image-section']['fields']['show_notification_image'];
        $show_image = Rules::includes('source', $this->id, false, $show_image);
        $show_image['options'] = GlobalFields::get_instance()->normalize_fields([
            'fbreview_avatar' => ['value' => 'fbreview_avatar', 'label' => _x('Avatar', 'Facebook Review', 'notificationx'), 'rules' => Rules::is('source', $this->id)],
            'fbreview_icon'   => ['value' => 'fbreview_icon', 'label' => _x('Icon', 'Facebook Review', 'notificationx'), 'rules' => Rules::is('source', $this->id)],
        ], null, null, $show_image['options']);

        $fields['image-section']['fields']['default_avatar']['options'][] = [
            'value' => 'facebook-f-icon.svg',
            'label' => __('Facebook Icon', 'notificationx'),
            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.svg',
            'rules' => Rules::is('source', $this->id),
        ];
        return $fields;
    }

    public function preview_entry($entry, $settings) {
        if (isset($settings['show_notification_image']) && 'fbreview_icon' === $settings['show_notification_image']) {
            $entry['image_data'] = ['url' => NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.svg', 'alt' => '', 'classes' => 'fbreview_icon'];
        }
        return $entry;
    }

    /**
     * True when this campaign is the aggregate-rating kind.
     *
     * Decided by the template rather than the theme name, because the theme only
     * sets the initial template and the user is free to change the tags
     * afterwards — `tag_rated` is what actually needs the aggregate entry to
     * exist, and nothing else does.
     */
    public static function wants_summary($settings) {
        $template = isset($settings['notification-template']) && is_array($settings['notification-template'])
            ? $settings['notification-template']
            : [];
        if (isset($template['first_param'])) {
            return 'tag_rated' === $template['first_param'];
        }
        // No template yet (a campaign being created): fall back to the theme it
        // was started from.
        return isset($settings['themes']) && 'facebook_reviews_total-rated' === $settings['themes'];
    }

    /** @return array{connection_id:string,page_id:string,page_name:string} */
    public static function connection_from_settings($settings) {
        $value = isset($settings[self::FIELD_CONNECTION]) ? $settings[self::FIELD_CONNECTION] : [];
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        $value = is_array($value) ? $value : [];
        return [
            'connection_id' => isset($value['connection_id']) ? sanitize_text_field((string) $value['connection_id']) : '',
            'page_id'       => isset($value['page_id']) ? sanitize_text_field((string) $value['page_id']) : '',
            'page_name'     => isset($value['page_name']) ? sanitize_text_field((string) $value['page_name']) : '',
        ];
    }

    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
        return $post;
    }

    public function get_notification_ready($data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    /**
     * Refreshes the page summary entry from the API. Individual review entries
     * (delivered by webhook) are left untouched.
     */
    public function update_data($nx_id, $settings = []) {
        if (empty($nx_id)) {
            return;
        }
        if (empty($settings)) {
            $settings = PostType::get_instance()->get_post($nx_id);
        }
        $connection = self::connection_from_settings($settings);
        if ('' === $connection['connection_id'] || !FacebookReviewsManaged::is_connected()) {
            return;
        }
        $result = FacebookReviewsManaged::connections($connection['connection_id']);
        if (empty($result['ok']) || empty($result['body']['connection'])) {
            $this->log($nx_id, isset($result['error']) ? $result['error'] : 'status_failed');
            return;
        }
        $remote = $result['body']['connection'];
        if (empty($remote['status']) || 'active' !== $remote['status']) {
            $this->log($nx_id, 'connection_' . sanitize_key((string) $remote['status']));
            return;
        }

        // A campaign shows EITHER the Page's aggregate rating OR its individual
        // reviews — never both. They are different notifications: the aggregate
        // is one aggregate card ("128 people rated Example Business"), while the
        // review themes put a person's name and words in the sentence. Mixing
        // them puts a nameless, wordless summary into a per-review rotation,
        // where it renders as "  just reviewed Example Business".
        $entry_key = 'summary_' . $connection['page_id'];
        if (!self::wants_summary($settings)) {
            // Individual reviews. The webhook delivers these the moment the API
            // collects them, but only to sites it can reach; pulling here is what
            // makes the source work on local, staging and firewalled installs, and
            // what repairs a campaign created after collection already happened.
            $this->sync_reviews($nx_id, $connection['connection_id']);
            $this->delete_notification($entry_key, $nx_id);
            return;
        }

        $this->delete_notification($entry_key, $nx_id);
        if (isset($remote['rating_count']) && null !== $remote['rating_count']) {
            $this->update_notifications([[
                'nx_id'     => $nx_id,
                'source'    => $this->id,
                'entry_key' => $entry_key,
                'data'      => [
                    'place_name' => !empty($remote['page_name']) ? sanitize_text_field((string) $remote['page_name']) : $connection['page_name'],
                    'page_id'    => $connection['page_id'],
                    'rated'      => (int) $remote['rating_count'],
                    'rating'     => isset($remote['rating_overall']) ? (float) $remote['rating_overall'] : 0,
                    'is_summary' => true,
                    'url'        => 'https://www.facebook.com/' . rawurlencode($connection['page_id']) . '/reviews',
                    'timestamp'  => time(),
                ],
            ]]);
        }
    }

    /**
     * Stores one individual review (from the webhook) into every campaign bound
     * to its connection. Idempotent: the entry key is derived from the review's
     * identity, so a redelivery updates nothing and creates nothing.
     *
     * @param array $review normalized payload from the API
     * @return int number of campaigns updated
     */
    public function ingest_review($review) {
        $data = self::map_review($review);
        if (empty($data)) {
            return 0;
        }
        $connection_id = sanitize_text_field((string) (isset($review['connection_id']) ? $review['connection_id'] : ''));
        if ('' === $connection_id) {
            return 0;
        }

        $count = 0;
        foreach ($this->campaigns_for_connection($connection_id) as $post) {
            // Aggregate campaigns show one rating card; an individual review has
            // no place in them.
            if (self::wants_summary($post)) {
                continue;
            }
            if (false !== $this->store_review_entry($post['nx_id'], $data)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Write one mapped review into one campaign, unless it is already there.
     *
     * `update_notification(..., false)` is the existence-checked path: the same
     * review arriving twice — once pushed, once pulled — must not produce two
     * notifications, and a visitor must not be shown a review they have already
     * been shown because a cron ran twice.
     */
    protected function store_review_entry($nx_id, array $data) {
        return $this->update_notification([
            'nx_id'     => $nx_id,
            'source'    => $this->id,
            'entry_key' => self::entry_key($data['page_id'], $data['review_id']),
            'data'      => $data,
        ], false);
    }

    /** Stable per-review key within a campaign. */
    public static function entry_key($page_id, $review_id) {
        return md5('facebook_reviews|' . $page_id . '|' . $review_id);
    }

    /**
     * Normalized API review → the flat data bag a NotificationX theme renders.
     *
     * Both delivery paths run through here, so a pushed review and a pulled one
     * produce byte-identical entries — otherwise the same review could render
     * differently depending on how it happened to arrive.
     *
     * Everything optional is allowed to be missing. Facebook reviews are ragged
     * by nature: recommendations carry no star rating, a reviewer's photo
     * depends on their own privacy settings, a recommendation can be a bare
     * thumbs-up with no words, and most dates are only ever "2 weeks ago". The
     * rule here is to record what we were given and never to invent the rest —
     * the renderer decides what to show when a field is absent.
     *
     * @param array $review
     * @return array empty when the payload is unusable
     */
    public static function map_review($review) {
        if (!is_array($review)) {
            return [];
        }
        $review_id = sanitize_text_field((string) (isset($review['review_id']) ? $review['review_id'] : ''));
        $page_id   = sanitize_text_field((string) (isset($review['page_id']) ? $review['page_id'] : ''));
        if ('' === $review_id || '' === $page_id) {
            return [];
        }

        $reviewer = isset($review['reviewer']) && is_array($review['reviewer']) ? $review['reviewer'] : [];
        $meta     = isset($review['meta']) && is_array($review['meta']) ? $review['meta'] : [];
        $type     = isset($review['recommendation_type']) ? sanitize_key((string) $review['recommendation_type']) : '';

        // A Facebook recommendation is NOT a five-star review, so no star is
        // fabricated for one. Inventing 5s would corrupt any average shown
        // beside it and would put stars on a notification for a review that
        // never had them. `is_recommended` carries the signal instead, and the
        // themes render it as "Recommends" — which is what Facebook shows too.
        $rating = isset($review['rating']) && null !== $review['rating'] ? (int) $review['rating'] : 0;
        $rating = ($rating >= 1 && $rating <= 5) ? $rating : 0;

        $data = [
            'review_id'         => $review_id,
            'page_id'           => $page_id,
            'username'          => isset($reviewer['name']) ? sanitize_text_field((string) $reviewer['name']) : '',
            'profile_photo_url' => self::safe_url(isset($reviewer['avatar']) ? $reviewer['avatar'] : ''),
            'reviewer_url'      => self::safe_url(isset($reviewer['url']) ? $reviewer['url'] : ''),
            'place_name'        => isset($review['page_name']) ? sanitize_text_field((string) $review['page_name']) : '',
            'place_review'      => isset($review['content']) ? sanitize_textarea_field((string) $review['content']) : '',
            'rating'            => $rating,
            'source_provider'   => isset($review['source']) ? sanitize_key((string) $review['source']) : 'facebook',
            'url'               => self::safe_url(isset($review['review_url']) ? $review['review_url'] : ''),
        ];

        if ('' === $data['username']) {
            // Facebook withholds the reviewer's name when their privacy settings
            // say so. The review is still worth showing; it just needs a subject.
            $data['username'] = __('A Facebook user', 'notificationx');
        }
        if ('' === $data['url']) {
            // No permalink for this particular recommendation — send visitors to
            // the Page's reviews tab, which is where it lives.
            $data['url'] = 'https://www.facebook.com/' . rawurlencode($page_id) . '/reviews';
        }
        if ('positive' === $type || 'negative' === $type) {
            $data['is_recommended'] = ('positive' === $type);
        }

        // Timestamps. An absent date must not become "a few seconds ago": that
        // would present a years-old review as breaking news. Fall back to when
        // we collected it, and keep Facebook's own label so a theme can show
        // "2 weeks ago" rather than a precision we do not have.
        $timestamp = !empty($review['created_at']) ? strtotime((string) $review['created_at']) : false;
        if (false === $timestamp && !empty($meta['collected_at'])) {
            $timestamp = strtotime((string) $meta['collected_at']);
        }
        $data['timestamp'] = $timestamp ? (int) $timestamp : time();
        if (!empty($meta['relative_time'])) {
            $data['time_label'] = sanitize_text_field((string) $meta['relative_time']);
        }
        if (!empty($meta['date_is_approximate'])) {
            $data['date_is_approximate'] = true;
        }

        // Page-level context, so a theme can show "4.6 from 128 recommendations"
        // next to an individual review without a second lookup.
        $page_rating = isset($review['page_rating']) && is_array($review['page_rating']) ? $review['page_rating'] : [];
        if (isset($page_rating['count']) && null !== $page_rating['count']) {
            $data['rated'] = (int) $page_rating['count'];
        }
        if (isset($page_rating['overall']) && null !== $page_rating['overall']) {
            $data['page_rating'] = (float) $page_rating['overall'];
        }

        // Optional extras. Present only when the source actually had them, so a
        // theme can test with isset() rather than for emptiness.
        if (!empty($meta['tags']) && is_array($meta['tags'])) {
            $data['tags'] = implode(', ', array_map('sanitize_text_field', array_slice($meta['tags'], 0, 5)));
        }
        if (!empty($meta['photos'][0])) {
            $data['review_photo'] = self::safe_url($meta['photos'][0]);
        }
        if (!empty($meta['engagement']['reactions'])) {
            $data['likes'] = (int) $meta['engagement']['reactions'];
        }
        if (!empty($meta['engagement']['comments'])) {
            $data['comments'] = (int) $meta['engagement']['comments'];
        }
        if (!empty($meta['owner_reply']['text'])) {
            $data['owner_reply'] = sanitize_textarea_field((string) $meta['owner_reply']['text']);
        }

        return $data;
    }

    /** http(s) URLs only; anything else becomes '' rather than reaching a template. */
    protected static function safe_url($url) {
        $url = esc_url_raw((string) $url, ['http', 'https']);
        return is_string($url) ? $url : '';
    }

    /**
     * Pull individual reviews for a campaign and store any we do not have.
     *
     * Walks newest-first and stops as soon as a page adds nothing new, which
     * makes the steady-state cost one request. The page budget bounds a first
     * run (or a restored backup) so a campaign bound to a Page with hundreds of
     * reviews cannot turn one cron tick into a long crawl.
     *
     * @return int reviews newly stored
     */
    public function sync_reviews($nx_id, $connection_id, $fresh = false) {
        if (empty($nx_id) || '' === $connection_id) {
            return 0;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        $max_pages = (int) apply_filters('nx_facebook_reviews_sync_pages', 4, $nx_id);
        $stored    = 0;
        $after     = 0;

        for ($page = 0; $page < max(1, $max_pages); $page++) {
            $result = FacebookReviewsManaged::reviews($connection_id, ['limit' => 50, 'after' => $after, 'fresh' => $fresh && 0 === $page]);
            if (empty($result['ok'])) {
                $this->log($nx_id, isset($result['error']) ? $result['error'] : 'reviews_failed');
                break;
            }
            $reviews = isset($result['body']['reviews']) && is_array($result['body']['reviews']) ? $result['body']['reviews'] : [];
            if ([] === $reviews) {
                break;
            }

            $new = 0;
            foreach ($reviews as $review) {
                $data = self::map_review($review);
                if (empty($data)) {
                    continue;
                }
                if (false !== $this->store_review_entry($nx_id, $data)) {
                    $new++;
                }
            }
            $stored += $new;

            $next = isset($result['body']['next_cursor']) ? (int) $result['body']['next_cursor'] : 0;
            // Nothing new on this page means everything older is already stored
            // too, because the API returns them newest-first.
            if (0 === $new || $next <= 0) {
                break;
            }
            $after = $next;
        }

        return $stored;
    }

    /**
     * Campaigns bound to a connection, whatever their enabled state.
     *
     * Disabled campaigns are included on purpose: a review that arrives while a
     * campaign is paused should still be stored, so switching it back on shows
     * the reviews collected in the meantime rather than a gap.
     *
     * @return array
     */
    public function campaigns_for_connection($connection_id) {
        $matches = [];
        foreach (PostType::get_instance()->get_posts(['source' => $this->id]) as $post) {
            $bound = self::connection_from_settings($post);
            if ($bound['connection_id'] === $connection_id) {
                $matches[] = $post;
            }
        }
        return $matches;
    }

    public function conversion_data($saved_data, $settings) {
        // Built at render time rather than stored, so the label follows the
        // site's language instead of whichever language was active when the
        // review happened to be collected.
        if (isset($saved_data['is_recommended'])) {
            $saved_data['recommendation'] = $saved_data['is_recommended']
                ? __('Recommends', 'notificationx')
                : __('Doesn\'t recommend', 'notificationx');
        } elseif (!empty($saved_data['rating'])) {
            /* translators: %s: star rating out of five */
            $saved_data['recommendation'] = sprintf(__('Rated %s out of 5', 'notificationx'), number_format_i18n((float) $saved_data['rating'], 1));
        } else {
            $saved_data['recommendation'] = '';
        }

        // A recommendation with no words is a normal Facebook review, and the
        // review-comment themes put the text in the middle of the sentence — so
        // an empty one would render "Sam just reviewed ."
        //
        // Prefer the reviewer's own tags when there are any: Facebook offers
        // chips like "5 stars" or "Food" and plenty of people use only those, so
        // the tags ARE what that person said. Falling straight to the Page name
        // would throw away the one thing they actually chose.
        if (empty($saved_data['place_review'])) {
            if (!empty($saved_data['tags'])) {
                $saved_data['place_review'] = $saved_data['tags'];
            } elseif (!empty($saved_data['place_name'])) {
                $saved_data['place_review'] = $saved_data['place_name'];
            }
        }

        if (!empty($saved_data['place_review'])) {
            $theme       = isset($settings['themes']) ? $settings['themes'] : '';
            $trim_length = "{$this->id}_review-comment-2" === $theme || "{$this->id}_review-comment-3" === $theme ? 80 : 100;
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            $trim_length = apply_filters('nx_text_trim_length', $trim_length, $settings);
            $review = $saved_data['place_review'];
            if (mb_strlen($review) > $trim_length) {
                $review = mb_substr($review, 0, $trim_length) . '...';
            }
            if ("{$this->id}_review-comment-2" === $theme) {
                $review = '" ' . $review . ' "';
            }
            $saved_data['place_review'] = $review;
        }
        return $saved_data;
    }

    public function product_link($link, $post, $entry) {
        if (isset($post['link_type'], $entry['url']) && 'review_page' === $post['link_type']) {
            $link = $entry['url'];
        }
        return $link;
    }

    public function notification_image($image_data, $data, $settings) {
        if (empty($settings['show_default_image'])) {
            $icon      = NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.svg';
            $image_url = '';
            switch (isset($settings['show_notification_image']) ? $settings['show_notification_image'] : '') {
                case 'fbreview_avatar':
                    $image_url = isset($data['profile_photo_url']) ? $data['profile_photo_url'] : '';
                    // Reviewer photos are frequently absent: Facebook withholds
                    // them according to the reviewer's own privacy settings, and
                    // the aggregate summary entry has no reviewer at all. Falling
                    // back to the Facebook mark keeps the notification's shape —
                    // an imageless card in a layout designed around an avatar
                    // reads as broken, not as minimal.
                    if ('' === $image_url) {
                        $image_url = $icon;
                    }
                    break;
                case 'fbreview_icon':
                    $image_url = $icon;
                    break;
            }
            $image_data['url'] = $image_url;
        }
        $image_data['alt'] = isset($data['username']) ? $data['username'] : '';
        return $image_data;
    }

    protected function log($nx_id, $code) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Reviewed for the NotificationX codebase: acceptable in this context.
        error_log("NX {$this->id} ({$nx_id}): {$code}");
    }

    public function doc() {
        $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#facebook_reviews_settings_section');
        /* translators: %1$s: API integrations settings URL, %2$s: documentation URL */
        return sprintf(__('<p>Connect a Facebook Page you manage — the NotificationX API handles the Facebook login, so there is no app or token to set up. Manage your <a target="_blank" href="%1$s">connected Pages</a> at any time. Note: Facebook no longer provides individual reviews through its API; the page rating and review count are shown instead. For further assistance, check out our <a target="_blank" href="%2$s">documentation</a>.</p>', 'notificationx'), $url, $this->doc_link);
    }
}
