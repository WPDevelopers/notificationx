<?php
/**
 * Ability: read NotificationX analytics (views / clicks / CTR).
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns impression/click analytics for one notification or the whole site.
 */
class GetAnalytics extends AbilityBase {

    protected $id          = 'notificationx/get-analytics';
    protected $label       = 'Get analytics';
    protected $description = 'Get NotificationX analytics: total views (impressions), clicks and click-through rate. Pass an nx_id for one notification, or omit it for a site-wide total plus a per-notification breakdown.';

    public function input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id' => array(
                    'type'        => 'integer',
                    'description' => 'Optional notification id. Omit for site-wide analytics.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'totals' => array( 'type' => 'object' ),
                'items'  => array( 'type' => 'array' ),
            ),
        );
    }

    public function execute( $input ) {
        $wheres = array();
        if ( ! empty( $input['nx_id'] ) ) {
            $wheres['a.nx_id'] = (int) $input['nx_id'];
        }

        $posts = PostType::get_instance()->get_post_with_analytics( $wheres );
        if ( ! is_array( $posts ) ) {
            $posts = array();
        }

        if ( ! empty( $input['nx_id'] ) && empty( $posts ) ) {
            return new \WP_Error(
                'nx_mcp_not_found',
                /* translators: %d: notification id. */
                sprintf( __( 'No notification found with id %d.', 'notificationx' ), (int) $input['nx_id'] ),
                array( 'status' => 404 )
            );
        }

        $items        = array();
        $total_views  = 0;
        $total_clicks = 0;

        foreach ( $posts as $post ) {
            $views  = isset( $post['views'] ) ? (int) $post['views'] : 0;
            $clicks = isset( $post['clicks'] ) ? (int) $post['clicks'] : 0;

            $total_views  += $views;
            $total_clicks += $clicks;

            $items[] = array(
                'nx_id'  => isset( $post['nx_id'] ) ? (int) $post['nx_id'] : 0,
                'title'  => isset( $post['title'] ) ? $post['title'] : '',
                'source' => isset( $post['source'] ) ? $post['source'] : '',
                'views'  => $views,
                'clicks' => $clicks,
                'ctr'    => $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0,
            );
        }

        return array(
            'totals' => array(
                'views'  => $total_views,
                'clicks' => $total_clicks,
                'ctr'    => $total_views > 0 ? round( ( $total_clicks / $total_views ) * 100, 2 ) : 0,
            ),
            'items'  => $items,
        );
    }
}
