# Third-Party Integration Shims

Compatibility code for third-party plugins that aren't *data sources* but require special handling. Lives in [includes/ThirdParty/](../../includes/ThirdParty/) and a few scattered config files.

For **data-source integrations** (WooCommerce, FluentForm, Mailchimp, etc.) see [../recipes/add-extension.md](../recipes/add-extension.md) — those are *Extensions*, not shims.

## WPML

**Purpose:** make NotificationX's user-authored strings translatable via WPML.

**Files:**
- [wpml-config.xml](../../wpml-config.xml) — declares which option fields and post meta keys WPML should pick up for translation.
- [includes/ThirdParty/WPML.php](../../includes/ThirdParty/WPML.php) — runtime shim that registers translatable strings and resolves the current language during display.

**Integration points:**
- Each Extension can declare a `$wpml_included` array listing field names that should be WPML-translatable (e.g. `WooCommerceSales::$wpml_included = ['sales_count', 'donation_count']`).
- The base [Extension::wpml_actions()](../../includes/Extensions/Extension.php) hook fires automatically when WPML is loaded.

**Adding WPML support to a new Extension:** set `$wpml_included` to the field names you want translated. The shim handles the rest.

**Gotchas:**
- WPML support is *partial* — popup-level fields are well-covered, but dynamic tag values (the actual buyer names, etc.) are not translated. They're displayed as-is.
- If you add new translatable fields to `wpml-config.xml`, the user must rescan WPML's strings UI to pick them up.

## VisualPortfolio

**Purpose:** compatibility fix — VisualPortfolio's lightbox can interfere with NotificationX popup rendering on the same page.

**Files:**
- [includes/ThirdParty/](../../includes/ThirdParty/) — look for `VisualPortfolio.php`.

**What it does:** detects VisualPortfolio's lightbox open/close events and adjusts NotificationX popup z-index / dismissal accordingly.

**When to update:** only if VisualPortfolio changes its event API. Otherwise leave it alone.

## Freemius

**Important clarification:** NotificationX does **NOT** use Freemius for licensing. Pro licensing is handled separately.

The "Freemius" code under [includes/Extensions/Freemius/](../../includes/Extensions/Freemius/) is an **Extension** — a *data source* for showing Freemius-tracked plugin sales as notifications, intended for plugin developers who use Freemius themselves.

Don't confuse the two:
- [includes/Extensions/Freemius/](../../includes/Extensions/Freemius/) — Extension, data source.
- (no Freemius licensing code in this repo)

## Cart abandonment / FluentCart / WooCommerce

These look like third-party shims at a glance but are actually full Extensions. Their integration is documented as part of the canonical Extension pattern. See:
- [includes/Extensions/WooCommerce/](../../includes/Extensions/WooCommerce/)
- [includes/Extensions/FluentCart/](../../includes/Extensions/FluentCart/)
- [includes/Extensions/EDD/](../../includes/Extensions/EDD/)

## When to write a shim vs an Extension

| Situation | Choice |
|---|---|
| The third party emits *data* you want to display in popups | Extension under `includes/Extensions/` |
| The third party visually conflicts with NotificationX (z-index, event collision, asset conflict) | Shim under `includes/ThirdParty/` |
| Both | Two files: an Extension for data, a Shim for compatibility |

## Adding a new shim

```
includes/ThirdParty/AcmePlugin.php
```

```php
<?php
namespace NotificationX\ThirdParty;

use NotificationX\GetInstance;

class AcmePlugin {
    use GetInstance;

    public function __construct() {
        if ( ! defined( 'ACME_PLUGIN_VERSION' ) ) {
            return;  // Acme not active — no-op.
        }
        add_filter( 'acme_relevant_filter', array( $this, 'fix_thing' ) );
    }

    public function fix_thing( $value ) {
        // …
        return $value;
    }
}
```

Then register it in [NotificationX.php](../../includes/NotificationX.php) constructor alongside WPML/VisualPortfolio (line 86–87 area):

```php
ThirdParty\AcmePlugin::get_instance();
```

**Guard rule:** every shim's constructor must check whether the third party is actually loaded (`defined`/`class_exists`/`function_exists`) and bail out cleanly if not. Otherwise you'll pay the cost on every page load for nothing.
