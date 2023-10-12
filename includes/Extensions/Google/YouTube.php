<?php
/**
 * YouTube Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Google;

use NotificationX\Core\Helper;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

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
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/google-rating.png';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'video';
    public $module          = 'modules_google_youtube';
    public $module_priority = 25;
    public $default_theme   = 'youtube_channel-1';
    public $is_pro          = true;
    public $link_type       = 'yt_channel_link';
    public $api_base        = 'https://youtube.googleapis.com/youtube/v3/';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/youtube.png';
    public $cron_schedule   = 'nx_youtube_interval';
    public $channel_themes  = ['youtube_channel-1', 'youtube_channel-2'];
    public $video_themes    = ['youtube_video-1', 'youtube_video-2', 'youtube_video-3', 'youtube_video-4'];

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('YouTube', 'notificationx');
        $this->module_title = __('YouTube', 'notificationx');
        $this->themes = [
            'channel-1'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/youtube/channel-theme-1.png',
                'template'                => [
                    // 'first_param'         => 'tag_rated',
                    // 'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('Follow ', 'notificationx'),
                    'third_param'         => 'tag_yt_channel_title',
                    'custom_third_param'  => __('Anonymous Title', 'notificationx'),
                    'yt_third_label'      => __('YouTube Channel', 'notificationx'),
                    'fourth_param'        => 'tag_yt_views',
                    'custom_fourth_param' => '3.4M+',
                    'yt_fourth_label'     => __('Views', 'notificationx'),
                    'fifth_param'         => 'tag_yt_videos',
                    'custom_fifth_param'  => '564',
                    'yt_fifth_label'      => __('Videos', 'notificationx'),
                ],
                'defaults'                => [
                    // 'youtube_type'            => 'channel',
                    'image_shape'             => 'rounded',
                    'link_type'               => 'yt_channel_link',
                    'show_notification_image' => 'yt_thumbnail',
                    'link_button_text'        => __('Subscribe Now','notificationx'),
                    'link_button'             => true,
                ],
            ],
            'channel-2'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/youtube/channel-theme-2.png',
                'template'                => [
                    // 'first_param'         => 'tag_rated',
                    // 'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('Follow ', 'notificationx'),
                    'third_param'         => 'tag_yt_channel_title',
                    'custom_third_param'  => __('Anonymous Title', 'notificationx'),
                    'yt_third_label'      => __('YouTube Channel', 'notificationx'),
                    'fourth_param'        => 'tag_yt_views',
                    'custom_fourth_param' => '3.4M+',
                    'fifth_param'         => 'tag_yt_videos',
                    'custom_fifth_param'  => '564',
                ],
                'defaults'                => [
                    // 'youtube_type'            => 'channel',
                    'image_shape'             => 'circle',
                    'link_type'               => 'yt_channel_link',
                    'show_notification_image' => 'yt_thumbnail',
                    'link_button_text'        => __('Subscribe Now','notificationx'),
                    'link_button'             => true,
                ],
            ],
            'video-1'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/youtube/video-theme-1.png',
                'template'                => [
                    'second_param'        => __('Check our latest video ', 'notificationx'),
                    'third_param'         => 'tag_yt_views',
                    'custom_third_param'  => '3.4M+',
                    'fourth_param'        => 'tag_yt_likes',
                    'custom_fourth_param' => '2.5K',
                    'fifth_param'         => 'tag_yt_comments',
                    'custom_fifth_param'  => '1K',
                ],
                'defaults'                => [
                    // 'youtube_type'            => 'video',
                    'image_shape'             => 'circle',
                    'link_type'               => 'yt_video_link',
                    'show_notification_image' => 'yt_thumbnail',
                    'link_button_text'        => __('Watch Now','notificationx'),
                ],
            ],
            'video-2'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/youtube/video-theme-2.png',
                'template'                => [
                    'second_param'       => __('Check our latest video ', 'notificationx'),
                    'third_param'        => 'tag_yt_views',
                    'custom_third_param'  => '3.4M+',
                    'yt_third_label'     => __('Views', 'notificationx'),
                    'fourth_param'       => 'tag_yt_likes',
                    'custom_fourth_param' => '2.5K',
                    'yt_fourth_label'    => __('Likes', 'notificationx'),
                    'fifth_param'        => 'tag_yt_comments',
                    'yt_fifth_label'     => __('Comments', 'notificationx'),
                    'custom_fifth_param'  => '1K',
                ],
                'defaults'                => [
                    // 'youtube_type'            => 'video',
                    'image_shape'             => 'rounded',
                    'link_type'               => 'yt_video_link',
                    'show_notification_image' => 'yt_thumbnail',
                    'link_button_text'        => __('Watch Now','notificationx'),
                ],
            ],
            'video-3'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/youtube/video-theme-3.png',
                'template'                => [
                    'second_param'       => __('Check our latest video ', 'notificationx'),
                    'third_param'        => 'tag_yt_views',
                    'custom_third_param'  => '3.4M+',
                    'fourth_param'       => 'tag_yt_likes',
                    'custom_fourth_param' => '2.5K',
                    'fifth_param'        => 'tag_yt_comments',
                    'custom_fifth_param'  => '1K',
                ],
                'defaults'                => [
                    // 'youtube_type'            => 'video',
                    'image_shape'             => 'circle',
                    'link_type'               => 'yt_video_link',
                    'show_notification_image' => 'yt_thumbnail',
                    'link_button_text'        => __('Watch Now','notificationx'),
                    'link_button'             => true,
                ],
            ],
            'video-4'     => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/youtube/video-theme-4.png',
                'template'                => [
                    'second_param'       => __('Check our latest video ', 'notificationx'),
                    'third_param'        => 'tag_yt_views',
                    'custom_third_param'  => '3.4M+',
                    'yt_third_label'     => __('Views', 'notificationx'),
                    'fourth_param'       => 'tag_yt_likes',
                    'custom_fourth_param' => '2.5K',
                    'yt_fourth_label'    => __('Links', 'notificationx'),
                    'fifth_param'        => 'tag_yt_comments',
                    'yt_fifth_label'     => __('Comments', 'notificationx'),
                    'custom_fifth_param'  => '1K',
                ],
                'defaults'                => [
                    // 'youtube_type'            => 'video',
                    'image_shape'             => 'rounded',
                    'link_type'               => 'yt_video_link',
                    'show_notification_image' => 'yt_thumbnail',
                    'link_button_text'        => __('Watch Now','notificationx'),
                    'link_button'             => true,
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
                '_themes' => [
                    "{$this->id}_channel-1",
                    "{$this->id}_channel-2",
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
                    "{$this->id}_video-2",
                    "{$this->id}_video-3",
                    "{$this->id}_video-4",
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
        // add_action('admin_init', array($this, 'init_google_client'));
        parent::__construct();
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
        $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_youtube_settings_section');
        return sprintf(__('<p>To create YouTube notification popups, make sure that you have configured your <a target="_blank" href="%1$s">YouTube API</a> key, Check out our step-by-step documentation for further assistance. <a target="_blank" href="%2$s">documentation</a>.</p>

		<p>👉NotificationX <a target="_blank" href="%3$s">Integration with Youtube</a>.</p>', 'notificationx'),
        $url,
        'https://notificationx.com/docs/collect-youtube-api-key/',
        'https://notificationx.com/docs/youtube-video-activities-popups/'
        );
    }
}
