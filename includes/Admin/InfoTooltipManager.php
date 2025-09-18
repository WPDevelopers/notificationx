<?php

namespace NotificationX\Admin;

use NotificationX\GetInstance;

class InfoTooltipManager {

    use GetInstance;

    private static $tooltips = [
        'advanced_template' => [
            'type'    => 'video',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/notificationx-advanced-template/" target="_blank">Advanced Template</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4',
            'width'   => '450',
            'height'  => '235',
        ],
        'animation' => [
            'type'    => 'video',
            'title'   => 'Learn how Animation works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'button' => [
            'type'    => 'video',
            'title'   => 'Learn how Button works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'custom_css' => [
            'type'    => 'video',
            'title'   => 'Learn how Custom CSS works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'exclude_by' => [
            'type'    => 'video',
            'title'   => 'Learn how Exclude By works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'global_queue_management' => [
            'type'    => 'video',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/centralized-queue/" target="_blank">Global Queue Management</a> works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'order_status' => [
            'type'    => 'video',
            'title'   => 'Learn how Order Status works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'random_order' => [
            'type'    => 'video',
            'title'   => 'Learn how <a href="https://notificationx.com/docs/random-order-notificationx/" target="_blank">Random Order</a> functions',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'reporting_frequency' => [
            'type'    => 'video',
            'title'   => 'Learn how Reporting Frequency works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
            'width'   => '450',
            'height'  => '235',
        ],
        'show_purchase_of' => [
            'type'    => 'video',
            'title'   => 'Learn how Show Purchase Of works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4.',
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
