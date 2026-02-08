---
name: release
description: >-
  Bump the app version, commit, push, and trigger the build workflow.
  Usage: /release patch | /release minor | /release major
disable-model-invocation: true
---

# Release

Bump the Vaxtly version, commit, push, and trigger the CI build.

## Arguments

`$ARGUMENTS` should be one of: `patch`, `minor`, `major`.

- If no argument is provided or it's not one of the three, **ask the user** which version bump they want using AskUserQuestion.

## Version Bump Rules

Given the current version `X.Y.Z`:

| Bump  | Result          |
|-------|-----------------|
| patch | `X.Y.(Z+1)`    |
| minor | `X.(Y+1).0`    |
| major | `(X+1).0.0`    |

## Files to Update

All three files must have the **exact same version string**:

1. `config/app.php` — `'version' => 'X.Y.Z',`
2. `nativephp/electron/package.json` — `"version": "X.Y.Z",`
3. `config/nativephp.php` — `env('NATIVEPHP_APP_VERSION', 'X.Y.Z')`

## Steps

1. Read the current version from `config/app.php`.
2. Calculate the new version based on the bump type.
3. Show the user: `Current: X.Y.Z -> New: A.B.C` and confirm before proceeding.
4. Update all 3 files.
5. Run `vendor/bin/pint --dirty --format agent`.
6. Run `php artisan test --compact` to verify nothing is broken.
7. Commit with message: `Bump version to A.B.C`
8. Push to remote.
9. Trigger the build: `gh workflow run build.yml -f version=A.B.C -R vaxtly/vaxtly`
10. Show the user how to check build status: `gh run list -R vaxtly/vaxtly --workflow=build.yml --limit 3`
