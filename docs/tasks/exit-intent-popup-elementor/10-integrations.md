# Task 10 — WP Rocket + edit-link integrations

## Why

Two small but production-critical integrations PressBar wires up:

1. **`get_edit_post_link` filter** for the post type so the Posts admin (and any WP UI calling `get_edit_post_link`) jumps into Elementor for `nx_exit_intent` posts. Already partly done in Task 01 — extend it to cover all WP admin paths.
2. **WP Rocket RUCSS safelist** — Rocket's Remove Unused CSS strips Elementor's per-document CSS files because they're loaded by a non-standard handle. PressBar lifts those out of the strip list at [`PressBar.php:2214`](../../../includes/Extensions/PressBar/PressBar.php#L2214). Without the same patch for Exit Intent, Elementor-built popups will look unstyled when RUCSS is enabled.

## What

1. Verify Task 01's `get_edit_post_link` filter covers `nx_exit_intent`. Add `nx_get_post`'s `elementor_edit_link` recomputation if it was deferred.
2. Add a `rocket_rucss_safelist` filter handler:
   ```php
   public function rocket_rucss_safelist($list) {
       try {
           $posts = PostType::get_instance()->get_posts(['source' => $this->id]);
           foreach ($posts as $post) {
               if (class_exists('Elementor\\Core\\Files\\CSS\\Post') && !empty($post['elementor_id'])) {
                   $css = \Elementor\Core\Files\CSS\Post::create($post['elementor_id']);
                   if (!empty($css)) {
                       $list[] = $css->get_url();
                   }
               }
           }
       } catch (\Exception $e) { /* swallow */ }
       return $list;
   }
   ```
   Hook via `add_filter('rocket_rucss_safelist', …)` in `public_actions()`.

## Acceptance

- With WP Rocket + RUCSS enabled, the Elementor-built popup retains its styling after RUCSS regenerates.
- Clicking the WP "Edit" link on an `nx_exit_intent` post opens Elementor (not Gutenberg).

## Files touched

- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php).

## Depends on

Tasks 01, 03, 09.
