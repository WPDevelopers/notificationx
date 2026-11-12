---
name: nx-release-sync
description: After a NotificationX release PR (dev into master) is merged, sync both repos (notificationx and notificationx-pro). Pull master locally, merge master into dev, push dev. Use only when the user asks for the post-release sync.
disable-model-invocation: true
---

# NX post-release sync

Run this only when the user invokes it. Invoking it is the user's permission for the
steps below: pulling `master` and `dev`, merging `master` into `dev`, and pushing `dev`.
It gives no permission for anything else.

## Never do

- Never push to `master` and never merge anything into `master`.
- Never create, push or delete tags. Never create a GitHub release.
- Never use `--force` on any push.
- Never stash, reset, discard or commit the user's uncommitted work.
- Never resolve a merge conflict on your own judgment. Abort the merge and report it.

## Repos

| Repo | Path | GitHub |
|------|------|--------|
| Free | this repo's root (`git rev-parse --show-toplevel`) | `WPDevelopers/notificationx` |
| Pro | sibling folder `../notificationx-pro` (next to this repo in `wp-content/plugins`) | `WPDevelopers/notificationx-pro` |

Resolve both to absolute paths first. If the Pro folder is missing, ask the user for its path.

Do the steps for both repos, Pro first. If the user names only one repo, do only that repo.

## Steps (per repo)

1. **Check the release PR is merged.**
   `gh pr list -R <GitHub> --base master --head dev --state merged --limit 1 --json number,title,mergedAt`
   Also run the same with `--state open`. If a release PR is still open, stop for this repo and tell
   the user it is not merged yet.

2. **Fetch.**
   `git -C <Path> fetch origin --prune`

3. **Check the working tree.** Run `git -C <Path> status --porcelain` and note the current branch
   (`git -C <Path> branch --show-current`).
   If the tree has uncommitted or untracked changes that would block a checkout, stop for this repo.
   List the files and ask the user what to do.

4. **Update local master (fast-forward only).**
   `git -C <Path> checkout master`
   `git -C <Path> pull --ff-only origin master`
   If the fast-forward fails, local `master` has commits that are not on `origin/master`.
   Stop and report them (`git log origin/master..master --oneline`).

5. **Update local dev (fast-forward only).**
   `git -C <Path> checkout dev`
   `git -C <Path> pull --ff-only origin dev`
   Same rule: if it fails, stop and report.

6. **Merge master into dev.**
   `git -C <Path> merge master --no-edit`
   If the merge has conflicts: run `git -C <Path> merge --abort`, list the conflicting files,
   and stop for this repo.

7. **Check before pushing.**
   - `git -C <Path> log --oneline origin/dev..dev` shows what will be pushed.
   - `git -C <Path> rev-list --count dev..master` must be `0`.
   - The plugin version on `dev` must equal the version on `master`
     (free: `notificationx.php`, Pro: `notificationx-pro.php`, plus `Stable tag` in `README.txt`).

8. **Push dev.**
   `git -C <Path> push origin dev`
   If `origin/dev..dev` is empty, dev is already in sync. Skip the push and say so.

9. **Return to the starting branch.**
   `git -C <Path> checkout <starting branch>`

## Report

For each repo, give: the merged release PR, the master and dev commit SHAs after the sync,
what was pushed (or "already in sync"), and anything that stopped the sync. Keep it short.
