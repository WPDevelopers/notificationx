<?php
/**
 * This class will provide all kind of helper methods.
 */
class NotificationX_ToggleFields {

    public static function common_fields(){
        return array(
            'conversion_from',
            'conversion_position',
            'delay_before',
            'display_last',
            'display_from',
            'display_for',
            'delay_between',
            'loop',
            'notification_preview',
            'conversion_size'
        );
    }

    public static function common_sections(){
        return array(
            'image',
            'themes',
            'conversion_link_options'
        );
    }

    public static function woocommerce(){
        return apply_filters( 'nx_woocommerce_toggle_fields', array(
            'fields' => self::common_fields(),
            'sections' => self::common_sections(),
        ));
    }

    public static function woocommerce_hide(){
        return apply_filters( 'nx_woocommerce_toggle_fields', array(
            'fields' => [ 'comments_template' ],
            'sections' => [],
        ));
    }

    public static function edd(){
        return apply_filters( 'nx_edd_toggle_fields', array(
            'fields' => self::common_fields(),
            'sections' => self::common_sections(),
        ));
    }

    public static function edd_hide(){
        return apply_filters( 'nx_edd_toggle_fields', array(
            'fields' => [ 'comments_template' ],
            'sections' => [],
        ));
    }

}
