# Changelog

All notable changes to Vaxtly will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
