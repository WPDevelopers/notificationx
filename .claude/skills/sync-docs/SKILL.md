---
name: sync-docs
description: Update the per-Type or per-Extension documentation in docs/types/ or docs/extensions/ after code changes, following the repo's _TEMPLATE.md and documentation conventions. Use when the user asks to update/sync docs for a Type or Extension, or after a feature change that affects documented behavior.
tools: Read, Edit, Write, Bash, Glob, Grep
---

# Sync Type/Extension Docs

Keeps the agent-ready docs (`docs/types/*.md`, `docs/extensions/*.md`) in sync with the code.

## Workflow

1. **Identify the subject**: a Type (`includes/Types/<Name>.php`) or Extension (`includes/Extensions/<Name>/`). If the user didn't name one, derive it from recently changed files: `git diff --name-only HEAD~5` or working-tree status.
2. **Read the conventions first**:
   - [docs/development/documentation-conventions.md](../../../docs/development/documentation-conventions.md)
   - The matching template: [docs/types/_TEMPLATE.md](../../../docs/types/_TEMPLATE.md) or [docs/extensions/_TEMPLATE.md](../../../docs/extensions/_TEMPLATE.md)
3. **Read the code, not your memory**: the doc must reflect the class as it is now — `$id`, `$types`, `$module`, fields registered, templates/themes declared, REST behavior, hooks fired.
4. **Update or create** `docs/types/<slug>.md` / `docs/extensions/<slug>.md`:
   - Keep the template's section order and heading style.
   - Only document observable behavior and extension points — no speculative/roadmap content.
   - Cross-link related docs (the Type a source feeds, sibling extensions).
5. **Verify**: every file path and hook name mentioned in the doc must exist in the codebase (grep each one).

## Rules

- Match the tone/structure of the best existing docs (e.g. `docs/extensions/mailchimp.md`, `docs/types/conversions.md`) — consistency over creativity.
- If code and docs contradict, code wins; note genuinely surprising behavior in the doc rather than silently papering over it.
- Summarize with a list of docs touched and anything you found undocumentable (missing info the user should fill in).
