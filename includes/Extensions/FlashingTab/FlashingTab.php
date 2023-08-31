<?php

/**
 * flashing Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\FlashingTab;

use NotificationX\Core\Helper;
use NotificationX\Core\Locations;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;

/**
 * flashing Extension
 * @method static FlashingTab get_instance($args = null)
 */
class FlashingTab extends Extension {
    /**
     * Instance of flashing
     *
     * @var FlashingTab
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'flashing_tab';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/contact-form-submission-alert/';
    public $types           = 'flashing_tab';
    // used in Settings > General tab
    public $module          = 'modules_flashing';
    public $module_priority = 30;
    public $default_theme   = 'flashing_tab_theme-1';
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->title = __('Flashing Tab', 'notificationx');
        $this->module_title = __('Flashing Tab', 'notificationx');
        $this->themes = [
            'theme-1' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-1.gif',
                'defaults'        => [
                    'ft_theme_one_icons' => [
                        'icon-one' => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-1.png',
                        'icon-two' => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-2.png',
                    ],
                    'ft_theme_one_message' => __('Comeback!', 'notificationx'),
                ],
            ),
            'theme-2' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-2.gif',
                'defaults'        => [
                    'ft_theme_one_icons' => [
                        'icon-one' => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-2-icon-1.png',
                        'icon-two' => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-2-icon-2.png',
                    ],
                    'ft_theme_one_message' => __('Comeback! We miss you.', 'notificationx'),
                ],
            ),
            'theme-3' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-3.gif',
                'defaults'        => [
                    'ft_theme_three_line_one' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-3-icon-1.png',
                        'message' => __('Comeback!', 'notificationx'),
                    ],
                    'ft_theme_three_line_two' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-3-icon-2.png',
                        'message' => __('You forgot to purchase!', 'notificationx'),
                    ],
                ],
            ),
            'theme-4' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-4.gif',
                'defaults'        => [
                    // 'ft_theme_three_line_one' => 'dddddd',
                    'ft_theme_three_line_one' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-1.png',
                        'message' => __('Comeback!', 'notificationx'),
                    ],
                    'ft_theme_four_line_two' => [
                        'is-show-empty' => false,
                        'default'       => [
                            'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-2.png',
                            'message' => __('{quantity} items in your cart!', 'notificationx'),
                        ],
                        'alternative' => [
                            'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-2.png',
                            'message' => '',
                        ],
                    ],
                ],
            ),
        ];

        parent::__construct();
    }

    public function init_fields(){
        parent::init_fields();

        add_filter( 'nx_metabox_tabs', [ $this, 'nx_tabs' ], 15 );
    }

    /**
     * Undocumented function
     *
     * @param [type] $tabs
     * @return void
     */
    public function nx_tabs( $tabs ) {
        $tabs['display_tab']   = Rules::is( 'source', $this->id, true, $tabs['display_tab'] );
        $tabs['customize_tab'] = Rules::is( 'source', $this->id, true, $tabs['customize_tab'] );
        return $tabs;
    }

    public function doc(){
        // translators: links
        return sprintf(__('
        <p>Make sure that you have NotificationX PRO installed and activated on your website to use Flashing Tab. For further assistance, follow the step-by-step <a target="_blank" href="%1$s">documentation</a>.</p>
		<p>🎥 Get a quick demo from the <a target="_blank" href="%2$s">video tutorial</a></p>
		<p>📖 Recommended Blog:</p>
		<p>🔥How To <a target="_blank" href="%3$s">Attract Customers With Flashing Browser Tab Notification Using NotificationX?</a></p>
		', 'notificationx'),
        'https://notificationx.com/docs/flashing-tab-alerts/',
        'https://www.youtube.com/watch?v=RCyB06nI-Xc',
        'https://notificationx.com/blog/flashing-browser-tab-notifications/'
        );
    }
}
