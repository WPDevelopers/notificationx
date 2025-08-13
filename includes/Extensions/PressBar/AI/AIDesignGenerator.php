<?php
/**
 * AI Design Generator for NotificationX
 * 
 * This class handles AI-powered notification bar design generation
 * 
 * @package NotificationX
 * @since 2.8.0
 */

namespace NotificationX\Extensions\PressBar\AI;

use NotificationX\Core\Helper;

class AIDesignGenerator {
    
    /**
     * Initialize the AI Design Generator
     */
    public function __construct() {
        add_action('wp_ajax_generate_notification_designs', [$this, 'handle_ai_generation']);
        add_action('wp_ajax_nopriv_generate_notification_designs', [$this, 'handle_ai_generation']);
        
        add_action('wp_ajax_import_ai_design', [$this, 'handle_design_import']);
        add_action('wp_ajax_nopriv_import_ai_design', [$this, 'handle_design_import']);
    }
    
    /**
     * Handle AI design generation AJAX request
     */
    public function handle_ai_generation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'notificationx_nonce')) {
            wp_die(__('Security check failed', 'notificationx'));
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $page = intval($_POST['page'] ?? 1);
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt is required', 'notificationx'));
        }
        
        try {
            $designs = $this->generate_designs($prompt, $page);
            wp_send_json_success([
                'designs' => $designs,
                'has_more' => $page < 3, // Simulate pagination
                'total' => count($designs)
            ]);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Generate notification bar designs based on prompt
     * 
     * @param string $prompt User's design requirements
     * @param int $page Page number for pagination
     * @return array Generated designs
     */
    private function generate_designs($prompt, $page = 1) {
        // In a real implementation, this would call an AI service like OpenAI
        // For now, we'll generate sample designs based on the prompt
        
        $base_designs = $this->get_base_design_templates();
        $generated_designs = [];
        
        // Analyze prompt for keywords
        $style_keywords = $this->analyze_prompt_style($prompt);
        $color_keywords = $this->analyze_prompt_colors($prompt);
        $content_keywords = $this->analyze_prompt_content($prompt);
        
        foreach ($base_designs as $index => $design) {
            $customized_design = $this->customize_design($design, [
                'style' => $style_keywords,
                'colors' => $color_keywords,
                'content' => $content_keywords,
                'prompt' => $prompt,
                'page' => $page,
                'index' => $index
            ]);
            
            $generated_designs[] = $customized_design;
        }
        
        return $generated_designs;
    }
    
    /**
     * Get base design templates
     * 
     * @return array Base design templates
     */
    private function get_base_design_templates() {
        return [
            [
                'id' => 'modern-gradient',
                'title' => 'Modern Gradient Design',
                'style' => 'modern',
                'elementor_template' => 'theme-one.json',
                'gutenberg_template' => 'theme-one-gutenberg.json',
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'text' => '#ffffff',
                    'background' => 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)'
                ]
            ],
            [
                'id' => 'bold-announcement',
                'title' => 'Bold Announcement',
                'style' => 'bold',
                'elementor_template' => 'theme-two.json',
                'gutenberg_template' => 'theme-two-gutenberg.json',
                'colors' => [
                    'primary' => '#ef4444',
                    'secondary' => '#dc2626',
                    'text' => '#ffffff',
                    'background' => '#ef4444'
                ]
            ],
            [
                'id' => 'minimal-clean',
                'title' => 'Minimal Clean Design',
                'style' => 'minimal',
                'elementor_template' => 'theme-three.json',
                'gutenberg_template' => 'theme-three-gutenberg.json',
                'colors' => [
                    'primary' => '#64748b',
                    'secondary' => '#475569',
                    'text' => '#1e293b',
                    'background' => '#f8fafc'
                ]
            ]
        ];
    }
    
    /**
     * Analyze prompt for style keywords
     * 
     * @param string $prompt User prompt
     * @return string Detected style
     */
    private function analyze_prompt_style($prompt) {
        $prompt_lower = strtolower($prompt);
        
        if (strpos($prompt_lower, 'modern') !== false || strpos($prompt_lower, 'gradient') !== false) {
            return 'modern';
        } elseif (strpos($prompt_lower, 'bold') !== false || strpos($prompt_lower, 'attention') !== false) {
            return 'bold';
        } elseif (strpos($prompt_lower, 'minimal') !== false || strpos($prompt_lower, 'clean') !== false) {
            return 'minimal';
        } elseif (strpos($prompt_lower, 'classic') !== false || strpos($prompt_lower, 'traditional') !== false) {
            return 'classic';
        }
        
        return 'modern'; // Default
    }
    
    /**
     * Analyze prompt for color keywords
     * 
     * @param string $prompt User prompt
     * @return array Detected colors
     */
    private function analyze_prompt_colors($prompt) {
        $prompt_lower = strtolower($prompt);
        $colors = [];
        
        // Color mapping
        $color_map = [
            'blue' => '#3b82f6',
            'red' => '#ef4444',
            'green' => '#10b981',
            'purple' => '#8b5cf6',
            'orange' => '#f97316',
            'yellow' => '#eab308',
            'pink' => '#ec4899',
            'black' => '#000000',
            'white' => '#ffffff',
            'gray' => '#6b7280'
        ];
        
        foreach ($color_map as $color_name => $color_value) {
            if (strpos($prompt_lower, $color_name) !== false) {
                $colors[] = $color_value;
            }
        }
        
        return $colors;
    }
    
    /**
     * Analyze prompt for content keywords
     * 
     * @param string $prompt User prompt
     * @return array Content suggestions
     */
    private function analyze_prompt_content($prompt) {
        $prompt_lower = strtolower($prompt);
        $content = [];
        
        // Content type detection
        if (strpos($prompt_lower, 'sale') !== false || strpos($prompt_lower, 'discount') !== false) {
            $content['type'] = 'sale';
            $content['text'] = 'Limited Time Sale - Save Up to 50%!';
            $content['button'] = 'Shop Now';
        } elseif (strpos($prompt_lower, 'announcement') !== false || strpos($prompt_lower, 'news') !== false) {
            $content['type'] = 'announcement';
            $content['text'] = 'Important Announcement - Check Out Our Latest Update';
            $content['button'] = 'Learn More';
        } elseif (strpos($prompt_lower, 'newsletter') !== false || strpos($prompt_lower, 'subscribe') !== false) {
            $content['type'] = 'newsletter';
            $content['text'] = 'Subscribe to Our Newsletter for Exclusive Updates';
            $content['button'] = 'Subscribe';
        } else {
            $content['type'] = 'general';
            $content['text'] = 'Don\'t Miss Out - Special Offer Available Now!';
            $content['button'] = 'Get Started';
        }
        
        return $content;
    }
    
    /**
     * Customize design based on analysis
     * 
     * @param array $base_design Base design template
     * @param array $customization Customization parameters
     * @return array Customized design
     */
    private function customize_design($base_design, $customization) {
        $design = $base_design;
        
        // Customize based on prompt analysis
        $design['id'] = 'ai-' . uniqid() . '-' . $customization['page'] . '-' . $customization['index'];
        $design['title'] = $this->generate_title($customization);
        $design['description'] = 'Generated based on: "' . substr($customization['prompt'], 0, 50) . '..."';
        
        // Apply color customizations
        if (!empty($customization['colors'])) {
            $design['colors']['primary'] = $customization['colors'][0];
            if (isset($customization['colors'][1])) {
                $design['colors']['secondary'] = $customization['colors'][1];
            }
        }
        
        // Apply style customizations
        if ($customization['style']) {
            $design['style'] = $customization['style'];
        }
        
        // Generate preview URL
        $design['preview_url'] = $this->generate_preview_url($design);
        
        // Generate template data
        $design['elementor_data'] = $this->generate_elementor_data($design, $customization);
        $design['gutenberg_data'] = $this->generate_gutenberg_data($design, $customization);
        
        return $design;
    }
    
    /**
     * Generate title based on customization
     * 
     * @param array $customization Customization parameters
     * @return string Generated title
     */
    private function generate_title($customization) {
        $style_titles = [
            'modern' => 'Modern',
            'bold' => 'Bold',
            'minimal' => 'Minimal',
            'classic' => 'Classic',
            'gradient' => 'Gradient'
        ];
        
        $content_titles = [
            'sale' => 'Sale Banner',
            'announcement' => 'Announcement Bar',
            'newsletter' => 'Newsletter Signup',
            'general' => 'Notification Bar'
        ];
        
        $style = $customization['style'] ?? 'modern';
        $content_type = $customization['content']['type'] ?? 'general';
        
        return ($style_titles[$style] ?? 'Modern') . ' ' . ($content_titles[$content_type] ?? 'Notification Bar');
    }
    
    /**
     * Generate preview URL for design
     * 
     * @param array $design Design data
     * @return string Preview URL
     */
    private function generate_preview_url($design) {
        // In a real implementation, this would generate an actual preview image
        // For now, return existing theme images
        $admin_url = NOTIFICATIONX_ADMIN_URL;
        
        switch ($design['style']) {
            case 'bold':
                return $admin_url . 'images/extensions/themes/bar-elementor/theme-two.jpg';
            case 'minimal':
                return $admin_url . 'images/extensions/themes/bar-elementor/theme-three.jpg';
            default:
                return $admin_url . 'images/extensions/themes/bar-elementor/theme-one.jpg';
        }
    }
    
    /**
     * Generate Elementor template data
     * 
     * @param array $design Design data
     * @param array $customization Customization parameters
     * @return array Elementor template data
     */
    private function generate_elementor_data($design, $customization) {
        // Load base template and customize it
        $template_file = NOTIFICATIONX_ROOT_DIR_PATH . 'includes/Extensions/PressBar/jsons/' . $design['elementor_template'];
        
        if (file_exists($template_file)) {
            $template_data = json_decode(file_get_contents($template_file), true);
            
            // Customize template with AI-generated content
            $this->apply_customizations_to_elementor($template_data, $design, $customization);
            
            return $template_data;
        }
        
        return [];
    }
    
    /**
     * Generate Gutenberg block data
     * 
     * @param array $design Design data
     * @param array $customization Customization parameters
     * @return array Gutenberg block data
     */
    private function generate_gutenberg_data($design, $customization) {
        // Generate Gutenberg block pattern
        return [
            'title' => $design['title'],
            'description' => $design['description'],
            'content' => $this->generate_gutenberg_content($design, $customization),
            'categories' => ['notificationx'],
            'keywords' => ['notification', 'bar', 'ai-generated']
        ];
    }
    
    /**
     * Apply customizations to Elementor template
     * 
     * @param array &$template_data Template data (passed by reference)
     * @param array $design Design data
     * @param array $customization Customization parameters
     */
    private function apply_customizations_to_elementor(&$template_data, $design, $customization) {
        // Apply background color
        if (isset($template_data['content'][0]['settings'])) {
            $template_data['content'][0]['settings']['background_color'] = $design['colors']['primary'];
        }
        
        // Apply text content
        if (isset($customization['content']['text'])) {
            // Find and update text elements
            $this->update_elementor_text_content($template_data, $customization['content']['text']);
        }
        
        // Apply button text
        if (isset($customization['content']['button'])) {
            $this->update_elementor_button_content($template_data, $customization['content']['button']);
        }
    }
    
    /**
     * Update text content in Elementor template
     * 
     * @param array &$template_data Template data
     * @param string $text New text content
     */
    private function update_elementor_text_content(&$template_data, $text) {
        // Recursive function to find and update text elements
        // This is a simplified version - real implementation would be more comprehensive
        if (isset($template_data['content'])) {
            foreach ($template_data['content'] as &$section) {
                if (isset($section['elements'])) {
                    foreach ($section['elements'] as &$column) {
                        if (isset($column['elements'])) {
                            foreach ($column['elements'] as &$element) {
                                if (isset($element['settings']['title'])) {
                                    $element['settings']['title'] = $text;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Update button content in Elementor template
     * 
     * @param array &$template_data Template data
     * @param string $button_text New button text
     */
    private function update_elementor_button_content(&$template_data, $button_text) {
        // Similar to text content update but for buttons
        // Implementation would search for button elements and update their text
    }
    
    /**
     * Generate Gutenberg content
     * 
     * @param array $design Design data
     * @param array $customization Customization parameters
     * @return string Gutenberg block content
     */
    private function generate_gutenberg_content($design, $customization) {
        $text = $customization['content']['text'] ?? 'Your notification message here';
        $button = $customization['content']['button'] ?? 'Learn More';
        $bg_color = $design['colors']['primary'];
        $text_color = $design['colors']['text'];
        
        return '<!-- wp:group {"style":{"color":{"background":"' . $bg_color . '","text":"' . $text_color . '"}}} -->
<div class="wp-block-group has-text-color has-background" style="background-color:' . $bg_color . ';color:' . $text_color . '">
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">' . esc_html($text) . '</p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button">
            <a class="wp-block-button__link">' . esc_html($button) . '</a>
        </div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';
    }
    
    /**
     * Handle design import AJAX request
     */
    public function handle_design_import() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'notificationx_nonce')) {
            wp_die(__('Security check failed', 'notificationx'));
        }
        
        $design_id = sanitize_text_field($_POST['design_id']);
        $import_type = sanitize_text_field($_POST['import_type']); // 'elementor' or 'gutenberg'
        
        try {
            $result = $this->import_design($design_id, $import_type);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Import design to Elementor or Gutenberg
     * 
     * @param string $design_id Design ID
     * @param string $import_type Import type ('elementor' or 'gutenberg')
     * @return array Import result
     */
    private function import_design($design_id, $import_type) {
        // Implementation would depend on how designs are stored and imported
        // This is a placeholder for the actual import functionality
        
        return [
            'message' => sprintf(__('Design %s imported successfully to %s', 'notificationx'), $design_id, $import_type),
            'design_id' => $design_id,
            'import_type' => $import_type
        ];
    }
}

// Initialize the AI Design Generator
new AIDesignGenerator();
