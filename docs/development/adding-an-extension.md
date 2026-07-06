# Adding an Extension (Data Source)

> **Status:** stub — outline only. The canonical pattern is [../../includes/Extensions/WooCommerce/](../../includes/Extensions/WooCommerce/); see also [../../includes/Extensions/Extension.php](../../includes/Extensions/Extension.php).

An **Extension** is a data source that feeds an existing **Type**. To add a whole new display category instead, see [adding-a-notification-type.md](adding-a-notification-type.md).

## 1. Pick the Type it feeds
_TODO — choose the matching Type ID (`conversions`, `form`, `reviews`, …). One Extension can feed several Types._

## 2. Create the class
_TODO — `includes/Extensions/<Name>/<Name><Type>.php` extending `Extension`; set `$id`, `$types`, `$module` (settings key), `$class`._

## 3. Dependency detection
_TODO — guard on `class_exists` / `function_exists` / `defined` for the source plugin; module activation is gated by the `$module` settings key — disabled modules never load._

## 4. Implement the behavior
_TODO — `init_extension()` (UI/popup config, field registration) and `get_data()` (fetch/normalize entries). Note which parts are Pro-only stubs in the free plugin._

## 5. Register & document
_TODO — registration via `ExtensionFactory`; add a per-integration doc under [../extensions/](../extensions/) using its `_TEMPLATE.md`, and a row in [../extensions/README.md](../extensions/README.md)._

## Source
- [../../includes/Extensions/Extension.php](../../includes/Extensions/Extension.php)
- [../../includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php)
- [../../includes/Extensions/WooCommerce/](../../includes/Extensions/WooCommerce/)
