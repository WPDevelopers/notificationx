<?php
/**
 * YouTube Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Google;

use NotificationX\Admin\Settings;
use NotificationX\Core\GetData;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use UsabilityDynamics\Settings as UsabilityDynamicsSettings;

/**
 * YouTube Extension
 * @method static YouTube get_instance($args = null)
 */
class YouTube extends Extension {
    /**
     * Instance of YouTube
     *
     * @var YouTube
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'youtube';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/google-rating.png';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'social';
    public $module          = 'modules_google_youtube';
    public $module_priority = 25;
    public $default_theme   = 'youtube_channel-1';
    public $is_pro          = true;
    // public $link_type       = 'map_page';
    public $api_base        = 'https://youtube.googleapis.com/youtube/v3/';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('YouTube', 'notificationx');
        $this->module_title = __('YouTube', 'notificationx');
        $this->themes = [
            'channel-1'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-rated.png',
                'template'                => [
                    // 'first_param'         => 'tag_rated',
                    // 'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('Join ', 'notificationx'),
                    'third_param'         => 'tag_yt_channel_title',
                    'custom_third_param'  => __('', 'notificationx'),
                    'yt_third_label'      => __('YouTube Channel', 'notificationx'),
                    'fourth_param'        => 'tag_yt_views',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                    'yt_fourth_label'     => __('Views', 'notificationx'),
                    'fifth_param'         => 'tag_yt_videos',
                    'yt_fifth_label'      => __('Videos', 'notificationx'),
                    'sixth_param'         => 'tag_yt_subscribers',
                    'yt_sixth_label'      => __('Subscribers', 'notificationx'),
                ],
                'defaults'                => [
                    'youtube_type'            => 'channel',
                    'image_shape'             => 'square',
                    'show_notification_image' => 'yt_thumbnail',
                ],
            ],
            'video-1'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-rated.png',
                'template'                => [
                    'second_param'       => __('Check our latest work ', 'notificationx'),
                    'third_param'        => 'tag_yt_views',
                    'custom_third_param' => __('', 'notificationx'),
                    'yt_third_label'     => __('Views', 'notificationx'),
                    'fourth_param'       => 'tag_yt_likes',
                    'yt_fourth_label'    => __('Videos', 'notificationx'),
                    'fifth_param'        => 'tag_yt_comments',
                    'yt_fifth_label'     => __('Subscribers', 'notificationx'),
                ],
                'defaults'                => [
                    'youtube_type'            => 'video',
                    'image_shape'             => 'square',
                    'show_notification_image' => 'yt_thumbnail',
                ],
            ],
        ];

        $this->templates = [
            "{$this->id}_channel"  => [
                'third_param' => [
                    'tag_yt_channel_title' => __('Channel Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_yt_views' => __('Total Views', 'notificationx'),
                    'tag_none'     => __('None', 'notificationx'),
                ],
                'fifth_param' => [
                    'tag_yt_videos' => __('Total Videos', 'notificationx'),
                    'tag_none'      => __('None', 'notificationx'),
                ],
                'sixth_param' => [
                    'tag_yt_subscribers' => __('Total Subscriber', 'notificationx'),
                    'tag_none'           => __('None', 'notificationx'),
                ],
                '_themes' => [
                    "{$this->id}_channel-1",
                ],
            ],
            "{$this->id}_video"  => [
                'third_param' => [
                    'tag_yt_views' => __('Total Views', 'notificationx'),
                    'tag_none'     => __('None', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_yt_likes' => __('Total Likes', 'notificationx'),
                    'tag_none'     => __('None', 'notificationx'),
                ],
                'fifth_param' => [
                    'tag_yt_comments' => __('Total Comments', 'notificationx'),
                    'tag_none'        => __('None', 'notificationx'),
                ],
                '_themes' => [
                    "{$this->id}_video-1",
                ],
            ],
        ];
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/google-reviews-with-notificationx/
            ' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Google reviews provide helpful information and make your business stand out.</span>
            ', 'notificationx')
        ];
        parent::__construct();
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        // settings page
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_notification_template', [ $this, 'youtube_templates' ], 7 );
        add_filter('nx_content_fields', [$this, 'content_fields']);
        add_filter('nx_show_image_options', [$this, 'show_image_options']);
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function youtube_templates( $template ) {
        $template['yt_third_label'] = [
            'name'     => 'yt_third_label',
            'type'     => 'text',
            'priority' => 27,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        $template['yt_fourth_label'] = [
            'name'     => 'yt_fourth_label',
            'type'     => 'text',
            'priority' => 37,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        $template['yt_fifth_label'] = [
            'name'     => 'yt_fifth_label',
            'type'     => 'text',
            'priority' => 47,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        $template['yt_sixth_label'] = [
            'name'     => 'yt_sixth_label',
            'type'     => 'text',
            'priority' => 57,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        return $template;
    }

    /**
     * @param array $sections
     * @return array
     */
    public function api_integration_settings($sections) {
        $sections['google_youtube_settings_section'] = array(
            'name'     => 'google_youtube_settings_section',
            'type'     => 'section',
            'label'    => __('YouTube Settings', 'notificationx-pro'),
            'modules'  => 'modules_google_youtube',
            'priority' => 80,
            'rules'    => Rules::is('modules.modules_google_youtube', true),
            'fields'   => [
                'google_youtube_cache_duration' => [
                    'name' => 'google_youtube_cache_duration',
                    'type'        => 'number',
                    'label'       => __('Cache Duration', 'notificationx-pro'),
                    'default'     => 30,
                    'min'     => 30,
                    'description' => __('Minutes, scheduled duration for collect new data. Estimated cost per month around $25 for every 30 minute.', 'notificationx-pro'),
                ],
                'google_youtube_api_key' => array(
                    'name'  => 'google_youtube_api_key',
                    'type'  => 'text',
                    'text'  => __('API Key', 'notificationx-pro'),
                    'label' => __('API Key', 'notificationx-pro'),
                    'description' => sprintf('%s <a href="%s" target="_blank">%s</a>.',
                        __('To get an API key, check out', 'notificationx-pro'),
                        'https://notificationx.com/docs/collect-api-key-from-google-console',
                        __(' this doc', 'notificationx-pro')
                    ),
                ),
                [
                    'name' => 'google_youtube_connect',
                    // 'label' => 'Connect Button',
                    'type' => 'button',
                    'default' => false,
                    'text' => [
                        'normal'  => __('Validate', 'notificationx-pro'),
                        'saved'   => __('Refresh', 'notificationx-pro'),
                        'loading' => __('Validating...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on' => 'click',
                        'api' => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'                        => $this->id,
                            'google_youtube_cache_duration' => '@google_youtube_cache_duration',
                            'google_youtube_api_key'        => '@google_youtube_api_key',
                        ],
                    ]
                ],
            ],
        );
        return $sections;
    }

    /**
     * Get data for WooCommerce Extension.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];

        $content_fields['youtube_type'] = [
            'label'    => __('Type', 'notificationx'),
            'name'     => 'youtube_type',
            'type'     => 'select',
            'priority' => 79,
            'default'  => 'channel',
            'options'  => GlobalFields::get_instance()->normalize_fields([
                'channel' => __('Channel', 'notificationx'),
                'video'   => __('Video', 'notificationx'),
            ]),
            'rules'    => ['is', 'source', $this->id],
        ];

        $content_fields['youtube_id'] = [
            'label'    => __('ID or Username', 'notificationx'),
            'name'     => 'youtube_id',
            'type'     => 'text',
            'priority' => 80,
            'rules'    => ['is', 'source', $this->id]
        ];
        return $fields;
    }

    public function connect($params) {
        if (!empty($params['google_youtube_api_key'])) {
            Settings::get_instance()->set('settings.google_review_cache_duration', $params['google_review_cache_duration'] ? $params['google_review_cache_duration'] : 30);
            Settings::get_instance()->set('settings.google_youtube_api_key', $params['google_youtube_api_key']);
            $api_key = $params['google_youtube_api_key'];
            if (!empty($api_key)) {
                $query = http_build_query( [
                    'part'        => 'snippet,contentDetails,statistics',
                    'forUsername' => 'GoogleDevelopers',
                    'key'         => $api_key,
                ] );

                $channel_data = Helper::remote_get( $this->api_base . 'channels?' . $query );

                if(isset($channel_data->kind) && "youtube#channelListResponse" === $channel_data->kind){
                    return array(
                        'status' => 'success',
                    );
                }

            }
        }
        return array(
            'status' => 'error',
            'message' => __('Please insert a valid API key.', 'notificationx-pro')
        );
    }


    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    public function update_data($nx_id, $data = array()) {
        if (empty($nx_id)) {
            return;
        }
        if (empty($data)) {
            $data = PostType::get_instance()->get_post($nx_id);
        }

        if(!isset($data['youtube_type'], $data['youtube_id'])){
            return;
        }


        $youtube_stats = $this->get_stats($data['youtube_type'], $data['youtube_id']);

        // removing old notifications.
        $this->delete_notification(null, $nx_id);
        $entries = [];
        foreach ($youtube_stats as $stats) {
            // $review = array_merge($review, $plugin_data);
            $entries[] = [
                'nx_id'      => $nx_id,
                'source'     => $this->id,
                'entry_key'  => $stats['kind'] . '_' . $stats['id'],
                'data'       => $stats,
            ];
        }
        $this->update_notifications($entries);
        return $entries;
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data = array()) {
        if (!is_null($data['nx_id'])) {
            return $this->update_data($data['nx_id'], $data);
        }
        return [];
    }

    /**
     * Undocumented function
     *
     * @param int $nx_id
     * @param array $data
     * @return array
     */
    public function get_stats($youtube_type, $youtube_id){
        $result = [];
        $api_key = Settings::get_instance()->get('settings.google_youtube_api_key');

        $query = [
            'part' => 'snippet,contentDetails,statistics',
            'key'  => $api_key,
        ];

        if('channel' === $youtube_type){
            if(strpos($youtube_id, '@') === 0){
                $query['forUsername'] = ltrim($youtube_id, '@');
            }
            else{
                $query['id'] = $youtube_id;
            }

            $transient_key = "nx_{$this->id}" . md5(http_build_query($query));
            $place_data    = get_transient($transient_key);

            if(empty($place_data)){
                $place_data = Helper::remote_get( $this->api_base . 'channels?' . http_build_query($query), [], false, true );
                set_transient($transient_key, $place_data, HOUR_IN_SECONDS);
            }


        }
        else if('video' === $youtube_type){
            $query['id']   = $youtube_id;
            $transient_key = "nx_{$this->id}" . md5(http_build_query($query));
            $place_data    = get_transient($transient_key);

            if(empty($place_data)){
                $place_data = Helper::remote_get( $this->api_base . 'videos?' . http_build_query($query), [], false, true );
                set_transient($transient_key, $place_data, HOUR_IN_SECONDS);
            }

        }

        if(!empty($place_data['items']) && is_array($place_data['items'])){
            foreach ($place_data['items'] as $item) {
                $item = new UsabilityDynamicsSettings(['data' => $item]);
                $result[] = [
                    'kind'             => $item->get('kind'),
                    'etag'             => $item->get('etag'),
                    'id'               => $item->get('id'),
                    'yt_channel_title' => $item->get('snippet.title'),
                    'description'      => $item->get('snippet.description'),
                    'publishedAt'      => $item->get('snippet.publishedAt'),
                    'image'            => $item->get('snippet.thumbnails.default.url', ''),
                    '_yt_views'        => $item->get('statistics.viewCount', 0),
                    '_yt_subscribers'  => $item->get('statistics.subscriberCount', 0),
                    '_yt_videos'       => $item->get('statistics.videoCount', 0),
                    '_yt_likes'        => $item->get('statistics.likeCount', 0),
                    '_yt_comments'     => $item->get('statistics.commentCount', 0),
                    '_yt_favorites'    => $item->get('statistics.favoriteCount', 0),
                ];
            }
        }
        return $result;
    }

    public function preview_entry($entry, $settings){
        if($settings['show_notification_image'] === "greview_icon" ){
            $entry = array_merge($entry, [
                "image_data"        => [
                    "url"     => "https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/generic_business-71.png",
                    "alt"     => "",
                    "classes" => "greview_icon"
                ],
            ]);
        }
        return $entry;
    }

    public function show_image_options( $options ) {
        $options['yt_thumbnail'] = [
            'value' => 'yt_thumbnail',
            'label' => __( 'Thumbnail', 'notificationx-pro' ),
            'rules' => ['is', 'source', $this->id],
        ];
        return $options;
    }

    /**
     * Image action callback
     * @param array $image_data
     * @param array $data
     * @param stdClass $settings
     * @return array
     */
    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image'] && $settings['show_notification_image'] === 'yt_thumbnail') {
            $image_data['url'] = $data['image'];
            $image_data['alt'] = $data['yt_channel_title'];
        }
        return $image_data;
    }

    /**
     * @param array $data
     * @param array $saved_data
     * @param stdClass $settings
     * @return array
     */
    public function fallback_data($data, $saved_data, $settings) {
        $data['title']          = $saved_data['yt_channel_title'];
        // channel
        $data['yt_views']       = Helper::nice_number($saved_data['_yt_views']);
        $data['yt_subscribers'] = Helper::nice_number($saved_data['_yt_subscribers']);
        $data['yt_videos']      = Helper::nice_number($saved_data['_yt_videos']);
        // single video
        $data['yt_likes']      = Helper::nice_number($saved_data['_yt_likes']);
        $data['yt_comments']   = Helper::nice_number($saved_data['_yt_comments']);
        $data['yt_favorites']  = Helper::nice_number($saved_data['_yt_favorites']);
        return $data;
    }

    public function doc(){
        $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_youtubes_settings_section');
        return sprintf(__('<p>Make sure that you have configured your <a target="_blank" href="%1$s">Google Reviews API</a> key, to showcase your reviews. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>

		<p>👉NotificationX <a target="_blank" href="%3$s">Integration with Google Reviews</a>.</p>', 'notificationx'),
        $url,
        'https://notificationx.com/docs/collect-api-key-from-google-console',
        'https://notificationx.com/docs/google-reviews-with-notificationx/'
        );
    }
}
