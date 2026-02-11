# Changelog

All notable changes to Vaxtly will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-02-11

### Added
- Pin tabs via right-click context menu: pinned tabs anchor left, hide close button, survive "Close Other Tabs", and resist Ctrl+W / middle-click close
- Keyboard shortcuts: Ctrl/Cmd+Enter (send), Ctrl/Cmd+S (save), Ctrl/Cmd+N (new request), Ctrl/Cmd+W (close tab), Ctrl+PageDown/PageUp (next/prev tab), Ctrl/Cmd+L (focus URL), Ctrl/Cmd+P (search sidebar), Ctrl/Cmd+B (toggle sidebar), Ctrl/Cmd+E (cycle environment), F1 (help)
- Collapsible sidebar via Ctrl/Cmd+B with slide transition
- Keyboard shortcuts section in the Help / User Guide modal
- NativePHP menu accelerators for New Request, Save Request, and Close Tab

### Fixed
- Vault cache never invalidating: `fetchVariables()` cached under typo key `vaulta_secrets_` while `clearCache()` forgot the correct key `vault_secrets_`
- Silent exception swallowing: 12 catch blocks now report errors to Laravel log instead of silently discarding them

### Changed
- Narrowed environment deletion cleanup queries with `whereJsonContains` to only load models referencing the deleted environment instead of all models with any environment associations
- Replaced N+1 ancestor queries in `resolveEnvironmentFolder()` with single-query in-memory traversal, plus cycle guard
- Replaced `new ServiceClass` with `app(ServiceClass::class)` across all production code for proper dependency injection and testability
- Added database indexes on 7 foreign key columns (`workspace_id`, `collection_id`, `folder_id`, `parent_id`, `request_id`) for faster queries
- Converted `$collections` in api-tester from public property to `#[Computed]`, eliminating Eloquent collection serialization in every Livewire payload
- Stripped response data (body, headers, status, duration, error) from tab state cache in request-builder to prevent multi-MB payloads with multiple tabs
- Moved inline DB queries from environment-modal blade to `#[Computed]` properties on sidebar component
- Added `#[Renderless]` to 4 environment-modal toggle/default methods since Alpine manages UI state client-side
- Wrapped 15 sync debug/timing logs in `RemoteSyncService` behind `APP_DEBUG` to stop cluttering production logs
- Added depth limit (max 20) to `YamlCollectionSerializer` import/serialize recursion to guard against stack overflow from malformed data

## [0.1.27] - 2026-02-11

### Fixed
- App freeze after update: Livewire JS 404 caused by route cache containing build-time APP_KEY hash while runtime uses persisted key; now deletes stale route cache and compiled views in register() before any provider boots

## [0.1.26] - 2026-02-11

### Fixed
- App won't boot after update to 0.1.25: `route:clear` and `config:clear` deleted pre-built production caches; now only clears compiled views which safely recompile on demand

## [0.1.25] - 2026-02-11

### Fixed
- App freeze after update: stale compiled Blade views referenced old Livewire JS hash causing 404; now auto-clears compiled views on version change

## [0.1.24] - 2026-02-10

### Added
- Boot performance debug logger (`storage/logs/boot.log`) with timestamped entries when `APP_DEBUG=true`

### Changed
- Optimized auto-migration check: compares on-disk file count against cached setting instead of querying the database on every request

## [0.1.23] - 2026-02-10

### Added
- Verify SSL and Follow Redirects global settings in Settings > General
- SSL verification disabled by default for easier local development with self-signed certs

### Fixed
- App freeze after update: auto-sync now runs in a separate Livewire component so UI stays responsive during git/vault sync
- Optimized collection loading to skip encrypted columns, reducing unnecessary IPC decrypt calls on boot

## [0.1.22] - 2026-02-10

### Added
- Enabled/disabled toggle for request headers and query params
- Variable tooltip on hover showing resolved value and source
- Defer sync of git/vault

## [0.1.21] - 2026-02-10

### Added
- Unified tab system for requests and environments
- Nested variable resolution in environments (e.g. `{{url}}` = `{{host}}:{{port}}/{{path}}`)

### Changed
- Sidebar search now filters instantly client-side (no server round-trip)
- Sidebar expand/collapse no longer flickers on re-renders

## [0.1.20] - 2026-02-10

### Fixed
- HTTPS requests failing on macOS due to bundled PHP not finding system CA certificates

## [0.1.19] - 2026-02-10

### Fixed
- App unresponsive when vault or git server is unreachable (e.g. no VPN); added 5-second connect timeout to all HTTP clients

## [0.1.18] - 2026-02-10

### Added
- Update available toast notification on macOS with brew upgrade instructions (auto-update can't install unsigned builds)

### Fixed
- App crash (500 error) when vault or git server is unreachable on start (e.g. no VPN); now shows a warning toast instead
- macOS auto-updater not finding updates due to `latest-mac.yml` missing arm64 entries

## [0.1.17] - 2026-02-10

### Added
- Vault auto-sync on start: automatically pulls vault environments when the app loads
- Auto-sync on start toggle in Vault settings UI
- Vault `verify_ssl` and `auto_sync` settings included in data export/import

### Changed
- Both remote and vault auto-sync default to enabled for new workspaces

## [0.1.16] - 2026-02-09

### Added
- Homebrew tap for macOS installation (`brew install vaxtly/tap/vaxtly`)
- Auto-update Homebrew cask formula on each release

## [0.1.15] - 2026-02-09

### Added
- macOS build support (Apple Silicon arm64 and Intel x64) in CI workflow

### Fixed
- Migrations now check for existing columns before adding them, preventing errors on app updates

## [0.1.14] - 2026-02-09

### Added
- Splash screen with logo and loading indicator shown instantly on app launch
- Cross-machine vault environment resolution: collections synced via git now resolve vault-synced environments by vault_path when UUIDs differ between machines

### Changed
- Enable OPcache with optimized settings for faster app startup and runtime performance (especially on Windows)

## [0.1.13] - 2026-02-09

### Added
- Variable highlighting in request inputs: resolved variables show green, unresolved show red
- Verify SSL toggle in Vault settings to support self-signed certificates

### Fixed
- Variable substitution now supports dashes and dots in keys (e.g., `{{x-api-key}}`)
- Sidebar expanded/collapsed state now persists correctly across reloads and mode switches

## [0.1.12] - 2026-02-09

### Added
- Welcome modal on first launch highlighting key features (environments, git sync, vault, workspaces)
- Welcome Guide button (sparkles icon) in tab bar to revisit the modal
- Welcome Guide item in native Help menu
- Thin horizontal scrollbars globally matching existing vertical style
- Configurable request timeout (1-300 seconds) in Settings > General
- Cancel button to abort in-flight HTTP requests without freezing the app
- Press Enter in URL input to send request

### Changed
- HTTP requests now execute in a separate process, keeping the UI responsive during long requests

## [0.1.11] - 2026-02-09

### Added
- Persist UI state across restarts — open tabs, active tab, and sidebar expanded/collapsed collections and folders are restored on page load
- Each workspace maintains independent UI state

## [0.1.10] - 2026-02-09

### Fixed
- Session expiring after 2 hours in desktop app — increased default lifetime to 1 year

## [0.1.9] - 2026-02-09

### Fixed
- Eliminated ~3s tab switching delay by removing all DOM morphing from tab lifecycle (renderless methods + Alpine-driven tab bar)
- Decoupled sidebar from api-tester request batches by replacing wire:model with event-based communication
- Response data now persists when switching between tabs

## [0.1.8] - 2026-02-08

### Fixed
- Tab switching performance: collapsed two sequential HTTP round-trips into one batched Livewire request
- Eliminated redundant full-collection model loads in request builder (replaced with lightweight name lookup)
- Cached scripts-tab DB query to avoid per-render hits

## [0.1.7] - 2026-02-08

### Added
- Sensitive data warning modal on request save with "Sync without values" option
- "Sync without values" option in the enable-sync workflow
- Expanded sensitive keyword detection (82 param keys, 11 header keys)
- Collection/environment mode switcher icons in sidebar footer
- Middle-click to close tabs
- CHANGELOG.md following Keep a Changelog format

### Changed
- Restyled Send button to transparent background with brand-colored text
- Release skill now updates changelog and GitHub Release notes automatically

### Fixed
- Sidebar performance optimized for large collections

## [0.1.6] - 2025-05-28

### Added
- At-rest encryption for sensitive model data (auth, headers, variables)

## [0.1.5] - 2025-05-27

### Added
- Toast notifications for sync, import, and error feedback

## [0.1.4] - 2025-05-26

### Added
- Right-click context menu for collections and folders
- Drag-and-drop reordering for collections, folders, and requests

### Fixed
- Right-click context menu not closing previous menu
- Git sync orphan file deletion
- Auto-migration breaking CI builds

### Changed
- Save inline edits on blur
- Removed sort-test diagnostic page

## [0.1.3] - 2025-05-25

### Added
- Dedicated documentation window with comprehensive user guide
- Folder-level environment defaults with context-aware selector

## [0.1.2] - 2025-05-24

### Added
- Token scope hints per provider in git sync settings
- Custom Vaxtly app menu replacing default Electron menu

### Changed
- Dynamic repository placeholder based on GitLab/GitHub provider

### Fixed
- Inline name inputs becoming unresponsive during creation/rename
- File and Help menus not opening in Electron

## [0.1.1] - 2025-05-23

### Added
- App version display in About section
- `/release` skill for version bump and build workflow

### Fixed
- GET requests stripping body by using unified send() dispatch
- Postman import body type and format inconsistencies

## [0.1.0] - 2025-05-22

### Added
- Initial beta release
- Collection-based API request management
- Environment variables with activation toggle
- Git sync (GitHub & GitLab) with conflict detection
- Postman collection import
- Import/export data feature
- Code snippet generation (cURL, Python, PHP, JavaScript, Node.js)
- Pre-request and post-response scripts
- Request history tracking
- Dark/light theme support
- Auto-updates via GitHub Releases
