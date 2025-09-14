<?php

namespace NotificationX\Admin;

use NotificationX\GetInstance;

class InfoTooltipManager {

    use GetInstance;

    private static $tooltips = [
        'advanced_template' => [
            'type'    => 'video',
            'title'   => 'See how advanced templates works',
            'content' => 'https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4',
            'docs'    => 'https://wpdeveloper.com/docs',
            'width'   => 450,
            'height'  => 235,
        ],
        'custom_themes' => [
            'type'    => 'text',
            'title'   => 'Custom Themes',
            'content' => 'Get access to 20+ premium themes.',
            'docs'    => 'https://notificationx.com/themes',
        ],
        'analytics' => [
            'type'    => 'image',
            'title'   => 'Analytics Integration',
            'content' => 'https://yourcdn.com/images/analytics.gif',
            'docs'    => 'https://notificationx.com/docs/analytics',
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
            $html .= sprintf('<p>%s</p>', esc_html($tooltip['content']));
        }

        if (!empty($tooltip['title'])) {
            $html .= '<h3 style="margin:5px 0;">' . esc_html($tooltip['title']);
            if (!empty($tooltip['docs'])) {
                $html .= ' <a href="' . esc_url($tooltip['docs']) . '" target="_blank">Docs</a>';
            }
            $html .= '</h3>';
        }

        $html .= '</div>';
        return $html;
    }
}
