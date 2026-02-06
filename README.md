# Vaxtly

A desktop API client built with Laravel, Livewire, and NativePHP. Design, test, and organize HTTP requests in a local-first application with optional Git-based sync.

## Features

- **Request Builder** — Compose HTTP requests with headers, query params, body, and auth
- **Collections** — Organize requests into collections with folder structure
- **Environment Variables** — Define variables per environment and reference them across requests
- **Git Sync** — Push/pull collections to GitHub or GitLab repositories
- **Vault** — Securely store sensitive values (tokens, keys) separate from collection data
- **Import/Export** — Backup and restore your data
- **Desktop App** — Runs as a native desktop application via NativePHP

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
