# Vaxtly

A desktop API client built with Laravel, Livewire, and NativePHP. Design, test, and organize HTTP requests in a local-first application with optional Git-based sync and HashiCorp Vault integration.

## Features

- **Request Builder** — Compose HTTP requests with headers, query params, multiple body types (JSON, form data, URL-encoded, raw), and authentication (Bearer, Basic, API Key)
- **Pre-Request & Post-Response Scripts** — Chain requests and capture response data into variables automatically
- **Collections & Folders** — Organize requests into collections with nested folder structures
- **Environment Variables** — Define variables per environment, reference them with `@{{variable}}` syntax across all request fields
- **Folder-Level Environments** — Assign different default environments to folders within a collection for automatic context switching
- **Git Sync** — Push and pull collections to GitHub or GitLab repositories with conflict detection and resolution
- **Vault Integration** — Store sensitive variables (API keys, tokens) in HashiCorp Vault instead of the local database
- **Workspaces** — Separate collections and environments into isolated workspaces
- **Import/Export** — Backup and restore data, import from Postman (collections, environments, workspace dumps, and archives)
- **Desktop App** — Runs as a native desktop application via NativePHP with auto-updates

## Documentation

- [Git Sync](docs/git-sync.md) — Set up GitHub/GitLab integration, token scopes, sync workflows, and conflict resolution
- [Vault Integration](docs/vault.md) — Configure HashiCorp Vault, authentication methods, policies, and secret management
- [Environments](docs/environments.md) — Environment variables, folder-level defaults, auto-activation, and the context-aware selector

## Requirements

- PHP 8.2+
- Node.js 18+
- Composer
- SQLite

## Installation

```bash
git clone https://github.com/your-org/vaxtly.git
cd vaxtly
composer setup
```

The `composer setup` script handles installing PHP and Node dependencies, generating the application key, running database migrations, and building frontend assets.

## Usage

### Development

```bash
composer dev
```

Starts the development server, queue worker, log viewer, and Vite dev server concurrently.

### Desktop App

```bash
composer native:dev
```

Launches Vaxtly as a native desktop application using NativePHP.

### Testing

```bash
php artisan test
```

### Code Style

```bash
vendor/bin/pint
```

## Tech Stack

- **Backend** — Laravel 12, PHP 8.3
- **Frontend** — Livewire 4, Tailwind CSS v4, Alpine.js
- **Editor** — CodeMirror 6
- **Desktop** — NativePHP
- **Testing** — Pest 4

## License

[MIT](LICENSE)
