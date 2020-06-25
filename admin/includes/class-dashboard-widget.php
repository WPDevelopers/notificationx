<?php 
/**
 * Class for Dashboard Widget for Analytics.
 * @since 1.7.0
 * @package notificationx
 */
class NotificationX_Dashboard_Widget {
    /**
     * Widget ID
     * @constant string
     */
    protected static $WIDGET_ID = 'nx_analytics_dashboard_widget';
    /**
     * Widget Title
     * @var string
     */
    protected $widget_name = null;
    /**
     * Constructor
     * Invoked automatically when object created
     */
    public function __construct(){
        if( NotificationX_DB::get_settings( 'enable_analytics' ) != 1 && NotificationX_DB::get_settings( 'enable_analytics' ) !== '' ) {
            return;
        }
        if( NotificationX_DB::get_settings( 'disable_dashboard_widget' ) === '1' && NotificationX_DB::get_settings( 'disable_dashboard_widget' ) !== '' ) {
            return;
        }
        $this->widget_name = __( 'NotificationX Analytics', 'notificationx' );
        add_action( 'wp_dashboard_setup', array( $this, 'widget_action' ) );
    }
    /**
     * Admin Action callback 
     * for wp_dashboard_setup
     * @return void
     */
    public function widget_action(){
        wp_add_dashboard_widget( self::$WIDGET_ID, $this->widget_name, array( $this, 'widget_output' ) );
    }
    /**
     * Get all analytics data
     * @return array
     */
    public function analytics_counter(){
        global $pagenow, $current_user, $wpdb;
        $ids = false;

        $inner_sql = "SELECT DISTINCT INNER_POSTS.ID, INNER_POSTS.post_title FROM $wpdb->posts AS INNER_POSTS INNER JOIN $wpdb->postmeta AS INNER_META ON INNER_POSTS.ID = INNER_META.post_id WHERE INNER_POSTS.post_type = '%s'";

        $query = $wpdb->prepare(
            "SELECT META.meta_key as `key`, SUM( META.meta_value ) as `value` FROM ( $inner_sql ) as POSTS INNER JOIN $wpdb->postmeta as META ON POSTS.ID = META.post_id WHERE META.meta_key IN ( '_nx_meta_views', '_nx_meta_clicks' ) GROUP BY META.meta_key", 
            array(
                'notificationx',
            )
        );
        $results = $wpdb->get_results( $query );

        $views = $clicks = $ctr = 0;
        if( ! empty( $results ) ) { 
            foreach( $results as $result ) {
                if( isset( $result->key ) && $result->key === '_nx_meta_views' ) {
                    $views = $result->value;
                }
                if( isset( $result->key ) && $result->key === '_nx_meta_clicks' ) {
                    $clicks = $result->value;
                }
            }
        }

        $ctr = $views > 0 ? number_format( ( intval( $clicks ) / intval( $views ) ) * 100, 2) : 0;

        $views = NotificationX_Helper::nice_number( $views );
        $clicks = NotificationX_Helper::nice_number( $clicks );

        $views_link = admin_url( 'admin.php?page=nx-analytics&comparison_factor=views' );
        $clicks_link = admin_url( 'admin.php?page=nx-analytics&comparison_factor=clicks' );
        $ctr_link = admin_url( 'admin.php?page=nx-analytics&comparison_factor=ctr' );

        return array(
            'views_link'  => $views_link,
            'clicks_link' => $clicks_link,
            'ctr_link'    => $ctr_link,
            'views'       => $views,
            'clicks'      => $clicks,
            'ctr'         => $ctr,
        );
    }
    /**
     * Widget Output
     * @return void
     */
    public function widget_output(){
        extract( $this->analytics_counter() );
        $class = 'nx-analytics-widget';
        if( file_exists( NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-counter.php' ) ) {
            return include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-counter.php';
        }
    }
}
/**
 * Initiate the class
 */
new NotificationX_Dashboard_Widget;