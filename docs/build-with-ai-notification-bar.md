# Build With AI — Notification Bar

How the **"Build With AI"** tab inside the Notification Bar (PressBar) design step works: a Pro-only feature that lets a user type a prompt, calls OpenAI directly from the browser to generate a set of Notification Bar presets, previews them, and applies one to the campaign being edited.

This sits alongside the *Presets* and *Custom* tabs documented in [notification-bar-reference.md](./notification-bar-reference.md) (which covers the Elementor/Gutenberg builder paths) — "Build With AI" is a third, independent path that only ever touches the **built-in theme renderer** fields (`press_content`, `bar_bg_color`, etc.), not Elementor/Gutenberg.

---

## 1. Key files

### Free plugin (`notificationx`) — tab scaffold + gating only

| File | Purpose |
|---|---|
| [includes/Extensions/PressBar/PressBar.php:406-468](../includes/Extensions/PressBar/PressBar.php#L406-L468) | `design_tab_presets_fields()` — adds the "Build With AI" section (`nxbar_build_with_ai`) as a sibling of "Presets" and "Custom" under the Themes tab. Field `type: "nxbar-build_with_ai"` is the hook point. |
| [nxdev/notificationx/fields/Field.tsx:78-79](../nxdev/notificationx/fields/Field.tsx#L78-L79) | QuickBuilder field-type switch maps `"nxbar-build_with_ai"` → the `<BuildWithAI/>` React component. |
| [nxdev/notificationx/fields/BuildWithAI.tsx](../nxdev/notificationx/fields/BuildWithAI.tsx) | The actual mounted component. It renders nothing by itself — it fires the `nx_build_ai_render` WP-hooks filter (`@wordpress/hooks` `applyFilters`) and shows whichever gating message applies. |
| [nxdev/notificationx/components/ProAlertForBuildWithAI.jsx](../nxdev/notificationx/components/ProAlertForBuildWithAI.jsx) | Shown when Pro isn't active — "Build with AI Available for PRO Users Only" + upsell links. |
| [nxdev/notificationx/components/BuildWithAIVersionCompare.jsx](../nxdev/notificationx/components/BuildWithAIVersionCompare.jsx) | Shown when Pro **is** active but its version is `<= 3.0.7` — "please update Pro" notice (checked via `compareVersions(pro_version, '3.0.7')`). |

Free plugin never talks to OpenAI. It only decides *whether to show* the real UI, which is entirely supplied by Pro via the filter.

### Pro plugin (`notificationx-pro`) — the real feature

| File | Purpose |
|---|---|
| [nxdev/index.tsx:14-25](../../notificationx-pro/nxdev/index.tsx#L14-L25) | Hooks `nx_build_ai_render` and renders `<BuildAIProvider><BuildWithAI/></BuildAIProvider>` — this is what actually fills the space the free plugin's `BuildWithAI.tsx` left empty. |
| [nxdev/core/BuildAIContext.jsx](../../notificationx-pro/nxdev/core/BuildAIContext.jsx) | React context holding all AI-builder state: `prompt`, `suggestions` (generated presets), `isLoading`, `generated` (prompt-tab vs design-tab switch), `numberOfGeneration`, prompt-history helpers. |
| [nxdev/components/build-with-ai/BuildWithAI.jsx](../../notificationx-pro/nxdev/components/build-with-ai/BuildWithAI.jsx) | Top component: shows `PromptTab` while `!generated`, `DesignTab` once presets exist. |
| [nxdev/components/build-with-ai/PromptTab.jsx](../../notificationx-pro/nxdev/components/build-with-ai/PromptTab.jsx), [PromptSection.jsx](../../notificationx-pro/nxdev/components/build-with-ai/PromptSection.jsx), [PromptInput.jsx](../../notificationx-pro/nxdev/components/build-with-ai/PromptInput.jsx) | The prompt-entry screen: free-text prompt box + a "Prompt Suggestions" drawer with 5 canned categories (Urgency/FOMO, Offer, Social Proof, Promotion, Announcement — lists in [common/constants.js](../../notificationx-pro/nxdev/components/common/constants.js)), a "Number of Generations" stepper (1–7), and the Generate button. |
| [nxdev/components/build-with-ai/GenerateButton.jsx](../../notificationx-pro/nxdev/components/build-with-ai/GenerateButton.jsx) | Click handler: calls the OpenAI-generation function, then persists the result server-side. |
| [nxdev/core/functions.js](../../notificationx-pro/nxdev/core/functions.js) | `generateNotificationPresets()` — builds the system prompt and calls the OpenAI Chat Completions API **directly from the browser**. `storeNotificationPresets()` — POSTs the generated presets to the plugin's own REST endpoint for history. |
| [nxdev/components/build-with-ai/DesignTab.jsx](../../notificationx-pro/nxdev/components/build-with-ai/DesignTab.jsx) | Shown after generation: a grid of preset cards. Clicking one calls `builderContext.setValues(...)` to push the preset's config straight into the campaign's live form state. |
| [nxdev/components/build-with-ai/presetHelper.js](../../notificationx-pro/nxdev/components/build-with-ai/presetHelper.js) | `getPresetValues()` — maps a generated preset's `config`/`colors` onto the actual NX Bar field names (`bar_bg_color`, `bar_btn_bg`, `bar_font_size`, …), with fallbacks from `colors` when `config` omits a value. |
| [nxdev/components/build-with-ai/AIPreviewGenerator.jsx](../../notificationx-pro/nxdev/components/build-with-ai/AIPreviewGenerator.jsx) | Renders a live, styled preview of each preset card (countdown timer, sliding content, button, coupon) using the same markup/classes as the real frontend bar. |
| [nxdev/components/build-with-ai/NavigationTab.jsx](../../notificationx-pro/nxdev/components/build-with-ai/NavigationTab.jsx) + [common/HistoryModal](../../notificationx-pro/nxdev/components/common/HistoryModal.jsx) | "Start Over" (resets `generated`) and "History" (fetches previously generated preset sets and lets the user re-apply one) controls shown on both tabs. |
| [includes/Core/REST.php:42-51,76-115](../../notificationx-pro/includes/Core/REST.php#L42-L115) | Registers `POST /notificationx/v1/store-ai-presets` and `GET /notificationx/v1/get-ai-presets`. Both just read/write a single `nx_ai_presets` option (an array of `{prompt, count, presets, time}`, newest first). |
| [includes/Admin/Settings.php:49-146](../../notificationx-pro/includes/Admin/Settings.php#L49-L146) | Adds the **API Integrations** settings tab: `openai_access_token`, `openai_model` (`gpt-4.1-nano` / `gpt-4o-mini` / `gpt-5.1` / `gpt-5.2`), `openai_temperature`, `openai_max_tokens` (non-GPT-5 models), `openai_max_completion_tokens` (GPT-5 models), and a "Save Settings" button. |
| [includes/Extensions/PressBar/PressBar.php:47-97](../../notificationx-pro/includes/Extensions/PressBar/PressBar.php#L47-L97) (Pro's `PressBar`, extends the free one) | `connect($params)` — validates the OpenAI key with `GET https://api.openai.com/v1/models` and, if valid, persists all four OpenAI settings via `Settings::set()`. |
| [includes/Core/Rest/Integration.php:203-220](../includes/Core/Rest/Integration.php#L203-L220) (free plugin) | Generic `POST /notificationx/v1/api-connect` handler — resolves the extension by `source` (here `press_bar`) via `ExtensionFactory` and calls its `connect()` method. Shared by every "Save & Test Connection" style integration in the plugin, not AI-specific. |

### Styling / assets

| File | Purpose |
|---|---|
| `notificationx-pro/nxdev/scss/admin/build_with_ai.scss` | Styles for the Pro prompt/design tabs, preset cards, history modal. |
| [nxdev/notificationx/scss/nx_new/_build_with_ai.scss](../nxdev/notificationx/scss/nx_new/_build_with_ai.scss) | Free-plugin styles for the pro-alert / version-compare gating states. |
| `assets/admin/images/icons/build-with-ai-icon.svg` | Tab icon. |

---

## 2. Where it lives in the admin UI

Inside a Notification Bar campaign, **Design tab → Themes → Build With AI**, as a sibling tab to **Presets** and **Custom**:

```
themes_tab
├── for_desktop        (section "Presets")
├── nxbar_custom        (section "Custom" — the manual NX Bar editor)
└── nxbar_build_with_ai (section "Build With AI")   ← this feature
    └── nxbar_build_with_ai_fields  (type: nxbar-build_with_ai → <BuildWithAI/>)
```

Gating, in order:
1. Free plugin renders the tab shell regardless of license.
2. If Pro is **not** active → `ProAlertForBuildWithAI` upsell, nothing else.
3. If Pro is active but version `<= 3.0.7` → `BuildWithAIVersionCompare` "please update" notice.
4. Otherwise Pro's `nx_build_ai_render` filter supplies the real `<BuildWithAI/>` (prompt/design tabs).

---

## 3. One-time setup: connecting OpenAI

Before generation works, the user must go to **NotificationX → Settings → API Integrations** and enter an OpenAI API key ([platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys)), pick a model, and click **Save Settings**.

```
[Settings UI] openai_access_token, openai_model, openai_temperature, openai_max_tokens
    └── click "Save Settings"
        └── POST /notificationx/v1/api-connect  { source: 'press_bar', ...fields }
            └── Integration::api_connect()  (free plugin, generic dispatcher)
                └── ExtensionFactory::get('press_bar') → Pro's PressBar::connect($params)
                    └── GET https://api.openai.com/v1/models  (Bearer <token>)
                        ├── 401 → "Incorrect API key"
                        ├── 429 → "Rate limit exceeded"
                        └── 200 → Settings::set('settings.openai_*', ...) for all 4 fields
```

If no token is saved, `BuildHeader.jsx` shows an inline warning ("To generate designs with AI, you need to add your OpenAI API key…") with a button that deep-links to that settings tab, and `GenerateButton` refuses to call OpenAI at all.

**Note:** the saved token is read back into the page via `window.notificationxTabs.settings.savedValues.openai_access_token` and used **client-side** — the browser calls `api.openai.com` directly with the key attached as a Bearer token (see next section). There is no PHP-side proxy for the generation call itself (only for the initial validation in `connect()`).

---

## 4. Generation flow (Prompt tab)

```
[User] types/selects a prompt, sets "Number of Generations" (1-7), clicks Generate
    └── GenerateButton.handleGenerate()
        ├── getHistoryForPrompt(prompt, numberOfGeneration)
        │     └── GET /notificationx/v1/get-ai-presets → find cached entry with same {prompt, count}
        │           └── HIT → reuse cached presets, skip the OpenAI call entirely, scroll into view
        └── MISS → generateNotificationPresets({ prompt, numberOfGeneration, openaiToken, openaiModel, ... })
              ├── builds a system prompt (functions.js: `system_prompt(numberOfGeneration)`) that instructs
              │     the model to return a JS array of exactly N preset objects in a fixed shape (see §5)
              ├── POST https://api.openai.com/v1/chat/completions
              │     { model, messages: [system, user=prompt], temperature/max_tokens (or
              │       max_completion_tokens + temperature=1 for gpt-5* models) }
              │     — 30s client-side timeout via AbortController
              ├── on non-2xx → maps 401/429/400 to a friendly error string, shown via toast
              ├── on success → `presets = eval(response.choices[0].message.content)`
              │     (the model is expected to return a raw JS array literal, not JSON — parsed with eval, not JSON.parse)
              └── on parse success:
                    ├── setSuggestions(presets); setGenerated(true)  → BuildWithAI switches to DesignTab
                    ├── addToGenerationHistory(prompt, presets, count)  (client-side cache in React state)
                    └── storeNotificationPresets({ presets, prompt, count })
                          └── POST /notificationx/v1/store-ai-presets
                                └── REST::store_ai_presets() appends { prompt, count, presets, time }
                                      to the `nx_ai_presets` wp_option (server-side history, shared site-wide)
```

---

## 5. Preset shape produced by the model

The system prompt (`functions.js:29-90`) asks the model for an array of objects like:

```js
{
  id: '123456',                       // random 5-6 digit string
  title: 'Flash Sale Frenzy',
  description: '...',
  style: 'modern' | 'minimal' | 'bold' | ...,
  colors: { primary, secondary, text, background },   // hex/gradient strings
  config: {
    nx_id: '',
    themes: 'theme-one',
    position: 'top',
    sticky_bar: true|false,
    advance_edit: true,
    enable_countdown, countdown_text, countdown_start_date, countdown_end_date, evergreen_timer,
    press_content,                    // HTML string (static content)
    bar_content_type: 'static'|'sliding',
    sliding_content, sliding_interval, bar_transition_style, bar_transition_speed,  // only if sliding
    button_text, button_url,
    bar_bg_color, bar_text_color, bar_btn_bg, bar_btn_text_color,
    bar_counter_bg, bar_counter_text_color,           // only if countdown enabled
    bar_font_size, bar_close_position, bar_close_color, bar_close_button_size,
  }
}
```

`colors` and `config` fields map 1:1 to real NX Bar field names, which is what makes `presetHelper.getPresetValues()` a near pass-through: it spreads `cfg` over the existing `builderContext.values`, then fills a handful of color/typography fields from `colors` as a fallback whenever `config` didn't specify them.

---

## 6. Applying a preset (Design tab)

```
[User] clicks a preset card in DesignTab
    └── handleSelectPreset(preset)
        └── getPresetValues(builderContext, preset, { link_button_bg_color, link_button_text_color, link_button_text })
              → merges preset.config over builderContext.values (the live QuickBuilder form state)
        └── builderContext.setValues(updatedValues)
              → the campaign's form re-renders with the AI-generated content/colors already filled in;
                nothing is saved until the user hits the campaign's own Save button
```

Each preset card also renders a full live preview via `AIPreviewGenerator` (same markup classes as the real frontend bar — countdown, sliding content, close button, coupon — so what you pick is close to what you'll get).

---

## 7. History

- **Client-side, session-only**: `BuildAIContext.generationHistory` — an in-memory cache keyed by `` `${prompt}::${count}` ``, used only to short-circuit a repeat Generate click within the same page load.
- **Server-side, persistent, shared across all admins**: the `nx_ai_presets` option (via `store-ai-presets` / `get-ai-presets`). `NavigationTab`'s **History** button opens `HistoryModal`, which fetches this option and lets the user re-apply any past generation. There's no per-user scoping and no size cap on this option — every generation ever made on the site accumulates in it.

---

## 8. Notable implementation details / caveats

- **OpenAI calls happen client-side.** The saved API key is sent to the browser (`window.notificationxTabs.settings.savedValues`) and `fetch()`'d straight to `api.openai.com` from `GenerateButton`/`functions.js`. Only the one-time key *validation* (`Settings > API Integrations > Save`) goes through a PHP-side `wp_remote_get`.
- **Response parsing uses `eval()`**, not `JSON.parse()` — the system prompt explicitly asks for "valid JavaScript syntax", and the code trusts the model's output enough to `eval` it directly (`functions.js:170`).
- **This feature is Notification Bar–specific.** The system prompt hard-codes the NX Bar field schema (`press_content`, `bar_bg_color`, `sliding_content`, …); it isn't a generic "Build With AI" reusable across other Types (Popup, Sales notification, etc.) as it stands today.
- **Gating is two-layered**: Pro-active check, then a Pro *version* check (`<= 3.0.7`) baked into the free plugin — bump `BuildWithAIVersionCompare`'s threshold if a future Pro release changes the AI payload shape in a backward-incompatible way.
- **No rate limiting / usage caps** are enforced by the plugin itself beyond whatever OpenAI's account-level limits do; a 429 from OpenAI surfaces as a toast ("Rate limit exceeded. Please try again later.").
