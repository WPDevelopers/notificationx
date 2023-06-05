<?php

/**
 * flashing Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\FlashingTab;

use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
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
                'image_shape'     => 'rounded',
                'defaults'        => [
                    'ft_message_1' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-1.png',
                        'message' => 'Comeback!',
                    ],
                    'ft_message_2' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-2.png',
                        'message' => 'Comeback!',
                    ],
                ],
            ),
            'theme-2' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-2.gif',
                'image_shape'     => 'rounded',
                'defaults'        => [
                    'ft_message_1' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-2-icon-1.png',
                        'message' => 'Comeback! We miss you.',
                    ],
                    'ft_message_2' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-2-icon-2.png',
                        'message' => 'Comeback! We miss you.',
                    ],
                ],
            ),
            'theme-3' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-3.gif',
                'image_shape'     => 'rounded',
                'defaults'        => [
                    'ft_message_1' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-3-icon-1.png',
                        'message' => 'Comeback!',
                    ],
                    'ft_message_2' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-3-icon-2.png',
                        'message' => 'You forgot to purchase!',
                    ],
                ],
            ),
            'theme-4' => array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-4.gif',
                'image_shape'     => 'rounded',
                'defaults'        => [
                    'ft_message_1' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-1.png',
                        'message' => 'Comeback!',
                    ],
                    'ft_message_2' => [
                        'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-2.png',
                        'message' => '{quantity} items in your cart!',
                    ],
                ],
            ),
        ];

        parent::__construct();
    }

    public function init(){
        parent::init();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 10);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter( 'nx_content_fields', array( $this, 'content_fields' ), 999 );
        add_filter( 'nx_design_tab_fields', array( $this, 'design_fields' ), 999 );
        add_filter( 'nx_display_fields', array( $this, 'display_fields' ), 999 );

        add_filter( 'nx_metabox_tabs', [ $this, 'nx_tabs' ], 15 );
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function public_actions() {
        parent::public_actions();

    }

    public function enqueue_scripts() {


        $posts = PostType::get_instance()->get_posts([
            'source'  => $this->id,
            'enabled' => true,
        ]);

        if(!empty($posts)){
            // @todo remove unnecessary values
            $settings = end($posts);

            wp_enqueue_script('notificationx-public-flashing-tab', Helper::file('public/js/flashing-tab.js', true), [], NOTIFICATIONX_VERSION, true);
            wp_localize_script('notificationx-public-flashing-tab', 'nx_flashing_tab', $settings);
        }

    }


    public function design_fields($fields){
        $fields['themes']['fields']['advance_edit'] = Rules::is('source', $this->id, true, $fields['themes']['fields']['advance_edit']);
        return $fields;
    }
    public function content_fields($fields){
        $content_fields = &$fields['content']['fields'];

        $content_fields['template_adv'] = Rules::is('source', $this->id, true, $content_fields['template_adv']);
        $content_fields['random_order'] = Rules::is('source', $this->id, true, $content_fields['random_order']);

        $fields['utm_options'] = Rules::is('source', $this->id, true, $fields['utm_options']);


        $content_fields['ft_message_1'] = [
            'label'    => __('Message 1', 'notificationx'),
            'name'     => 'ft_message_1',
            'type'     => 'flashing-message-icon',
            'priority' => 20,
            'default'  => '',
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id),
                // Rules::includes('themes', ['flashing_tab_theme-1']),
            ]),
            'default'  => [
                'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-1.png',
                'message' => '',
            ],
            'options'     => array(
                array(
                    'column' => 6,
                    'value'  => 'theme-1-icon-1.png',
                    'label'  => __('Verified', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-1.png',
                ),
                array(
                    'column' => 6,
                    'value'  => 'theme-2-icon-1.png',
                    'label'  => __('Flames', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-2-icon-1.png',
                ),
                array(
                    'column' => 6,
                    'value'  => 'theme-3-icon-1.png',
                    'label'  => __('Flames GIF', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-3-icon-1.png',
                ),
                array(
                    'column' => 6,
                    'value'  => 'theme-4-icon-1.png',
                    'label'  => __('Pink Face', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-1.png',
                ),
            )
        ];

        $content_fields['ft_message_2'] = [
            'label'    => __('Message 2', 'notificationx'),
            'name'     => 'ft_message_2',
            'type'     => 'flashing-message-icon',
            'priority' => 25,
            'default'  => '',
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id),
                // Rules::includes('themes', ['flashing_tab_theme-1']),
            ]),
            'default'  => [
                'icon'    => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-1.png',
                'message' => '',
            ],
            'options'     => array(
                array(
                    'column' => 6,
                    'value'  => 'theme-1-icon-1.png',
                    'label'  => __('Verified', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-1-icon-2.png',
                ),
                array(
                    'column' => 6,
                    'value'  => 'theme-2-icon-1.png',
                    'label'  => __('Flames', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-2-icon-2.png',
                ),
                array(
                    'column' => 6,
                    'value'  => 'theme-3-icon-1.png',
                    'label'  => __('Flames GIF', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-3-icon-2.png',
                ),
                array(
                    'column' => 6,
                    'value'  => 'theme-4-icon-1.png',
                    'label'  => __('Pink Face', 'notificationx'),
                    'icon'   => NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/theme-4-icon-2.png',
                ),
            )
        ];

        $fields['ft_timing'] = [
            'label'    => __("Timing", 'notificationx'),
            'name'     => "ft_timing",
            'type'     => "section",
            'priority' => 200,
            'rules'    => Rules::is( 'global_queue', true, true ),
            'fields'   => [
                'ft_delay_before' => [
                    'label'       => __("Start Blinking after", 'notificationx'),
                    'name'        => "ft_delay_before",
                    'type'        => "number",
                    'priority'    => 40,
                    'default'     => 0,
                    // 'help'        => __('Initial Delay', 'notificationx'),
                    'description' => __('seconds', 'notificationx'),

                ],
                'ft_delay_between' => [
                    'name'        => "ft_delay_between",
                    'type'        => "number",
                    'label'       => __("Delay Between", 'notificationx'),
                    'description' => __('seconds', 'notificationx'),
                    // 'help'        => __('Delay between each notification', 'notificationx'),
                    'priority'    => 50,
                    'default'     => 1,
                ],
                'ft_display_for' => [
                    'name'        => "ft_display_for",
                    'type'        => "number",
                    'label'       => __("Display For", 'notificationx'),
                    'description' => __('minutes', 'notificationx'),
                    // 'help'        => __('Display each notification for * seconds', 'notificationx'),
                    'priority'    => 60,
                    'default'     => 0,
                ],
            ]
        ];


        return $fields;
    }
    public function display_fields($fields){
        $fields['image-section']['fields']['show_default_image'] = Rules::is('source', $this->id, true, $fields['image-section']['fields']['show_default_image']);
        return $fields;
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
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">Contact Form 7 installed & configured</a> to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="%3$s">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="%4$s">Integration with Contact Form 7</a></p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 Hacks to Increase Your <a target="_blank" href="%5$s">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>', 'notificationx'),
        'https://wordpress.org/plugins/contact-form-7/',
        'https://notificationx.com/docs/contact-form-submission-alert/',
        'https://youtu.be/SP9NXMioIK8',
        'https://notificationx.com/integrations/contact-form-7/',
        'https://notificationx.com/blog/wordpress-contact-forms/'
        );
    }
}
