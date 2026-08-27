# NotificationX Integrations

This folder is the **per-integration (Extension = data source)** reference. Each file documents one Extension: the plugin/SaaS it reads from, how it detects that dependency, and which notification Type(s) it feeds.

## The Type ↔ Extension model

A **Type** is a display category (e.g. `conversions`, `form`, `reviews`) — see [../types/README.md](../types/README.md). An **Extension** is a concrete data *source* that plugs into one or more Types (e.g. WooCommerce and SureCart both feed `conversions`). A NotificationX notification is one Type + one selected Extension + a theme. To add either side, start from [../development/adding-a-notification-type.md](../development/adding-a-notification-type.md); for the plugin's overall architecture and conventions see [../../CLAUDE.md](../../CLAUDE.md).

Many free-plugin Extension classes are registration/UI **stubs** (`get_data()` returns a placeholder) whose real API-fetch logic lives in the sibling `notificationx-pro` plugin — the "What it surfaces" column notes where this applies.

## Integrations

| Integration | Module key | Feeds Types | Depends on | What it surfaces |
|---|---|---|---|---|
| [ActiveCampaign](./activecampaign.md) | `modules_activecampaign` | email_subscription | ActiveCampaign account (API URL + key); no local guard | Pro-gated email-subscription source; free class is a registration stub, real API in notificationx-pro. |
| [BitIntegrations](./bitintegrations.md) | (BitApps source) | conversions, email_subscription, reviews | Bit Integrations plugin — `class_exists('BitApps\Integrations\Config')` | Bit Integrations as a selectable source for Sales, Email Subscription, and Reviews; `get_data()` is a hardcoded stub. |
| [CCPA](./ccpa.md) | `ccpa_notification` | gdpr | none detected — likely dead/stub code | Registered but effectively inert Extension paired to `gdpr`; real cookie behavior lives in the GDPR extension. |
| [Contact Form 7](./cf7.md) | (CF7 form source) | form | Contact Form 7 — `class_exists('WPCF7_ContactForm')` | Real-time CF7 submissions (via `wpcf7_mail_sent`) as `form` notifications; no cron polling. |
| [ConvertKit](./convertkit.md) | `convertkit` | email_subscription | ConvertKit account/API key + secret (consumed only in Pro) | Email-subscription source; free class is a stub, real connect/list/subscribers in notificationx-pro. |
| [Custom Notification](./customnotification.md) | (custom source) | custom, conversions | none | Hand-authored entries (name/message/image via `custom_contents`); no third-party data source. |
| [Easy Digital Downloads](./edd.md) | `modules_edd` | conversions, inline | Easy Digital Downloads — `class_exists('Easy_Digital_Downloads')` | Recent EDD sales (`edd`/conversions) plus a Pro inline sales-count widget (`edd_inline`/inline). |
| [Elementor](./elementor.md) | `elementor_form` | form | Elementor (free) for widgets; Elementor Pro (`Form_Snapshot_Repository`) for submissions | Elementor Pro form submissions as `form` notifications, plus two standalone Elementor widgets. |
| [Envato](./envato.md) | `modules_envato` | conversions | Envato account + API token (Pro only) | Free stub registering the Envato source/UI; real Market API sales-fetch lives in notificationx-pro. |
| [Exit Intent](./exitintent.md) | `modules_exit_intent` | exit_intent | Elementor (optional, for custom popup design) | Built-in exit-intent popup (7 themes + optional Elementor mode); no `get_data()`. |
| [Flashing Tab](./flashingtab.md) | `modules_flashing` | flashing_tab | NotificationX PRO — `NotificationX::is_pro()` | Pro-gated tab title/favicon flasher with 4 static theme configs; no data source. |
| [FluentCart](./fluentcart.md) | `modules_fluentcart` | conversions, inline | FluentCart — `class_exists('\FluentCart\Framework\Foundation\App')` | Recent FluentCart orders as Sales popups plus a Pro inline sales/stock-count widget. |
| [Fluent Forms](./fluentform.md) | (Fluent Forms source) | form | Fluent Forms — `defined('FLUENTFORM_VERSION')` | New + existing Fluent Forms submissions as `form` notifications (live hook + backfill). |
| [Freemius](./freemius.md) | `modules_freemius` | conversions, reviews, download_stats | Freemius dev account (Dev ID + keys, Pro only); no free-plugin check | Three Pro stubs (conversions/reviews/stats) for Freemius seller data; real API in notificationx-pro. |
| [GDPR](./gdpr.md) | `modules_gdpr` | gdpr | none | Self-contained Cookie Notice/Consent banner; design fields + section gating, no `get_data()`. |
| [Gravity Forms](./grvf.md) | `grvf` | form | Gravity Forms — `class_exists('\GFForms')`; Pro-only extension | Free plugin ships only the module/upsell shell; capture + GFAPI backfill in notificationx-pro. |
| [GiveWP](./give.md) | `modules_give` | donation | GiveWP — `class_exists('\Give')` (+ `\Give_Payments_Query`) | Completed GiveWP donations as real-time and backfilled Donation notifications. |
| [Facebook Reviews](./facebook-reviews.md) | `modules_facebook_reviews` | reviews | NotificationX API (managed Meta app; no local dependency) | Facebook Page rating/review count via the NotificationX API, individual reviews over a signed webhook; functional in free, filters + refresh interval in notificationx-pro. |
| [Google](./google.md) | (Reviews/Analytics/YouTube sources) | reviews, page_analytics, video | Google Places / Analytics / YouTube Data API; none enforced in free plugin | Google Reviews, Analytics, and YouTube sources; free UI/source stubs, real fetch in notificationx-pro. |
| [IFTTT](./ifttt.md) | (IFTTT source) | email_subscription | none (webhook auth via `api_key=md5(home_url())`) | Email-subscription source; free class is a `get_data()` stub, webhook ingestion in notificationx-pro. |
| [LearnDash](./learndash.md) | `modules_learndash` | elearning, inline | LearnDash — `class_exists('\LDLMS_Post_Types')` | Course-enrollment popups (elearning) + Pro inline enrollment-count widget; fetch logic in Pro. |
| [LearnPress](./learnpress.md) | (LearnPress source) | elearning, inline | LearnPress — `function_exists('LP')` | Course-enrollment popups (via `learn-press/added-order-item-data`) + Pro inline count widget. |
| [MailChimp](./mailchimp.md) | `mailchimp` | email_subscription | MailChimp account + API key (validated only in Pro) | Email-subscription source; free class is a stub, list/subscriber/cron logic in notificationx-pro. |
| [Ninja Forms](./njf.md) | `modules_njf` | form | Ninja Forms — `class_exists('Ninja_Forms')` | Ninja Forms submissions as `form` notifications (live hook + save-time backfill). |
| [Offer Announcement](./offerannouncement.md) | `modules_announcements` | offer_announcement | none | Pro-gated, manually-authored Discount Alert extension; no third-party plugin. |
| [Popup Notification](./popupnotification.md) | `modules_popup` | popup | none | Builder-authored Announcement popup (7 themes); shares the popup-submit pipeline with Exit Intent. |
| [PressBar](./pressbar.md) | `modules_bar` | notification_bar | none required; optional Elementor + Essential Blocks | Config-only Notification Bar extension with optional Elementor and Gutenberg design paths. |
| [ReviewX](./reviewx.md) | `modules_reviewx` | reviews | ReviewX plugin (requires WooCommerce) — `function_exists('rvx_wpdrill_init')` | Approved ReviewX WooCommerce product-review comments into the Reviews Type (extends WooReviews). |
| [SureCart](./surecart.md) | `surecart` | conversions | SureCart — `class_exists('\SureCart')` | SureCart checkout/order data (webhooks + backfill) driving Sales notifications. |
| [Tutor LMS](./tutor.md) | `modules_tutor` | elearning, inline | Tutor LMS — `function_exists('tutor_lms')`; optionally WooCommerce/EDD | Tutor LMS course enrollments (free/Woo/EDD monetized) feeding elearning + inline Types. |
| [Vimeo](./vimeo.md) | `vimeo` | video | Vimeo (nominal only; no detection implemented) | Registers "Vimeo" as a source/icon for the `video` Type dropdown; no module, themes, or fetch. |
| [WPForms](./wpf.md) | `modules_wpf` | form | WPForms — `class_exists('\WPForms_Form_Handler')`; backfill needs WPForms Pro | WPForms submissions (live via `wpforms_process_complete` + Pro-only historical backfill) as `form`. |
| [Wistia](./wistia.md) | (Wistia source) | video | none (no detection, no API calls) | Registers a "Wistia" icon/label in the video-source picker; no themes, fields, or `get_data()`. |
| [WooCommerce](./woocommerce.md) | (WooCommerce sources) | conversions, inline, reviews, woocommerce_sales | WooCommerce — `class_exists('\WooCommerce')` | WooCommerce orders + product-review comments driving Sales, Reviews, inline, and Growth Alert. |
| [WordPress](./wordpress.md) | `modules_wordpress` | comments, reviews, download_stats | none (uses WP core comments + wordpress.org API) | WP blog comments, wordpress.org plugin/theme reviews, and download stats as notifications. |
| [Zapier](./zapier.md) | `modules_zapier` | conversions, reviews, email_subscription | none (external Zapier SaaS over REST webhook; logic needs Pro) | Three free Zapier stubs; real webhook ingestion in the notificationx-pro subclasses. |

## Authoring

Use [_TEMPLATE.md](./_TEMPLATE.md) as the authoring template when adding a new integration doc.
