# Video Notification Type (`video`)

> Shows a live-style social-proof popup built from a YouTube channel's or video's stats
> (subscribers, views, likes, comments) to build credibility/engagement for a
> YouTube channel. This is a Pro-only notification type.

## At a glance

| | |
|---|---|
| **Type ID** | `video` |
| **Class** | [`includes/Types/Video.php`](../../includes/Types/Video.php) |
| **Trait** | none — `Video` has no matching file under `includes/Types/Traits/` |
| **Priority** | `60` |
| **Default source** | `youtube` |
| **Default theme** | **Verified:** `Video::$default_theme` is left unset (commented out at [`Video.php:35`](../../includes/Types/Video.php#L35)); the effective default comes from the `youtube` extension, which sets `$default_theme = 'youtube_channel-1'` (see [`YouTube.php`](../../includes/Extensions/Google/YouTube.php)) |
| **Module gate (`$module`)** | `modules_google_youtube` |
| **Compatible extensions** | `youtube` ([`includes/Extensions/Google/YouTube.php`](../../includes/Extensions/Google/YouTube.php)) — fully implemented, `is_pro = true`. `vimeo` ([`includes/Extensions/Vimeo/Vimeo.php`](../../includes/Extensions/Vimeo/Vimeo.php)) and `wistia` ([`includes/Extensions/Wistia/Wistia.php`](../../includes/Extensions/Wistia/Wistia.php)) also declare `$types = 'video'` but are stubs (`show_on_module = false`, `show_on_type = false`, only set a `$title` — no themes, fields, or data fetching) |

## What it does

`Video` (`includes/Types/Video.php`) is a minimal `Types` subclass — it just sets the
admin title, priority, the gating module (`modules_google_youtube`), and an
upgrade-to-Pro popup (`$this->popup`) shown when the module/feature isn't available.
`$this->is_pro = true`, so the type itself is Pro-gated.

All real behaviour lives in the `youtube` Extension
([`includes/Extensions/Google/YouTube.php`](../../includes/Extensions/Google/YouTube.php)),
which declares:
- **Channel themes**: `channel-1`, `channel-2` — pull channel-level stats (views,
  video count) and default to a "Subscribe Now" link button.
- **Video themes**: `video-1`…`video-4` — pull single-video stats (views, likes,
  comments) and default to a "Watch Now" link button.
- Matching responsive theme stubs under `res_themes` (`res-channel-1`, `res-channel-2`,
  `res-video-1`…`res-video-4`), all `is_pro => true`.
- `link_type` defaults to `yt_channel_link` (channel themes) or `yt_video_link` (video
  themes) per-theme via `defaults.link_type`.
- A cron schedule key `nx_youtube_interval` (`$cron_schedule`) — presumably used to
  periodically refresh channel/video stats from the YouTube Data API
  (`$api_base = 'https://youtube.googleapis.com/youtube/v3/'`). **Verified:** the actual
  API-fetch / cron-registration code lives in the sibling `notificationx-pro` plugin (its
  `includes/Admin/Cron.php` and Pro `Youtube` extension) — both the Type and Extension are
  `is_pro = true`, so the free class only carries the theme/field declarations.
- `fallback_data()` maps saved meta (`_yt_views`, `_yt_subscribers`, `_yt_videos`,
  `_yt_likes`, `_yt_comments`, `_yt_favorites`) through `Helper::nice_number()` into the
  `data` array consumed by templates.

`vimeo` and `wistia` extensions exist and register `$types = 'video'` but are inert
stubs — no themes, no fields, no data fetching, and explicitly hidden from the module
list and type picker (`show_on_module = false`, `show_on_type = false`). **Verified:** they
remain inert stubs in `notificationx-pro` too (no themes/fields/data logic in either
plugin) — effectively reserved placeholders, not user-selectable sources today.

## Data flow

`youtube` is one of the sources explicitly excluded from the `display_last` slicing in
`FrontEnd::filtered_data()` (see the `in_array($post['source'], [..., 'youtube', ...])`
check in [`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php)) — i.e. it is treated
like the other "inline/single-value" sources rather than a growing entries feed.
Beyond that exemption, `video`/`youtube` was not found to have a dedicated bucket in
`FrontEnd::get_notifications_ids()` / `get_notifications_data()` the way `exit_intent`
or `popup` do (per the pattern documented in
[`../development/adding-a-notification-type.md`](../development/adding-a-notification-type.md)); it appears to flow
through the standard `active`/`global` notification pipeline like other non-popup
types. **Verified:** on the React side it renders through the generic
[`nxdev/notificationx/frontend/themes/Theme.tsx`](../../nxdev/notificationx/frontend/themes/Theme.tsx)
renderer — there is no `video`/`youtube`-specific component (only `announcements/` has
per-theme `.tsx` files).

The Growth Alert / inline text renderer in
[`includes/Features/Inline.php`](../../includes/Features/Inline.php) has explicit
`case` branches for `youtube_channel-1`, `youtube_channel-2`, `youtube_video-1`…`4`
that stitch together `second_param` … `fifth_param` (plus the `yt_*_label` tokens) into
a single sentence — this is what a "Growth Alert" inline rendering of a video
notification looks like.

## Fields & settings schema

`Video` itself defines no fields (`init_fields()` is inherited as a no-op from
`Types`). Field/theme definitions live entirely on the `youtube` Extension:

- Per-theme `template` tokens: `second_param` … `fifth_param`, each paired with a
  `custom_<param>` fallback and (for some themes) a `yt_<n>_label` string — e.g.
  `third_param => 'tag_yt_channel_title'`, `fourth_param => 'tag_yt_views'` /
  `'tag_yt_likes'`, `fifth_param => 'tag_yt_videos'` / `'tag_yt_comments'`.
- Per-theme `defaults`: `image_shape` (`rounded`/`circle`), `link_type`
  (`yt_channel_link`/`yt_video_link`), `show_notification_image` (`yt_thumbnail`),
  `link_button_text`, and `link_button` (bool).
- `$this->templates` groups themes into two template families —
  `youtube_channel` (themes `youtube_channel-1`, `youtube_channel-2`) and
  `youtube_video` (themes `youtube_video-1..4`) — each declaring the allowed
  `third_param`/`fourth_param`/`fifth_param` tag options shown in the admin builder.
- The shared `link_button` global field (in
  [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)) is explicitly
  enabled for `type` `video` (among `conversions`, `woocommerce`, `woocommerce_sales`,
  `page_analytics`) via `Rules::includes('type', [...])`.
- Admin Quick Builder ([`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php))
  lists `video` in `types_title` (label "Video") and includes `youtube_channel_id` /
  `youtube_video_id` in its visible-field `show` list — i.e. the source-specific ID
  inputs the user supplies to identify which channel/video to pull stats for.
  **Verified:** the full field definitions for `youtube_channel_id` / `youtube_video_id`
  live in the Pro `Youtube` extension (`notificationx-pro/includes/Extensions/Google/Youtube.php`),
  not in the free plugin.

## Themes / templates

| Theme key (Extension-prefixed as `youtube_<key>`) | Kind | Notes |
|---|---|---|
| `channel-1` | Channel | `image_shape: rounded`, link type `yt_channel_link`, button "Subscribe Now" |
| `channel-2` | Channel | `image_shape: circle`, link type `yt_channel_link`, button "Subscribe Now" |
| `video-1` | Video | `image_shape: circle`, link type `yt_video_link`, button "Watch Now" |
| `video-2` | Video | `image_shape: rounded`, link type `yt_video_link`, button "Watch Now" |
| `video-3` | Video | `image_shape: circle`, link type `yt_video_link`, button "Watch Now" + link button on |
| `video-4` | Video | `image_shape: rounded`, link type `yt_video_link`, button "Watch Now" + link button on |

Responsive counterparts: `res-channel-1`, `res-channel-2`, `res-video-1`…`res-video-4`
(all `is_pro => true`, image-only definitions in `$res_themes`).

`Video::$themes` on the Type class itself is an empty array (`[]`) — themes are
sourced from the Extension, not the Type.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/Video.php`](../../includes/Types/Video.php) |
| Trait | none |
| Extensions | [`includes/Extensions/Google/YouTube.php`](../../includes/Extensions/Google/YouTube.php) (active), [`includes/Extensions/Vimeo/Vimeo.php`](../../includes/Extensions/Vimeo/Vimeo.php) (stub), [`includes/Extensions/Wistia/Wistia.php`](../../includes/Extensions/Wistia/Wistia.php) (stub) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'video' => 'NotificationX\Types\Video'`), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'youtube'`, `'vimeo'`, `'wistia'`) |
| Global fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (`link_button` rule includes `video`) |
| Inline/Growth Alert rendering | [`includes/Features/Inline.php`](../../includes/Features/Inline.php) (`youtube_channel-*` / `youtube_video-*` cases) |
| Admin Quick Builder | [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php) (`types_title['video']`, `youtube_channel_id`, `youtube_video_id`) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (`youtube` excluded from `display_last` slicing in `filtered_data()`) |
| Frontend runtime | [`nxdev/notificationx/frontend/themes/Theme.tsx`](../../nxdev/notificationx/frontend/themes/Theme.tsx) — the generic renderer; no `video`/`youtube`-specific React component |

## Dependencies

A YouTube Data API v3 key (`$api_base = 'https://youtube.googleapis.com/youtube/v3/'`),
configured per `YouTube::doc()`'s link to
`admin.php?page=nx-settings&tab=tab-api-integrations#google_youtube_settings_section`.
No WordPress plugin dependency — this integrates with an external Google API, not
another WP plugin. `vimeo` and `wistia` extensions exist but are non-functional stubs
(no API integration implemented in this repo).

## Testing notes & gotchas

- Both `Video` (Type) and `youtube` (Extension) are `is_pro = true` — this type is
  invisible/locked without the Pro plugin active; the "More Info" / "Upgrade to PRO"
  popup defined in `Video::init()` is what free-plugin users see instead.
- The module gate is `modules_google_youtube` — if that settings key is off, the type
  won't register at all (per the standard module-gating convention in
  [`CLAUDE.md`](../../CLAUDE.md)).
- `vimeo` and `wistia` are registered in `ExtensionFactory` and declare `$types =
  'video'`, but `show_on_module = false` / `show_on_type = false` hide them from the
  admin UI — don't assume they are user-selectable sources for this type today.
- The actual YouTube API fetch / cron job wiring (`cron_schedule = 'nx_youtube_interval'`)
  was not found in the free plugin — verify in `notificationx-pro` before assuming
  behaviour.
- No PHPUnit tests specific to `Video` or `youtube` exist. **Verified:** the free `tests/`
  suite covers only the factories, migration/upgrader, and REST; none exercise this type.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
