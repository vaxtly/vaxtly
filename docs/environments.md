# Environments

Environments let you define sets of variables that can be swapped in and out of your requests. Use them to switch between production, staging, and development APIs without editing each request individually.

## Table of Contents

- [Overview](#overview)
- [Creating an Environment](#creating-an-environment)
- [Variables](#variables)
- [Using Variables in Requests](#using-variables-in-requests)
- [Activating an Environment](#activating-an-environment)
- [Collection Environments](#collection-environments)
- [Folder Environments](#folder-environments)
- [Environment Inheritance](#environment-inheritance)
- [Auto-Activation](#auto-activation)
- [Environment Lock](#environment-lock)
- [Context-Aware Selector](#context-aware-selector)

---

## Overview

An environment is a named set of key-value variables. For example:

| Environment | `base_url` | `api_key` |
|---|---|---|
| Production | `https://api.example.com` | `pk_live_xxx` |
| Staging | `https://staging.api.example.com` | `pk_test_xxx` |
| Local Dev | `http://localhost:8000` | `pk_dev_xxx` |

Only one environment can be active at a time (per workspace). When active, its variables are available for substitution in all requests.

---

## Creating an Environment

1. Switch to the **Environments** panel in the sidebar (toggle at the top)
2. Click **New Environment**
3. Type a name and press Enter
4. Add variables in the editor panel on the right

---

## Variables

Each variable has three properties:

- **Key:** The variable name (e.g., `base_url`, `api_key`)
- **Value:** The variable value (e.g., `https://api.example.com`)
- **Enabled:** Toggle to temporarily disable a variable without deleting it

### Adding Variables

Click the **+ Add Variable** button or start typing in the empty row at the bottom of the variables list. Changes are auto-saved with a short debounce (500ms).

### Disabling Variables

Click the toggle next to a variable to disable it. Disabled variables are not substituted in requests but remain in the environment for easy re-enabling.

---

## Using Variables in Requests

Reference environment variables using `@{{variable_name}}` syntax. This works in:

- **URL** — `@{{base_url}}/users`
- **Headers** — `Authorization: Bearer @{{api_key}}`
- **Query parameters** — key or value fields
- **Request body** — anywhere in the body content
- **Form data** — key or value fields
- **Auth fields** — bearer token, basic auth username/password, API key value

Variables are resolved at the moment you send the request, using the currently active environment.

### Collection Variables

Collections also have their own variables (editable in the collection settings). Both collection and environment variables are available in requests. If the same key exists in both, the environment variable takes precedence.

---

## Activating an Environment

There are several ways to activate an environment:

1. **Environment selector** (top-right of the request builder) — click and pick an environment
2. **Environments panel** — click the radio/toggle button next to an environment
3. **Auto-activation** — set a default environment on a collection or folder (see below)

To deactivate all environments, select **No Environment** in the selector.

---

## Collection Environments

You can associate specific environments with a collection. This serves two purposes:

1. **Organization** — The environment selector shows associated environments first, making it faster to pick the right one
2. **Auto-activation** — Set a default environment that activates automatically when you open a request from that collection

### Setting Up Collection Environments

1. Right-click a collection (or click the 3-dot menu)
2. Click **Set environments**
3. Check the environments you want to associate
4. Click the star icon next to one to make it the default

The default environment (starred) auto-activates when you open or switch to a request in that collection.

---

## Folder Environments

Folders can have their own environment associations, independent of the collection. This is useful when a collection contains folders that target different APIs or services.

### Example

Imagine a "Salesforce" collection with two folders:

| Folder | Default Environment |
|---|---|
| Salesforce B2B | `Salesforce B2B Production` |
| Salesforce B2C | `Salesforce B2C Staging` |

When you open a request in the "Salesforce B2B" folder, the "Salesforce B2B Production" environment activates automatically. Switch to a request in "Salesforce B2C" and the environment switches too.

### Setting Up Folder Environments

1. Right-click a folder (or click the 3-dot menu)
2. Click **Set environments**
3. Check the environments you want to associate
4. Click the star icon to set a default

A small green dot appears next to folders that have environment associations.

---

## Environment Inheritance

Folder environments follow an inheritance model. When you open a request, Vaxtly looks for environment settings in this order:

1. **Request's folder** — Does this folder have environment associations?
2. **Parent folder** — Walk up the folder tree, checking each ancestor
3. **Collection** — Fall back to the collection's environment settings
4. **None** — No auto-activation if nothing is configured

The first folder in the tree (starting from the request's folder and walking up) that has environment associations wins. This means you can set environments on a top-level folder and all its subfolders will inherit that setting, unless a subfolder overrides it with its own associations.

### Example with Nesting

```
My Collection (default: Production)
  Auth/                          (no envs set — inherits from collection)
    Login.yaml                   → activates "Production"
    Register.yaml                → activates "Production"
  Payments/                      (default: Stripe Staging)
    Charges/                     (no envs set — inherits from Payments/)
      Create Charge.yaml         → activates "Stripe Staging"
      List Charges.yaml          → activates "Stripe Staging"
    Refunds/                     (default: Stripe Production)
      Create Refund.yaml         → activates "Stripe Production"
  Users/                         (no envs set — inherits from collection)
    Get User.yaml                → activates "Production"
```

---

## Auto-Activation

When you open a request tab or switch between tabs, Vaxtly automatically activates the appropriate environment based on the folder/collection hierarchy described above.

Auto-activation happens when:
- You open a new request tab
- You switch to an existing tab
- You click a request in the sidebar

Auto-activation does **not** happen when:
- The environment lock is engaged (see below)
- No default environment is configured for the request's context

---

## Environment Lock

The lock button (padlock icon) next to the environment selector prevents auto-activation. When locked:

- Switching between request tabs does **not** change the active environment
- You can still manually change the environment using the selector
- The lock button turns amber to indicate it's engaged

Use the lock when you want to test multiple requests against the same environment regardless of their folder/collection settings.

---

## Context-Aware Selector

The environment selector dropdown adapts based on the currently active request:

### When a request is open with associated environments

The dropdown shows:
1. **No Environment** option
2. **Associated environments** — grouped under a "Folder" or "Collection" header, with the default marked by a star
3. **Show all environments** — toggle to reveal the remaining environments

This keeps the dropdown focused on relevant environments while still allowing access to all of them.

### When a request is open but no environments are associated

The dropdown shows:
1. **No Environment** option
2. **Add environments** button — opens the environment association modal for the current folder or collection
3. All environments listed below

### When no request is open

All environments are listed in a flat list (the current behavior, no grouping).
