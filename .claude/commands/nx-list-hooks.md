---
description: Enumerate all nx_* filters/actions exposed by the free plugin — the contract surface between free and Pro. Reports new/removed hooks vs docs/integrations/pro-hooks.md.
argument-hint: (no arguments)
allowed-tools: Bash, Read
---

# /nx-list-hooks

List the filter/action contract surface that the free NotificationX plugin exposes. Use this to audit drift between the actual code and `docs/integrations/pro-hooks.md`.

## Steps to perform

1. **Grep for global hooks** (filters/actions named `nx_<word>`):

   ```sh
   grep -rEn "apply_filters\(\s*['\"]nx_[a-z_]+['\"]" includes/ \
     | sed -E "s|.*apply_filters\(\s*['\"]([^'\"]+)['\"].*|\1|" \
     | sort -u
   ```

   And:

   ```sh
   grep -rEn "do_action\(\s*['\"]nx_[a-z_]+['\"]" includes/ \
     | sed -E "s|.*do_action\(\s*['\"]([^'\"]+)['\"].*|\1|" \
     | sort -u
   ```

2. **Grep for per-source hooks** (dynamic names with `{$id}` or `{$source}` interpolation):

   ```sh
   grep -rEn "apply_filters\(\s*['\"]nx_[a-z_]+_['\"]" includes/
   grep -rEn "do_action\(\s*['\"]nx_[a-z_]+_['\"]" includes/
   grep -rEn "apply_filters\(\s*\"nx_[a-z_]+_\{" includes/
   grep -rEn "do_action\(\s*\"nx_[a-z_]+_\{" includes/
   ```

   These won't be picked up by the static grep in step 1 — they require the dynamic-naming variants.

3. **Build the actual list.** Combine results from steps 1 and 2. For each hook, record: name (with `{$source}` placeholder if dynamic), file:line, and the literal `apply_filters` / `do_action` call so the reader can see args.

4. **Read `docs/integrations/pro-hooks.md`** and extract the documented hook names (the tables list them).

5. **Diff**:
   - **In code, not in docs** → list as "undocumented hooks" — these are either intentionally internal or stable-but-not-yet-promoted. The user must decide which.
   - **In docs, not in code** → list as "removed/missing hooks" — the doc is stale. These need investigation; a hook documented as stable should not disappear silently.

6. **Output format:**

   ```
   ## Hooks in code: <count>
   ## Hooks documented: <count>

   ### Undocumented hooks (in code, not in docs)
   - nx_some_filter — includes/Core/Foo.php:42 — apply_filters('nx_some_filter', $value)
   - nx_other_{$source} — includes/Extensions/Extension.php:175 — apply_filters("nx_other_{$source}", ...)
   ...

   ### Missing hooks (in docs, not in code)
   - nx_thing — documented in pro-hooks.md but no code reference found
   ...

   ### Recommendation
   <one-sentence recommendation: update docs / investigate removed hooks / promote internal hooks to stable>
   ```

7. **Do NOT auto-update `pro-hooks.md`.** The user must review which undocumented hooks should be promoted to stable. Offer to add a section to it if they want, but don't write without confirmation.

## Anti-patterns

- ❌ Reporting hook *call sites* (e.g. `add_filter('nx_foo', ...)`) — those are *consumers*, not the contract surface. Only `apply_filters`/`do_action` calls are the contract surface.
- ❌ Including third-party hooks the plugin merely reacts to (`woocommerce_*`, `wp_*`).
- ❌ Listing every per-source filter invocation separately — group by the template (e.g. `nx_filtered_data_{$source}`), not by every individual source firing it.

## Reference

- `docs/integrations/pro-hooks.md` — current documented contract.
- `docs/decisions/0003-dual-frontend-builds.md` — note that frontend bundles also have a contract surface (`window.notificationXArr`, etc.) — this command does NOT cover that. Frontend contract is a separate audit.
