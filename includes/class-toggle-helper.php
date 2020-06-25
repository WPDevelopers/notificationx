<?php
/**
 * This class will provide all kind of helper methods.
 */
class NotificationX_ToggleFields {

    public static function common_fields(){
        $common_fields = apply_filters( 'nx_meta_common_fields', array(
            'conversion_position',
            'delay_before',
            'display_last',
            'display_from',
            'display_for',
            'delay_between',
            'loop',
            'notification_preview',
            'conversion_size'
        ) );
        return $common_fields;
    }

    public static function common_sections(){
        $common_sections = apply_filters( 'nx_meta_common_sections', array(
            'image',
            'sound_checkbox_wrap'
        ) );
        return $common_sections;
    }

    public static function woocommerce(){
        $fields = self::common_fields();
        $fields[] = 'conversion_from';
        $sections = self::common_sections();
        $sections[] = 'themes';
        $sections[] = 'conversion_link_options';

        return apply_filters( 'nx_woocommerce_toggle_fields', array(
            'fields' => $fields,
            'sections' => $sections,
        ));
    }

    public static function woocommerce_hide(){
        return apply_filters( 'nx_woocommerce_toggle_fields', array(
            'fields' => [ 'comments_template' ],
            'sections' => [],
        ));
    }

    public static function edd(){
        $fields = self::common_fields();
        $fields[] = 'woo_template_new';
        $fields[] = 'woo_template_adv';
        $fields[] = 'combine_multiorder';
        $fields[] = 'conversion_from';

        $sections = self::common_sections();
        $sections[] = 'themes';
        $sections[] = 'conversion_link_options';

        return apply_filters( 'nx_edd_toggle_fields', array(
            'fields' => $fields,
            'sections' => $sections,
        ));
    }

    public static function edd_hide(){
        return apply_filters( 'nx_edd_toggle_fields', array(
            'fields' => [ 'comments_template' ],
            'sections' => [],
        ));
    }

    public static function reviews(){
        $fields = self::common_fields();
        $sections = self::common_sections();
        $sections[] = 'rs_link_options';
        $fields = array_merge( $fields, array( 'reviews_source' ));

        return apply_filters( 'nx_reviews_toggle_fields', array(
            'fields' => $fields,
            'sections' => $sections,
        ));
    }

    public static function stats(){
        $fields = self::common_fields();
        $sections = self::common_sections();
        $sections[] = 'rs_link_options';

        return apply_filters( 'nx_stats_toggle_fields', array(
            'fields' => array_merge( $fields, array( 'stats_source' ) ),
            'sections' => $sections,
        ));
    }

    public static function comments(){
        $fields = self::common_fields();
        $sections = self::common_sections();

        return apply_filters( 'nx_comments_toggle_data', array(
            'fields' => array_merge( $fields, array( 'comments_source', 'comments_template_new', 'comments_template_adv', 'show_avatar' ) ),
            'sections' => array_merge( $sections, array( 'link_options', 'comment_themes' ) ),
        ));
    }

    public static function conversions(){
        $fields = self::common_fields();
        $sections = self::common_sections();
        
        return apply_filters( 'nx_conversions_toggle_data', array(
            'fields' => array_merge( $fields, array( 'conversion_from' ) ),
            'sections' => array_merge( $sections, array( 'themes', 'conversion_link_options' ) ),
        ));
    }

    public static function elearning(){
        $fields = self::common_fields();
        $sections = self::common_sections();
        
        return apply_filters( 'nx_elearning_toggle_fields', array(
            'fields' => array_merge( $fields, array( 'elearning_source', 'show_notification_image' ) ),
            'sections' => array_merge( $sections, array( 'elearning_themes', 'elearning_link_options' ) ),
        ));
    }

    public static function tutor(){
        $fields = self::common_fields();
        $fields[] = 'elearning_source';
        $fields[] = 'show_notification_image';
        $sections = self::common_sections();
        $sections[] = 'elearning_themes';
        $sections[] = 'elearning_link_options';

        return apply_filters( 'nx_tutor_toggle_fields', array(
            'fields' => $fields,
            'sections' => $sections,
        ));
    }

    public static function donation(){
        $fields = self::common_fields();
        $sections = self::common_sections();
        $sections[] = 'donation_link_options';
        $sections[] = 'donation_themes';

        return apply_filters( 'nx_donation_toggle_fields', array(
            'fields' => array_merge( $fields, array( 'donation_source', 'show_notification_image' ) ),
            'sections' => $sections,
        ));
    }
    public static function give(){
        $fields = self::common_fields();
        $fields[] = 'donation_source';
        $fields[] = 'show_notification_image';
        $sections = self::common_sections();
        $sections[] = 'donation_themes';
        $sections[] = 'donation_link_options';

        return apply_filters( 'nx_give_toggle_fields', array(
            'fields' => $fields,
            'sections' => $sections,
        ));
    }

    public static function form(){
        $fields = self::common_fields();
        $sections = self::common_sections();
        $sections[] = 'form_themes';

        return apply_filters( 'nx_form_toggle_fields', array(
            'fields' => array_merge( $fields, array( 'form_source', 'show_notification_image' ) ),
            'sections' => $sections,
        ));
    }
}
