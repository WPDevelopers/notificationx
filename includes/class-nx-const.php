<?php 
class NX_CONSTANTS {
    public static $COMMENT_SECTIONS = array(
        'comment_themes', 
        'comment_design',
        'comment_image_design',
        'comment_typography'
    );
    public static $COMMENT_FIELDS = array(
        'comments_source', 
        'comment_advance_edit', 
        'comments_template_new',
        'comments_template_new_adv',
        'comments_template',
        'link_options',
        'show_avatar',
    );

    public static $CONVERSION_SECTIONS = array(
        'themes', 
        'design',
        'image_design',
        'typography'
    );
    public static $CONVERSION_FIELDS = array(
        'conversion_from', 
        'advance_edit', 
        'woo_template_new',
        'woo_template_new_adv',
        'woo_template',
        'product_control',
        'product_list',
        'category_list',
        'product_exclude_by',
        'exclude_categories',
        'exclude_products',
        'conversion_link_options',
    );

    public static function is_pro() {
        return class_exists('NotificationXPro');
    }

    public static function themeSource( $name, $type = 'press_bar' ) {
        switch( $type ) {
            case 'comments' : 
                $source = NotificationX_Helper::comment_colored_themes();
                break;
            case 'conversions' : 
                $source = NotificationX_Helper::colored_themes();
                break;
            case 'reviews' : 
                $source = NotificationX_Helper::designs_for_review();
                break;
            case 'download_stats' : 
                $source = NotificationX_Helper::designs_for_stats();
                break;
            case 'press_bar' : 
                $source = NotificationX_Helper::bar_colored_themes();
                break;
            case 'elearning' : 
                $source = NotificationX_Helper::elearning_themes();
                break;
            case 'donation' : 
                $source = NotificationX_Helper::donation_themes();
                break;
            case 'form' : 
                $source = NotificationX_Helper::form_themes();
                break;
            default : 
                $source = apply_filters( 'nx_theme_source', array(), $type );
                break;
        }

        if( ! empty( $source ) && isset( $source[ $name ] ) ) {
            return $source[ $name ];
        }
    }
}