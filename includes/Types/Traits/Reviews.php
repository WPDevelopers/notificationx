<?php
/**
 * Reviews Types
 *
 * @package NotificationX\Types
 */

namespace NotificationX\Types\Traits;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;

trait Reviews {

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $_options = GlobalFields::get_instance()->normalize_fields([
            'review_page' => __('Product Page', 'notificationx'),
        ], 'type', $this->id);

        return array_merge($options, $_options);
    }

     /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function review_templates($template) {
        $template["review_fourth_param"] = [
            // 'label'     => __("Review Fourth Parameter", 'notificationx'),
            'name'      => "review_fourth_param",                            // changed name from "conversion_size"
            'type'      => "text",
            'priority'  => 27,
            'default'   => __('About', 'notificationx'),
            'rules' => Rules::includes('themes', 'reviews_review_saying'),
        ];
        return $template;
    }


    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_trim_length_dependency($dependency) {
        $dependency[] = 'reviews_review-comment';
        $dependency[] = 'reviews_review-comment-2';
        $dependency[] = 'reviews_review-comment-3';
        $dependency[] = 'woo_reviews_review-comment';
        $dependency[] = 'woo_reviews_review-comment-2';
        $dependency[] = 'woo_reviews_review-comment-3';
        $dependency[] = 'woocommerce_sales_reviews_review-comment';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-2';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-3';
        $dependency[] = 'reviewx_review-comment';
        $dependency[] = 'reviewx_review-comment-2';
        $dependency[] = 'reviewx_review-comment-3';
        return $dependency;
    }

    // @todo frontend
    public function conversion_data($saved_data, $settings) {
        if(empty($saved_data['content']) && !empty($saved_data['plugin_review'])){
            $saved_data['content'] = $saved_data['plugin_review'];
        }
        if (!empty($saved_data['content'])) {
            $trim_length = 100;
            if ($settings['themes'] == 'reviews_review-comment-3' || $settings['themes'] == 'reviews_review-comment-3') {
                $trim_length = 80;
            }
            $nx_trimmed_length = apply_filters('nx_text_trim_length', $trim_length, $settings);
            $review_content = $saved_data['content'];
            if (strlen($review_content) > $nx_trimmed_length) {
                $review_content = substr($review_content, 0, $nx_trimmed_length) . '...';
            }
            if ($settings['themes'] == 'reviews_review-comment-2') { // || $settings['theme'] == 'comments_theme-six-free'
                $review_content = '" ' . $review_content . ' "';
            }
            $saved_data['plugin_review'] = $review_content;
        }
        if(empty($saved_data['title']))
            $saved_data['title'] = isset($saved_data['post_title']) ? $saved_data['post_title'] : '';

        return $saved_data;
    }
    
    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "title"             => _x("NotificationX", 'nx_preview', 'notificationx'),
        ]);
        return $entry;
    }

}