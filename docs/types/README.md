# NotificationX Notification Types

This folder is the per-Type reference for NotificationX's notification **Types** — one Markdown doc per Type, tracing its class, themes/templates, data flow, and compatible data-source extensions for developer/AI onboarding.

**Type ↔ Extension model.** A **Type** (e.g. `conversions`, `comments`, `gdpr`) defines a notification category: its admin metadata, theme catalogue, fields, and any dedicated `FrontEnd.php` routing. Each Type is fed by one or more **Extensions** — the data-source adapters (e.g. WooCommerce, EDD, MailChimp, GiveWP) that implement `get_data()` to supply the actual entries. One Type can have many Extensions; an Extension declares which Type it belongs to. See [../development/adding-a-notification-type.md](../development/adding-a-notification-type.md) for the authoring walkthrough and the plugin-wide [../../CLAUDE.md](../../CLAUDE.md) for architecture context.

## Types

| Type | Type ID (code) | Dependency | What it shows |
| --- | --- | --- | --- |
| [Sales Notification (Conversions)](./conversions.md) | `conversions` | WooCommerce / EDD / SureCart / FluentCart / Envato / Freemius / Zapier / BitIntegrations (any one active data-source extension) | Recent sales/conversions social-proof popups sourced from ~9 compatible data-source extensions. |
| [WooCommerce Sales](./woocommerce_sales.md) | `woocommerce_sales` | none | WooCommerce-specific recent-sales popups with product include/exclude filtering across three WooCommerce data sources. |
| [Comments](./comments.md) | `comments` | none — core WordPress only | A WordPress comment rendered as a themed social-proof popup via the `wp_comments` source. |
| [Reviews](./reviews.md) | `reviews` | none — core type; individual sources need WooCommerce, ReviewX, Freemius, Zapier, Google Reviews, or BitIntegrations | Recent product/plugin/service reviews from 7 compatible review-source extensions. |
| [Download Stats](./download_stats.md) | `download_stats` | none | WordPress.org (or Freemius) download / active-install counts for a plugin or theme. |
| [eLearning](./elearning.md) | `elearning` | none (runtime: Tutor LMS / LearnDash / LearnPress, optionally WooCommerce or EDD) | Course-enrollment social-proof notifications from LMS platforms. |
| [Donations](./donation.md) | `donation` | GiveWP | A GiveWP-only feed of recent donations. |
| [Notification Bar](./notification_bar.md) | `notification_bar` | none | A site-wide announcement/notification bar (PressBar extension). |
| [Announcement / Popup](./popup.md) | `popup` | none | Announcement popups — promo, video, repeater, and Pro lead-capture forms — with exit-intent support. |
| [Contact Form](./form.md) | `form` | none | Contact-form submissions (CF7 / WPForms / Ninja / Gravity / Fluent / Elementor) as social-proof popups. |
| [Email Subscription](./email_subscription.md) | `email_subscription` | none | A Pro-gated "someone just subscribed" popup (MailChimp / ConvertKit / ActiveCampaign / Zapier / BitIntegrations / IFTTT). |
| [Exit Intent Popup](./exit_intent.md) | `exit_intent` | none (Elementor optional, for custom-design mode) | A built-in or Elementor-custom popup shown when a visitor is about to leave. |
| [Page Analytics](./page_analytics.md) | `page_analytics` | none (Google Analytics data source is Pro-only / stubbed in free) | A Google Analytics-driven "N people viewed this page" toast. |
| [Custom Notification](./custom.md) | `custom` | none | A Pro-only shell for hand-typed entries, borrowing themes from other types. |
| [Growth Alert / Inline](./inline.md) | `inline` | none | A Pro-only notification injected inline into page content (e.g. before Add to Cart). |
| [Flashing Tab](./flashing_tab.md) | `flashing_tab` | none | A Pro-only browser-tab title/favicon flashing alert (4 themes). |
| [Video](./video.md) | `video` | YouTube Data API v3 (external); notificationx-pro for fetch/cron | A Pro-only YouTube channel/video social-proof popup. |
| [Discount Alert](./offer_announcement.md) | `offer_announcement` | none | A Pro-only tag-based discount/offer announcement. |
| [Cookie Notice](./gdpr.md) | `gdpr` | none | A GDPR/CCPA cookie-consent banner (14 themes) with a Preference Center and cookie scanner. |

---

_[`_TEMPLATE.md`](./_TEMPLATE.md) is the authoring template for new per-Type docs._
