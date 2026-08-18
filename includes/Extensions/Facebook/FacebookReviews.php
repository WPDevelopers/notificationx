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
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * FacebookReviews Extension
 *
 * Facebook surfaces page reviews as Recommendations ("recommends" / "doesn't
 * recommend") rather than 1-5 stars, so this source ships no star-average theme.
 * Unlike Google Reviews this source is functional in the free plugin; the Pro
 * subclass only adds translation, higher caps and filtering.
 *
 * @method static FacebookReviews get_instance($args = null)
 */
class FacebookReviews extends Extension {
    /**
     * Instance of FacebookReviews
     *
     * @var FacebookReviews
     */
    use GetInstance;

    public $priority        = 6;
    public $id              = 'facebook_reviews';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/facebook-reviews.png';
    public $doc_link        = 'https://notificationx.com/docs/facebook-reviews-with-notificationx/';
    public $types           = 'reviews';
    public $module          = 'modules_facebook_reviews';
    public $module_priority = 21;
    public $default_theme   = 'facebook_reviews_review-comment';
    public $is_pro          = false;
    public $link_type       = 'review_page';
    public $api_base        = 'https://api.apify.com/v2/';
    public $cron_schedule   = 'nx_facebook_reviews_interval';

    /**
     * Apify runs are asynchronous and take seconds to minutes, while the campaign
     * refresh interval is measured in hours. This own-hook single event is what
     * polls a run to completion shortly after it starts, instead of making the
     * merchant wait for the next slow cron tick.
     */
    const POLL_HOOK = 'nx_facebook_reviews_poll';
    const MAX_POLLS = 20;

    /**
     * Apify actor that scrapes Facebook page recommendations.
     */
    const ACTOR = 'apify~facebook-reviews-scraper';

    /**
     * Refresh floor, in minutes. The actor bills per scraped review
     * (~$1.40/1000), so free installs are pinned to a 12-hour refresh, which
     * keeps a 10-review campaign inside Apify's free monthly allowance.
     */
    const FREE_CACHE_DURATION = 720;
    const PRO_MIN_CACHE_DURATION = 60;
    const FREE_RESULTS_LIMIT = 10;
    const PRO_RESULTS_LIMIT = 100;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __('Facebook Reviews', 'notificationx');
        $this->module_title = __('Facebook Reviews', 'notificationx');

        // Theme keys reuse the shared review suffixes on purpose: the frontend strips
        // the source prefix when building the CSS class (getThemeName), so these
        // inherit the existing review styles without any new SCSS.
        $this->themes = [
            // Order matters: the builder falls back to the FIRST theme when the
            // stored value is not yet set, while the source trigger applies
            // $default_theme. If the two disagree they overwrite each other every
            // render and the builder flickers, so $default_theme must be this list's
            // first entry.
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
                    'fourth_param'        => 'tag_recommendation',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
        ];

        $this->templates = [
            "{$this->id}_template_new" => [
                'first_param' => [
                    'tag_username' => __('Username', 'notificationx'),
                ],
                'third_param' => [
                    'tag_place_name'   => __('Page Name', 'notificationx'),
                    'tag_place_review' => __('Review', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_recommendation' => __('Recommendation', 'notificationx'),
                    'tag_rating'         => __('Rating', 'notificationx'),
                    'tag_time'           => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
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
        add_action(self::POLL_HOOK, [$this, 'poll'], 10, 1);
        add_action('nx_delete_post', [$this, 'delete_run_state'], 10, 1);
    }

    public function admin_actions() {
        parent::admin_actions();
        add_action("nx_cron_update_data_{$this->id}", [$this, 'update_data'], 10, 2);
    }

    public function public_actions() {
        parent::public_actions();
        add_filter("nx_notification_link_{$this->id}", [$this, 'product_link'], 10, 3);
        // Recommendations are often years old; without this the "Display From"
        // window would silently drop every entry we collected.
        add_filter("nx_entry_display_{$this->id}", '__return_false');
        add_filter("nx_filtered_entry_{$this->id}", [$this, 'conversion_data'], 11, 2);
    }

    public function init_fields() {
        parent::init_fields();
        add_filter('nx_display_fields', [$this, 'display_fields']);
        add_filter('nx_content_fields', [$this, 'content_fields'], 20);
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
        add_filter('nx_content_trim_length_dependency', [$this, 'content_trim_length_dependency']);
    }

    /**
     * "Display From" / "Display Last" filter by entry age, which is meaningless for
     * recommendations we pull wholesale from a page — hide them for this source.
     *
     * @param array $fields
     * @return array
     */
    public function customize_fields($fields) {
        $behaviour = &$fields['behaviour']['fields'];
        $behaviour['display_from'] = Rules::is('source', $this->id, true, $behaviour['display_from']);
        $behaviour['display_last'] = Rules::is('source', $this->id, true, $behaviour['display_last']);
        return $fields;
    }

    /**
     * Lets the Pro-only trim-length control appear for our comment themes.
     *
     * @param array $dependency
     * @return array
     */
    public function content_trim_length_dependency($dependency) {
        $dependency[] = "{$this->id}_review-comment";
        $dependency[] = "{$this->id}_review-comment-2";
        $dependency[] = "{$this->id}_review-comment-3";
        return $dependency;
    }

    /**
     * The two inputs the Apify actor takes. Sorting, language and filtering land here
     * in later phases.
     *
     * @param array $fields
     * @return array
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];
        $is_pro         = NotificationX::get_instance()->is_pro();

        $content_fields['facebook_reviews_page_url'] = [
            'label'       => __('Facebook Page Reviews URL', 'notificationx'),
            'name'        => 'facebook_reviews_page_url',
            'type'        => 'text',
            'priority'    => 10,
            'placeholder' => 'https://www.facebook.com/yourpage/reviews',
            'description' => __('The reviews tab of the Facebook Page you represent, e.g. https://www.facebook.com/yourpage/reviews', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        // The actor returns the page slug (`copperkettleyqr`) and no display name at
        // all, so let the merchant supply one rather than showing a slug in the popup.
        $content_fields['facebook_reviews_page_label'] = [
            'label'       => __('Page Display Name', 'notificationx'),
            'name'        => 'facebook_reviews_page_label',
            'type'        => 'text',
            'priority'    => 15,
            'description' => __('Optional. Shown by the Page Name tag. Leave empty to use the page slug Facebook returns.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        $content_fields['facebook_reviews_sort'] = [
            'label'    => __('Sort By', 'notificationx'),
            'name'     => 'facebook_reviews_sort',
            'type'     => 'select',
            'priority' => 30,
            'default'  => 'newest',
            'options'  => GlobalFields::get_instance()->normalize_fields([
                'newest'     => __('Newest First', 'notificationx'),
                'oldest'     => __('Oldest First', 'notificationx'),
                'most_liked' => __('Most Liked', 'notificationx'),
            ]),
            'description' => __('Applied after collection — Facebook returns recommendations unsorted.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        $content_fields['facebook_reviews_count'] = [
            'label'       => __('Number of Reviews', 'notificationx'),
            'name'        => 'facebook_reviews_count',
            'type'        => 'number',
            'priority'    => 20,
            'default'     => $is_pro ? 20 : self::FREE_RESULTS_LIMIT,
            'min'         => 1,
            'max'         => $is_pro ? self::PRO_RESULTS_LIMIT : self::FREE_RESULTS_LIMIT,
            'description' => $is_pro
                ? __('How many recommendations to collect per refresh. Apify bills per scraped review, so this multiplies your cost.', 'notificationx')
                /* translators: %d: maximum number of reviews on the free plan */
                : sprintf(__('How many recommendations to collect per refresh. Limited to %d on the free plan.', 'notificationx'), self::FREE_RESULTS_LIMIT),
            'rules'       => Rules::is('source', $this->id),
        ];

        return $fields;
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        add_filter('nx_settings_tab', [$this, 'settings_tab']);
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * The "API Integrations" tab is created by notificationx-pro. This source is
     * functional in the free plugin, so it has to stand the tab up itself when Pro
     * is absent — guarded, because Pro's own definition (extra sections, its own
     * label) must win whenever both are present.
     *
     * @param array $tabs
     * @return array
     */
    public function settings_tab($tabs) {
        if (isset($tabs['api_integrations_tab'])) {
            return $tabs;
        }

        $tabs['api_integrations_tab'] = array(
            'id'       => 'tab-api-integrations',
            'label'    => __('API Integrations', 'notificationx'),
            'classes'  => 'tab-api-integrations',
            'priority' => 90,
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            'fields'   => apply_filters('nx_settings_tab_api_integration', []),
        );

        return $tabs;
    }

    /**
     * Apify credentials + refresh interval.
     *
     * @param array $sections
     * @return array
     */
    public function api_integration_settings($sections) {
        $is_pro = NotificationX::get_instance()->is_pro();

        $sections['facebook_reviews_settings_section'] = array(
            'name'     => 'facebook_reviews_settings_section',
            'type'     => 'section',
            'label'    => __('Facebook Reviews Settings', 'notificationx'),
            'modules'  => $this->module,
            'priority' => 81,
            'rules'    => Rules::is('modules.' . $this->module, true),
            'fields'   => [
                'facebook_reviews_apify_token' => array(
                    'name'        => 'facebook_reviews_apify_token',
                    'type'        => 'text',
                    'text'        => __('Apify API Token', 'notificationx'),
                    'label'       => __('Apify API Token', 'notificationx'),
                    'description' => sprintf('%s <a href="%s" target="_blank">%s</a>.',
                        __('Facebook no longer exposes page recommendations through its own API, so they are collected through Apify. To get a token, check out', 'notificationx'),
                        'https://notificationx.com/docs/collect-api-token-from-apify',
                        __('this doc', 'notificationx')
                    ),
                ),
                'facebook_reviews_cache_duration' => [
                    'name'        => 'facebook_reviews_cache_duration',
                    'type'        => 'number',
                    'label'       => __('Cache Duration', 'notificationx'),
                    'default'     => $is_pro ? 360 : self::FREE_CACHE_DURATION,
                    'min'         => $is_pro ? self::PRO_MIN_CACHE_DURATION : self::FREE_CACHE_DURATION,
                    'disabled'    => ! $is_pro,
                    'description' => $is_pro
                        ? __('Minutes between refreshes. Apify bills per scraped review (about $1.40 per 1,000), so a shorter interval costs more: roughly (43200 ÷ minutes) × review count × $0.0014 per month.', 'notificationx')
                        : __('Minutes between refreshes. Fixed at 12 hours on the free plan to keep collection inside Apify\'s free monthly allowance. Upgrade to PRO to refresh more often.', 'notificationx'),
                ],
                [
                    'name'    => 'facebook_review_connect',
                    'type'    => 'button',
                    'default' => false,
                    'text'    => [
                        'normal'  => __('Validate', 'notificationx'),
                        'saved'   => __('Refresh', 'notificationx'),
                        'loading' => __('Validating...', 'notificationx'),
                    ],
                    'ajax' => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'                          => $this->id,
                            'facebook_reviews_apify_token'    => '@facebook_reviews_apify_token',
                            'facebook_reviews_cache_duration' => '@facebook_reviews_cache_duration',
                        ],
                    ],
                ],
            ],
        );

        return $sections;
    }

    /**
     * Effective refresh interval in minutes, floored per plan.
     *
     * @param int|null $duration Raw value; falls back to the stored setting.
     * @return int
     */
    public function get_cache_duration($duration = null) {
        $is_pro = NotificationX::get_instance()->is_pro();
        $floor  = $is_pro ? self::PRO_MIN_CACHE_DURATION : self::FREE_CACHE_DURATION;

        if (null === $duration) {
            $duration = Settings::get_instance()->get('settings.facebook_reviews_cache_duration', $floor);
        }

        return max($floor, absint($duration));
    }

    public function source_error_message($messages) {
        $token = Settings::get_instance()->get('settings.facebook_reviews_apify_token');
        if (empty($token)) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#facebook_reviews_settings_section');
            $messages[$this->id] = [
                'message' => sprintf('%s <a href="%s" target="_blank">%s</a>.',
                    __('You have to setup your Apify API Token for', 'notificationx'),
                    $url,
                    __(' Facebook Reviews', 'notificationx')
                ),
                'html'  => true,
                'type'  => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    /**
     * Validates the token against Apify's own account endpoint.
     *
     * This deliberately does not start an actor run: a run bills per scraped
     * review, so validating that way would charge the merchant for pressing a
     * button. /users/me is free and proves the token is usable.
     *
     * @param array $params
     * @return array
     */
    public function connect($params) {
        $token = isset($params['facebook_reviews_apify_token']) ? trim(sanitize_text_field($params['facebook_reviews_apify_token'])) : '';

        if (empty($token)) {
            return array(
                'status'  => 'error',
                'message' => __('Please insert a valid Apify API token.', 'notificationx'),
            );
        }

        $duration = $this->get_cache_duration(isset($params['facebook_reviews_cache_duration']) ? $params['facebook_reviews_cache_duration'] : null);

        $response = Helper::remote_get($this->api_base . 'users/me?token=' . rawurlencode($token));

        if (empty($response) || empty($response->data)) {
            $message = ! empty($response->error->message)
                ? $response->error->message
                : __('Could not reach Apify with that token. Please double-check the token and try again.', 'notificationx');

            return array(
                'status'  => 'error',
                'message' => $message,
            );
        }

        Settings::get_instance()->set('settings.facebook_reviews_apify_token', $token);
        Settings::get_instance()->set('settings.facebook_reviews_cache_duration', $duration);

        return array(
            'status' => 'success',
        );
    }

    /**
     * Adds the image options this source can actually fill: the reviewer's Facebook
     * avatar, and the Facebook mark as a static icon.
     *
     * @param array $fields
     * @return array
     */
    public function display_fields($fields) {
        $show_image = &$fields['image-section']['fields']['show_notification_image'];
        // Opt this source into the field's source whitelist.
        $show_image = Rules::includes('source', $this->id, false, $show_image);

        $show_image['options'] = GlobalFields::get_instance()->normalize_fields([
            'fbreview_avatar' => [
                'value' => 'fbreview_avatar',
                'label' => _x('Avatar', 'Facebook Review', 'notificationx'),
                'rules' => Rules::is('source', $this->id),
            ],
            'fbreview_icon'   => [
                'value' => 'fbreview_icon',
                'label' => _x('Icon', 'Facebook Review', 'notificationx'),
                'rules' => Rules::is('source', $this->id),
            ],
        ], null, null, $show_image['options']);

        $fields['image-section']['fields']['default_avatar']['options'][] = [
            'value' => 'facebook-f-icon.png',
            'label' => __('Facebook Icon', 'notificationx'),
            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.png',
            'rules' => Rules::is('source', $this->id),
        ];

        return $fields;
    }

    public function preview_entry($entry, $settings) {
        if (isset($settings['show_notification_image']) && 'fbreview_icon' === $settings['show_notification_image']) {
            $entry = array_merge($entry, [
                'image_data' => [
                    'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.png',
                    'alt'     => '',
                    'classes' => 'fbreview_icon',
                ],
            ]);
        }
        return $entry;
    }

    /**
     * Registers the refresh interval from the saved cache duration.
     *
     * @param array $schedules
     * @return array
     */
    public function cron_schedules($schedules) {
        $duration = $this->get_cache_duration();

        $schedules[$this->cron_schedule] = array(
            'interval' => MINUTE_IN_SECONDS * $duration,
            /* translators: %d: number of minutes */
            'display'  => sprintf(_n('Every %d minute', 'Every %d minutes', $duration, 'notificationx'), $duration),
        );

        return $schedules;
    }

    /**
     * Reviews to request, floored and capped per plan. Applied server-side so a
     * crafted save cannot exceed the free cap.
     *
     * @param int|null $count
     * @return int
     */
    public function get_results_limit($count = null) {
        $max   = NotificationX::get_instance()->is_pro() ? self::PRO_RESULTS_LIMIT : self::FREE_RESULTS_LIMIT;
        $count = absint($count);

        return $count > 0 ? min($max, $count) : min($max, self::FREE_RESULTS_LIMIT);
    }

    protected function run_state_key($nx_id) {
        return "nx_{$this->id}_run_" . absint($nx_id);
    }

    /**
     * Run state lives in an option, not in the campaign row: the campaign's `data`
     * blob is rewritten wholesale on every builder save, which would drop an
     * in-flight run handle and make us pay for the same scrape twice.
     */
    protected function get_run_state($nx_id) {
        $state = get_option($this->run_state_key($nx_id), []);
        return is_array($state) ? $state : [];
    }

    protected function set_run_state($nx_id, $state) {
        update_option($this->run_state_key($nx_id), $state, false);
    }

    /**
     * Forgets the in-flight run but keeps `last_success` / `signature`, so the
     * throttle below still knows when data was last collected.
     */
    protected function clear_run($nx_id, $state = [], $error = '') {
        unset($state['run_id'], $state['dataset_id'], $state['started_at'], $state['polls']);
        $state['last_error'] = $error;
        $this->set_run_state($nx_id, $state);
    }

    public function delete_run_state($nx_id) {
        delete_option($this->run_state_key($nx_id));
    }

    protected function schedule_poll($nx_id, $polls = 0) {
        $nx_id = (int) $nx_id;
        if (wp_next_scheduled(self::POLL_HOOK, [$nx_id])) {
            return;
        }
        // Back off gently: 30s, 60s, 90s, then every 2 minutes.
        wp_schedule_single_event(time() + min(30 * ($polls + 1), 120), self::POLL_HOOK, [$nx_id]);
    }

    /**
     * @hooked nx_facebook_reviews_poll
     */
    public function poll($nx_id) {
        $post = PostType::get_instance()->get_post($nx_id);
        if (empty($post['source']) || $this->id !== $post['source']) {
            return;
        }
        $this->update_data($nx_id, $post);
    }

    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
        return $post;
    }

    public function get_notification_ready($data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    /**
     * Drives one step of the Apify run: start it, poll it, or store its results.
     *
     * Each call does at most one thing and returns; the poll event brings us back.
     *
     * @param int   $nx_id
     * @param array $settings
     * @return void
     */
    public function update_data($nx_id, $settings = []) {
        if (empty($nx_id)) {
            return;
        }
        if (empty($settings)) {
            $settings = PostType::get_instance()->get_post($nx_id);
        }

        $token = Settings::get_instance()->get('settings.facebook_reviews_apify_token');
        if (empty($token)) {
            return;
        }

        $page_url = isset($settings['facebook_reviews_page_url']) ? esc_url_raw(trim($settings['facebook_reviews_page_url'])) : '';
        if (empty($page_url)) {
            return;
        }

        $limit     = $this->get_results_limit(isset($settings['facebook_reviews_count']) ? $settings['facebook_reviews_count'] : null);
        $signature = md5($page_url . '|' . $limit);
        $state     = $this->get_run_state($nx_id);

        if (!empty($state['run_id'])) {
            if (isset($state['signature']) && $state['signature'] === $signature) {
                $this->poll_run($nx_id, $state, $token, $settings);
                return;
            }
            // The page or the count changed — abandon the stale run and start over.
            $this->clear_run($nx_id, $state);
            $state = $this->get_run_state($nx_id);
        }

        // Throttle: a save and a cron tick can land within moments of each other, and
        // every run costs money for reviews we already have.
        if (!empty($state['last_success'])
            && isset($state['signature']) && $state['signature'] === $signature
            && (time() - (int) $state['last_success']) < $this->get_cache_duration() * MINUTE_IN_SECONDS) {
            return;
        }

        $this->start_run($nx_id, $page_url, $limit, $signature, $token, $state);
    }

    protected function start_run($nx_id, $page_url, $limit, $signature, $token, $state = []) {
        $response = Helper::remote_post(
            $this->api_base . 'acts/' . self::ACTOR . '/runs?token=' . rawurlencode($token),
            [
                'startUrls'    => [['url' => $page_url]],
                'resultsLimit' => $limit,
            ]
        );

        if (empty($response->data->id)) {
            $this->log_error($nx_id, !empty($response->error->message) ? $response->error->message : 'Could not start the Apify run.', $state);
            return;
        }

        $state['run_id']     = $response->data->id;
        $state['dataset_id'] = isset($response->data->defaultDatasetId) ? $response->data->defaultDatasetId : '';
        $state['started_at'] = time();
        $state['polls']      = 0;
        $state['signature']  = $signature;
        $state['last_error'] = '';

        $this->set_run_state($nx_id, $state);
        $this->schedule_poll($nx_id, 0);
    }

    protected function poll_run($nx_id, $state, $token, $settings = []) {
        $response = Helper::remote_get($this->api_base . 'actor-runs/' . rawurlencode($state['run_id']) . '?token=' . rawurlencode($token));
        $status   = isset($response->data->status) ? $response->data->status : '';

        if ('SUCCEEDED' === $status) {
            if (empty($state['dataset_id']) && !empty($response->data->defaultDatasetId)) {
                $state['dataset_id'] = $response->data->defaultDatasetId;
            }
            $this->store_results($nx_id, $state, $token, $settings);
            return;
        }

        if (in_array($status, ['READY', 'RUNNING'], true)) {
            $polls = (int) $state['polls'] + 1;
            if ($polls >= self::MAX_POLLS) {
                $this->log_error($nx_id, 'The Apify run did not finish in time.', $state, true);
                return;
            }
            $state['polls'] = $polls;
            $this->set_run_state($nx_id, $state);
            $this->schedule_poll($nx_id, $polls);
            return;
        }

        $message = !empty($response->error->message)
            ? $response->error->message
            /* translators: %s: Apify run status */
            : sprintf(__('The Apify run ended with status %s.', 'notificationx'), $status ? $status : 'UNKNOWN');
        $this->log_error($nx_id, $message, $state, true);
    }

    protected function store_results($nx_id, $state, $token, $settings = []) {
        $items = Helper::remote_get(
            $this->api_base . 'datasets/' . rawurlencode($state['dataset_id']) . '/items?clean=true&format=json&token=' . rawurlencode($token),
            [],
            false,
            true
        );

        if (!is_array($items) || empty($items)) {
            // Keep whatever is already showing rather than emptying the popup.
            $this->log_error($nx_id, 'The Apify run returned no recommendations.', $state, true);
            return;
        }

        $entries = $this->prepare_entries($nx_id, $items, $settings);
        if (empty($entries)) {
            $this->log_error($nx_id, 'No usable recommendations in the Apify results.', $state, true);
            return;
        }

        // Only now is it safe to drop the previous batch.
        $this->delete_notification(null, $nx_id);
        $this->update_notifications($entries);

        $state['last_success'] = time();
        $this->clear_run($nx_id, $state);
    }

    /**
     * Maps Apify dataset items onto review entries.
     *
     * @param int   $nx_id
     * @param array $items
     * @param array $settings Campaign settings; Pro reads its filter/language options
     *                        from the same array.
     * @return array
     */
    public function prepare_entries($nx_id, $items, $settings = []) {
        $entries = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $user = isset($item['user']) && is_array($item['user']) ? $item['user'] : [];
            $id   = isset($item['id']) ? $item['id'] : (isset($item['legacyId']) ? $item['legacyId'] : '');
            if (empty($id)) {
                continue;
            }

            $recommended = !empty($item['isRecommended']);
            $timestamp   = !empty($item['date']) ? strtotime($item['date']) : 0;

            $entries[] = [
                'nx_id'     => $nx_id,
                'source'    => $this->id,
                'entry_key' => md5((isset($item['facebookId']) ? $item['facebookId'] : '') . $id),
                'data'      => [
                    'username'          => isset($user['name']) ? $user['name'] : '',
                    'profile_photo_url' => isset($user['profilePic']) ? $user['profilePic'] : '',
                    'place_name'        => isset($item['pageName']) ? $item['pageName'] : '',
                    'place_review'      => isset($item['text']) ? $item['text'] : '',
                    // Facebook has no stars; synthesise one so star themes still render.
                    'rating'            => $recommended ? 5 : 1,
                    'is_recommended'    => $recommended,
                    'recommendation'    => $recommended
                        ? __('Recommends', 'notificationx')
                        : __('Doesn\'t recommend', 'notificationx'),
                    'tags'              => isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : [],
                    'likes'             => isset($item['likesCount']) ? (int) $item['likesCount'] : 0,
                    'comments'          => isset($item['commentsCount']) ? (int) $item['commentsCount'] : 0,
                    'url'               => isset($item['url']) ? $item['url'] : (isset($item['facebookUrl']) ? $item['facebookUrl'] : ''),
                    'timestamp'         => $timestamp ? $timestamp : time(),
                ],
            ];
        }

        $sort = isset($settings['facebook_reviews_sort']) ? $settings['facebook_reviews_sort'] : 'newest';

        return $this->sort_entries($entries, $sort);
    }

    /**
     * The actor returns items unsorted and offers no sort input, so ordering is ours
     * to do. Newest first is the default.
     *
     * @param array  $entries
     * @param string $sort
     * @return array
     */
    protected function sort_entries($entries, $sort = 'newest') {
        usort($entries, function ($a, $b) use ($sort) {
            $x = $a['data'];
            $y = $b['data'];
            switch ($sort) {
                case 'oldest':
                    return $x['timestamp'] <=> $y['timestamp'];
                case 'most_liked':
                    // Ties fall back to newest so the order stays deterministic.
                    return ($y['likes'] <=> $x['likes']) ?: ($y['timestamp'] <=> $x['timestamp']);
                default:
                    return $y['timestamp'] <=> $x['timestamp'];
            }
        });

        return $entries;
    }

    /**
     * Render-time formatting. Deriving the recommendation label here rather than
     * trusting the stored copy keeps it in the site's current language even for
     * entries collected before a locale change.
     *
     * @param array $saved_data
     * @param array $settings
     * @return array
     */
    public function conversion_data($saved_data, $settings) {
        if (isset($saved_data['is_recommended'])) {
            $saved_data['recommendation'] = $saved_data['is_recommended']
                ? __('Recommends', 'notificationx')
                : __('Doesn\'t recommend', 'notificationx');
        }

        if (!empty($settings['facebook_reviews_page_label'])) {
            $saved_data['place_name'] = $settings['facebook_reviews_page_label'];
        }

        if (!empty($saved_data['place_review'])) {
            $theme       = isset($settings['themes']) ? $settings['themes'] : '';
            $trim_length = 100;
            if ("{$this->id}_review-comment-2" === $theme || "{$this->id}_review-comment-3" === $theme) {
                $trim_length = 80;
            }
            // Pro's content_trim_length control rides this filter.
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

    /**
     * Records a failure on the campaign's run state. Only the message is logged —
     * never the request URL, which carries the Apify token.
     */
    protected function log_error($nx_id, $message, $state = [], $clear = false) {
        if ($clear) {
            $this->clear_run($nx_id, $state, $message);
        } else {
            $state['last_error'] = $message;
            $this->set_run_state($nx_id, $state);
        }
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Reviewed for the NotificationX codebase: acceptable in this context.
        error_log("NX {$this->id} ({$nx_id}): {$message}");
    }

    public function product_link($link, $post, $entry) {
        if (isset($post['link_type'], $entry['url']) && 'review_page' === $post['link_type']) {
            $link = $entry['url'];
        }
        return $link;
    }

    public function notification_image($image_data, $data, $settings) {
        if (empty($settings['show_default_image'])) {
            $image_url = '';
            switch ($settings['show_notification_image']) {
                case 'fbreview_avatar':
                    $image_url = isset($data['profile_photo_url']) ? $data['profile_photo_url'] : '';
                    break;
                case 'fbreview_icon':
                    $image_url = NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.png';
                    break;
            }
            $image_data['url'] = $image_url;
        }

        $image_data['alt'] = isset($data['username']) ? $data['username'] : '';

        return $image_data;
    }

    public function doc() {
        $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#facebook_reviews_settings_section');
        /* translators: %1$s: NotificationX API integrations settings URL, %2$s: Apify token documentation URL, %3$s: Facebook Reviews integration documentation URL */
        return sprintf(__('<p>Make sure that you have configured your <a target="_blank" href="%1$s">Apify API token</a>, to showcase your Facebook recommendations. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>

		<p>👉NotificationX <a target="_blank" href="%3$s">Integration with Facebook Reviews</a>.</p>', 'notificationx'),
        $url,
        'https://notificationx.com/docs/collect-api-token-from-apify',
        'https://notificationx.com/docs/facebook-reviews-with-notificationx/'
        );
    }
}
