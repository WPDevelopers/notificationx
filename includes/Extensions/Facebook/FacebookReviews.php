<?php
/**
 * Facebook Reviews Extension
 *
 * Shows the aggregate rating of a Facebook Page ("128 people rated 4.8 on
 * Facebook"). Meta stopped exposing individual Page reviews/recommendations
 * in Graph API v22.0 (error 12), so only `overall_star_rating` and
 * `rating_count` are available — see docs/extensions/facebook-reviews.md.
 *
 * The Page is connected from NotificationX > Settings > API Integrations with
 * the site owner's own Meta app (App ID / App Secret). All Graph API access,
 * OAuth and token storage live in the Pro extension.
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * FacebookReviews Extension
 * @method static FacebookReviews get_instance($args = null)
 */
class FacebookReviews extends Extension {
    /**
     * Instance of FacebookReviews
     *
     * @var FacebookReviews
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'facebook_reviews';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/facebook-reviews.svg';
    public $doc_link        = 'https://notificationx.com/docs/facebook-reviews-with-notificationx/';
    public $types           = 'reviews';
    public $module          = 'modules_facebook_reviews';
    public $module_priority = 21;
    public $default_theme   = 'facebook_reviews_total-rated';
    public $is_pro          = true;
    public $link_type       = 'facebook_page';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __('Facebook Reviews', 'notificationx');
        $this->module_title = __('Facebook Reviews', 'notificationx');
        $this->themes = [
            'total-rated'      => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/facebook/total-rated.png',
                'image_shape'             => 'square',
                'show_notification_image' => 'fbreview_icon',
                'template'                => [
                    'first_param'         => 'tag_rated',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('people rated', 'notificationx'),
                    'third_param'         => 'tag_page_name',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'reviewed'         => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/facebook/reviewed.png',
                'image_shape'             => 'circle',
                'show_notification_image' => 'fbreview_picture',
                'template'                => [
                    'first_param'         => 'tag_reviewer',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('recommends', 'notificationx'),
                    'third_param'         => 'tag_page_name',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'review-comment'   => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/facebook/review-comment.png',
                'image_shape'             => 'rounded',
                'show_notification_image' => 'fbreview_picture',
                'template'                => [
                    'first_param'         => 'tag_reviewer',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('reviewed', 'notificationx'),
                    'third_param'         => 'tag_review_content',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_recommendation',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'review-comment-2' => [
                'source'                  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/facebook/review-comment-2.png',
                'image_shape'             => 'circle',
                'show_notification_image' => 'fbreview_picture',
                'template'                => [
                    'first_param'         => 'tag_reviewer',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('recommends', 'notificationx'),
                    'third_param'         => 'tag_review_content',
                    'custom_third_param'  => __('Anonymous Page', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => '',
                ],
            ],
        ];

        $this->templates = [
            "{$this->id}_template_new" => [
                'first_param'  => [
                    'tag_reviewer' => __('Reviewer (anonymous)', 'notificationx'),
                    'tag_rated'    => __('Rated', 'notificationx'),
                ],
                'third_param'  => [
                    'tag_page_name'      => __('Page Name', 'notificationx'),
                    'tag_review_content' => __('Review', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_rating'         => __('Rating', 'notificationx'),
                    'tag_recommendation' => __('Recommendation', 'notificationx'),
                    'tag_time'           => __('Definite Time', 'notificationx'),
                ],
                '_themes'      => [
                    "{$this->id}_total-rated",
                    "{$this->id}_reviewed",
                    "{$this->id}_review-comment",
                    "{$this->id}_review-comment-2",
                ],
            ],
        ];

        $this->popup = [
            "denyButtonText"    => __("<a href='https://notificationx.com/docs/facebook-reviews-with-notificationx/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings -- Reviewed for the NotificationX codebase: acceptable in this context.
            "html"              => __('
                <span>Showcase your Facebook Page rating and recommendations to build trust with your visitors.</span>
            ', 'notificationx'),
        ];
    }

    public function preview_entry($entry, $settings) {
        $entry = array_merge($entry, [
            'reviewer'       => __('Someone', 'notificationx'),
            'page_name'      => __('Example Page', 'notificationx'),
            'review_content' => __('Excellent service, highly recommended!', 'notificationx'),
            'recommendation' => 'recommends::1',
            'rated'          => 128,
            'rating'         => 4.8,
        ]);
        if (isset($settings['show_notification_image']) && $settings['show_notification_image'] === 'fbreview_icon') {
            $entry = array_merge($entry, [
                'image_data' => [
                    'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/facebook-f-icon.svg',
                    'alt'     => '',
                    'classes' => 'fbreview_icon',
                ],
            ]);
        }
        return $entry;
    }

    public function doc() {
        $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#facebook_reviews_settings_section');
        /* translators: %1$s: settings page URL, %2$s: Meta app documentation URL, %3$s: integration documentation URL */
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">connected your Facebook Page</a> using your own Meta app, to showcase your Page rating. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>

		<p>👉NotificationX <a target="_blank" href="%3$s">Integration with Facebook Reviews</a>.</p>', 'notificationx'),
            $url,
            'https://notificationx.com/docs/create-a-meta-app-for-facebook-reviews/',
            'https://notificationx.com/docs/facebook-reviews-with-notificationx/'
        );
    }
}
