---
description: Scaffold a new NotificationX Extension (data source) — creates the class file with the canonical skeleton and registers it in ExtensionFactory.
argument-hint: <VendorName> <type_id>
allowed-tools: Read, Edit, Write, Bash
---

# /nx-scaffold-extension

Scaffold a new NotificationX Extension. Arguments: `$ARGUMENTS`

Expected: `<VendorName> <type_id>` — e.g. `/nx-scaffold-extension Acme conversions`.

## Steps to perform

Parse `$ARGUMENTS` into `$VENDOR` and `$TYPE_ID`. If the user provided fewer than 2 arguments, ask them for the missing pieces before continuing — do **not** invent defaults.

1. **Validate `$TYPE_ID` exists.** Read `includes/Types/TypesFactory.php` and confirm `$TYPE_ID` appears as a key in the `$types` array (around lines 23–42). If not, stop and tell the user the valid Type IDs from that file.

2. **Derive names:**
   - `$ClassSuffix` = the Type class name (e.g. `conversions` → `Conversions`, `contact_form` → `ContactForm`). Look up the actual class name from the FQCN in `TypesFactory::$types` rather than transforming the ID yourself.
   - `$ExtClassName` = `${VENDOR}${ClassSuffix}` (e.g. `AcmeConversions`).
   - `$ExtId` = `strtolower($VENDOR) . '_' . $TYPE_ID` (e.g. `acme_conversions`).
   - `$ModuleKey` = `'modules_' . strtolower($VENDOR)` (e.g. `modules_acme`).
   - `$Dir` = `includes/Extensions/${VENDOR}/`.
   - `$File` = `${Dir}${ExtClassName}.php`.

3. **Check for collisions.**
   - `grep -n "'${ExtId}'" includes/Extensions/ExtensionFactory.php` — if `$ExtId` already exists, stop and tell the user.
   - `ls $File` — if the file exists, stop.

4. **Create the directory** if missing.

5. **Write the class skeleton** at `$File`:

   ```php
   <?php
   namespace NotificationX\Extensions\<VENDOR>;

   use NotificationX\GetInstance;
   use NotificationX\Extensions\Extension;
   use NotificationX\Types\<ClassSuffix>;

   class <ExtClassName> extends Extension {
       use GetInstance;

       public $priority        = 20;
       public $id              = '<ExtId>';
       public $types           = '<TYPE_ID>';
       public $module          = '<ModuleKey>';
       public $module_priority = 16;
       public $doc_link        = 'https://notificationx.com/docs/';

       public function __construct() {
           parent::__construct();
       }

       public function init_extension() {
           $this->title        = __( '<VENDOR>', 'notificationx' );
           $this->module_title = __( '<VENDOR>', 'notificationx' );

           // Inherit display templates from the Type.
           $this->templates = <ClassSuffix>::get_instance()->templates;
       }

       public function get_data( $args = array() ) {
           // TODO: return entries in the shape expected by the <ClassSuffix> Type.
           // For conversions, each entry typically has:
           //   tag_name, tag_product_title, tag_time, tag_country, tag_city, image, link
           return array();
       }
   }
   ```

   Replace every `<...>` placeholder with the derived values. Do NOT leave any placeholders.

6. **Register the class** in `includes/Extensions/ExtensionFactory.php`. Find the `$extension_classes` array (declared around line 31, closing `];` ~line 87). Append a new entry near the bottom of the array, matching surrounding indentation. The existing array is grouped (inline entries first, then loose) rather than strictly alphabetical — just append before the closing `];`:

   ```php
   '<ExtId>' => \NotificationX\Extensions\<VENDOR>\<ExtClassName>::class,
   ```

   Use the Edit tool with sufficient context to make the insertion unambiguous.

7. **Regenerate the Composer classmap.** The plugin uses classmap autoloading (`composer.json` → `autoload.classmap = ["includes", "blocks"]`). A new class file is invisible to PHP until the classmap is rebuilt. Run:

   ```sh
   composer dump-autoload -o
   ```

   Skip only if `vendor/` doesn't exist (fresh checkout) — in that case tell the user to run `composer install` first.

8. **Run `vendor/bin/phpcs --standard=phpcs.xml $File`** if the binary exists. Report any issues — do not auto-fix.

9. **Print a checklist** of what you did and what the user still needs to do:

   ```
   ✅ Created: <File>
   ✅ Registered in ExtensionFactory under '<ExtId>'

   Next steps:
   - [ ] Implement get_data() — see docs/recipes/add-extension.md
   - [ ] Wire up the source-plugin hook or cron (event-driven vs polled)
   - [ ] Add module title/icon and settings defaults if non-default
   - [ ] Enable the module in NotificationX → Settings → Modules
   - [ ] Test: create a notification using this source and verify entries appear
   - [ ] Run npm run pot to update translations
   ```

## Anti-patterns — refuse and explain

- ❌ If `$VENDOR` contains spaces, special chars, or starts with a digit: stop and ask for a valid PHP class name segment.
- ❌ If `$TYPE_ID` is `popup` or any Type that doesn't follow the standard Conversions-style data shape, warn the user that they may need to override the data shape — don't refuse, just flag it.
- ❌ Do not invent a `$priority` or `$module_priority` — use the defaults shown above. The user can tune after.

## On uncertainty

If at any step you're unsure about a value (e.g. an unusual Type ID transformation, an existing collision you can't disambiguate), **stop and ask the user** rather than guessing. Scaffolding wrong is worse than not scaffolding at all.
