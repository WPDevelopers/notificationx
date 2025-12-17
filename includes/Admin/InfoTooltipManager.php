<?php

namespace NotificationX\Admin;

use NotificationX\GetInstance;

class InfoTooltipManager {

    use GetInstance;

    private static $tooltips = [
        'advanced_template' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/notificationx-advanced-template/" target="_blank">Advanced Template</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/advanceTemplate.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'animation' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/notificationx-advanced-template/" target="_blank">Animation</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/notificationAnimation.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'button' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/configure-notification-button/" target="_blank">Button</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/notificationButton.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'custom_css' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/custom-css-in-notificationx/" target="_blank">Custom CSS</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/customCSS.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'exclude_by' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/how-exclude-by-works-in-notificationx/" target="_blank">Exclude By</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/excludeBy.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'global_queue_management' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/centralized-queue/" target="_blank">Global Queue Management</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/globalQueueManagement.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'order_status' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/how-order-status-works/" target="_blank">Order Status</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/orderStatus.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'random_order' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/random-order-notificationx/" target="_blank">Random Order</a> functions',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/randomOrder.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'reporting_frequency' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/how-reporting-frequency-works/" target="_blank">Reporting Frequency</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/reportingFrequency.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'show_purchase_of' => [
            'type'    => 'image',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/how-show-purchase-of-works/" target="_blank">Show Purchase Of</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2025/09/showPurchaseOf.gif',
            'width'   => '450',
            'height'  => '235',
        ],
        'schedule_type' => [
            'type'    => 'content',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/notificationx-advanced-template/" target="_blank">Schedule Type</a> works',
            'content' => '',
            'width'   => '450',
            'height'  => '235',
        ],
        'targeting' => [
            'type'    => 'content',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/how-audience-targeting-works/" target="_blank">Targeting</a> works',
            'content' => '',
            'width'   => '450',
            'height'  => '235',
        ],
        'popup_notification_message_field' => [
            'type'    => 'content',
            'title'   => 'Enable this field to let users send messages or feedback.','notificationx',
            'content' => '',
            'width'   => '450',
            'height'  => '235',
        ],
        'popup_notification_email_field' => [
            'type'    => 'content',
            'title'   => 'Enable this field to collect user\'s email addresses.','notificationx',
            'content' => '',
            'width'   => '450',
            'height'  => '235',
        ],
    ];
    

    /**
     * Get full tooltip HTML for a given feature
     *
     * @param string $id Pro feature ID
     * @return string Full HTML or empty string if not found
     */
    public static function render($id) {
        if (!isset(self::$tooltips[$id])) {
            return '';
        }
        $tooltip = self::$tooltips[$id];
        $html = '<div class="nx-pro-feature-tooltip" style="padding:10px; border:1px solid #ccc; max-width:450px; background:#fff;">';

        if ($tooltip['type'] === 'video') {
            $html .= sprintf(
                '<video width="%d" height="%d" autoplay loop muted playsinline>
                    <source src="%s" type="video/mp4">
                  </video>',
                $tooltip['width'],
                $tooltip['height'],
                esc_url($tooltip['content'])
            );
        } elseif ($tooltip['type'] === 'image') {
            $html .= sprintf(
                '<img src="%s" alt="%s" style="max-width:100%%;">',
                esc_url($tooltip['content']),
                esc_attr($tooltip['title'])
            );
        } else {
            $html .= sprintf('<p>%s</p>', wp_kses_post($tooltip['content']));
        }

        if (!empty($tooltip['title'])) {
            $html .= '<h3 style="margin:5px 0;">' . wp_kses_post($tooltip['title']);
            $html .= '</h3>';
        }

        $html .= '</div>';
        return $html;
    }
}
