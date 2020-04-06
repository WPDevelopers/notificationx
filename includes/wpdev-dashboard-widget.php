<?php
// Create the function to output the contents of our Dashboard Widget
#http://codex.wordpress.org/Dashboard_Widgets_API
#http://codex.wordpress.org/Function_Reference/fetch_feed
include_once(ABSPATH . WPINC . '/feed.php');
#prevent the collision of same function from another plugin by WPDeveloper
if( ! function_exists( 'wpdev_dashboard_widget_function' ) ) {
	function wpdev_dashboard_widget_function() {
		// Display whatever it is you want to show
		$tcm_maxitems = 0;
		// Get a SimplePie feed object from the specified feed source.
		$rss = fetch_feed('https://wpdeveloper.net/feed');
		if ( ! is_wp_error( $rss ) ) : // Checks that the object is created correctly 
			// Figure out how many total items there are, but limit it to 5. 
			$tcm_maxitems = $rss->get_item_quantity( 5 ); 
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items( 0, $tcm_maxitems ); 
		endif;
		?>
		<ul>
			<?php if ( $tcm_maxitems == 0) echo '<li>'. __( 'No items.', 'notificationx' ) .'</li>';
		else
			// Loop through each feed item and display each item as a hyperlink.
			foreach ( $rss_items as $item ) : ?>
				<li>
					<a href='<?php echo esc_url( $item->get_permalink() ); ?>' title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'><?php echo esc_html( $item->get_title() ); ?></a>
				</li>	
			<?php endforeach; ?>
		</ul>
	<?php	
		echo '<h4>'. __( 'Keep Your Eyes at', 'notificationx' ) . ' <a href="https://wpdeveloper.net" target="_blank">WPDeveloper</a></h4>';	
	} # END 
}#end if(!function_exists('wpdev_dashboar...
// Create the function use in the action hook
if( ! function_exists( 'wpdev_add_dashboard_widgets' ) ) { #prevent the collision of same function from another plugin by WPDeveloper
	function wpdev_add_dashboard_widgets() {
		wp_add_dashboard_widget('wpdev_dashboard_widget', __( 'WPDeveloper Feed', 'notificationx' ), 'wpdev_dashboard_widget_function');	
	} 
}
// Hook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'wpdev_add_dashboard_widgets');