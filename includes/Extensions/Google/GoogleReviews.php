<?php
/**
 * GoogleReviews Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Google;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * GoogleReviews Extension
 * @method static GoogleReviews get_instance($args = null)
 */
class GoogleReviews extends Extension {
    /**
     * Instance of GoogleReviews
     *
     * @var GoogleReviews
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'google_reviews';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/google-rating.png';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/
    ';
    public $types           = 'reviews';
    public $module          = 'modules_google_reviews';
    public $module_priority = 20;
    public $default_theme   = 'google_reviews_total-rated';
    public $is_pro          = true;
    public $link_type       = 'map_page';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Google Reviews', 'notificationx');
        $this->module_title = __('Google Reviews', 'notificationx');
        $this->themes = [
            'total-rated'     => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-rated.png',
                'image_shape' => 'square',
                'show_notification_image' => 'greview_icon',
                'template'  => [
                    'first_param'         => 'tag_rated',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('people rated', 'notificationx'),
                    'third_param'         => 'tag_place_name',
                    'custom_third_param'  => __('Anonymous Place', 'notificationx'),
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'reviewed'     => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/reviewed.png',
                'image_shape' => 'circle',
                'show_notification_image' => 'greview_avatar',
                'template'  => [
                    'first_param'         =>  'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_name',
                    'custom_third_param'  => __('Anonymous Place', 'notificationx'),
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review-comment' => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment.jpg',
                'image_shape' => 'rounded',
                'show_notification_image' => 'greview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_review',
                    'custom_third_param'  => __('Anonymous Place', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review-comment-2' => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-2.jpg',
                'image_shape' => 'circle',
                'show_notification_image' => 'greview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_review',
                    'custom_third_param'  => __('Anonymous Place', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => '',
                ]
            ],
            'review-comment-3' => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-3.jpg',
                'image_shape' => 'circle',
                'show_notification_image' => 'greview_avatar',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_place_review',
                    'custom_third_param'  => __('Anonymous Place', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'maps_theme' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/google-review-map.png',
                'image_shape' => 'square',
                'show_notification_image' => 'greview_map_image',
                'template'  => [
                    'first_param'         => 'tag_rated',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('people rated', 'notificationx'),
                    'third_param'         => 'tag_place_name',
                    'custom_third_param'  => __('Anonymous Place', 'notificationx'),
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ),
        ];

        $this->templates = [
            "{$this->id}_template_new"  => [
                'first_param' => [
                    'tag_username' => __('Username', 'notificationx'),
                    'tag_rated'    => __('Rated', 'notificationx'),
                ],
                'third_param' => [
                    'tag_place_name'     => __('Place Name', 'notificationx'),
                    'tag_place_review'   => __('Review', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_rating'   => __('Rating', 'notificationx'),
                    'tag_time'     => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    "{$this->id}_maps_theme",
                    "{$this->id}_total-rated",
                    "{$this->id}_reviewed",
                    "{$this->id}_review-comment",
                    "{$this->id}_review-comment-2",
                    "{$this->id}_review-comment-3",
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

    public function doc(){
        $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_reviews_settings_section');
        return sprintf(__('<p>Make sure that you have configured your <a target="_blank" href="%1$s">Google Reviews API</a> key, to showcase your reviews. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>

		<p>👉NotificationX <a target="_blank" href="%3$s">Integration with Google Reviews</a>.</p>', 'notificationx'),
        $url,
        'https://notificationx.com/docs/collect-api-key-from-google-console',
        'https://notificationx.com/docs/google-reviews-with-notificationx/'
        );
    }
}
