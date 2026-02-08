# Vault Integration

Vaxtly integrates with HashiCorp Vault to securely store environment variables (API keys, tokens, passwords) outside your local database. When Vault sync is enabled for an environment, variables are stored in Vault and fetched on demand — they never persist in your local database.

## Table of Contents

- [Overview](#overview)
- [How It Works](#how-it-works)
- [Setting Up HashiCorp Vault](#setting-up-hashicorp-vault)
- [Configuring Vaxtly](#configuring-vaxtly)
- [Enabling Vault Sync on an Environment](#enabling-vault-sync-on-an-environment)
- [Working with Vault-Synced Environments](#working-with-vault-synced-environments)
- [Vault Paths](#vault-paths)
- [Pull from Vault](#pull-from-vault)
- [Push to Vault](#push-to-vault)
- [Renaming Environments](#renaming-environments)
- [Deleting Environments](#deleting-environments)
- [Disabling Vault Sync](#disabling-vault-sync)
- [Troubleshooting](#troubleshooting)

---

## Overview

Without Vault, environment variables are stored in your local SQLite database. This is fine for development but has limitations:

- Sensitive values (API keys, secrets) sit in a local file
- If you share collections via Git Sync, secrets are not included (by design), but they also can't be shared securely
- No audit trail for who accessed which secrets

With Vault integration, sensitive values are stored in HashiCorp Vault's KV (key-value) secrets engine. Vaxtly fetches them when needed and caches them briefly (60 seconds) for performance.

---

## How It Works

1. You configure a Vault connection in Settings
2. You enable Vault sync on specific environments
3. When you enable sync, Vaxtly pushes current variables to Vault and clears them from the local database
4. When you use the environment, Vaxtly fetches variables from Vault on demand
5. Variables are cached for 60 seconds to avoid excessive API calls
6. When you edit variables, Vaxtly writes them back to Vault on save

The local database only stores a `vault_synced = true` flag and the Vault path — the actual secret values live exclusively in Vault.

---

## Setting Up HashiCorp Vault

### Prerequisites

- A running HashiCorp Vault instance (self-hosted or HCP Vault)
- A KV v2 secrets engine enabled (this is the default `secret/` engine in most Vault installations)
- An authentication method: Token or AppRole

### Vault Policies

Your token or AppRole needs the following Vault policy. Replace `secret` with your actual engine path if different:

```hcl
# Read and write secrets for all environments
path "secret/data/*" {
  capabilities = ["create", "read", "update", "delete"]
}

# Delete secret metadata (used when deleting environments)
path "secret/metadata/*" {
  capabilities = ["delete", "list"]
}

# List all secrets (used by "Pull from Vault" to discover environments)
path "secret/metadata" {
  capabilities = ["list"]
}
```

If you want tighter access, you can scope paths to specific environment names:

```hcl
path "secret/data/production" {
  capabilities = ["create", "read", "update", "delete"]
}

path "secret/data/staging" {
  capabilities = ["create", "read", "update", "delete"]
}

path "secret/metadata/production" {
  capabilities = ["delete"]
}

path "secret/metadata/staging" {
  capabilities = ["delete"]
}
```

### Authentication Methods

#### Token Authentication

The simplest method. Use a Vault token directly.

1. Generate a token with the appropriate policy:
   ```bash
   vault token create -policy=vaxtly-policy
   ```
2. Copy the token (typically starts with `hvs.`)

#### AppRole Authentication

Better for automated/production use. Uses a Role ID + Secret ID pair that exchanges for a short-lived token.

1. Enable AppRole (if not already):
   ```bash
   vault auth enable approle
   ```
2. Create a role with your policy:
   ```bash
   vault write auth/approle/role/vaxtly \
     token_policies="vaxtly-policy" \
     token_ttl=1h \
     token_max_ttl=4h
   ```
3. Get the Role ID:
   ```bash
   vault read auth/approle/role/vaxtly/role-id
   ```
4. Generate a Secret ID:
   ```bash
   vault write -f auth/approle/role/vaxtly/secret-id
   ```

If you use AppRole auth, Vaxtly also needs:
```hcl
path "auth/approle/login" {
  capabilities = ["create", "update"]
}
```

---

## Configuring Vaxtly

1. Open **Settings** (gear icon or `Cmd/Ctrl + ,`)
2. Go to the **Vault** tab
3. Fill in:
   - **Provider:** HashiCorp Vault
   - **Vault URL:** Your Vault server address (e.g., `https://vault.example.com`)
   - **Auth Method:** Token or AppRole
   - **Token:** Your Vault token (if using Token auth)
   - **Role ID** and **Secret ID:** (if using AppRole auth)
   - **Namespace:** (Optional, Enterprise only) The Vault namespace for authentication
   - **Engine Full Path:** The KV v2 mount path (default: `secret`)
4. Click **Test Connection** to verify
5. Click **Save**

### Engine Full Path

This is the mount path of your KV v2 secrets engine. Common values:

| Setup | Engine Path |
|---|---|
| Default Vault installation | `secret` |
| Custom mount | `kv` or `my-secrets` |
| Namespaced/scoped | `secret/team-api` |

Vaxtly stores secrets at `{engine_path}/data/{environment_name}`. For example, with engine path `secret` and an environment named "Production", secrets are stored at `secret/data/production`.

The settings UI shows a preview: **"Secrets stored at: `{engine_path}/data/{environment_name}`"**

### Namespace (Enterprise Only)

If your Vault instance uses namespaces (Enterprise feature), enter the namespace here. The namespace is only used during AppRole authentication — it is sent as the `X-Vault-Namespace` header when logging in.

For non-Enterprise Vault installations, leave this blank.

---

## Enabling Vault Sync on an Environment

1. Select an environment in the Environments panel
2. In the environment editor, find the **Vault Integration** section
3. Click **Enable Sync**

What happens when you enable sync:

1. Vaxtly pushes your current variables to Vault
2. Variables are cleared from the local database
3. The environment is marked with a **Vault** badge
4. From now on, variables are fetched from Vault when needed

Only enabled variables with non-empty keys are pushed to Vault. Disabled variables are discarded.

---

## Working with Vault-Synced Environments

### Viewing Variables

When you open a Vault-synced environment, Vaxtly fetches variables from Vault. A 60-second cache prevents excessive API calls — if you just viewed the variables, the cached version is shown.

Click **Refresh from Vault** to force a fresh fetch (bypasses cache).

### Editing Variables

Edit variables normally in the environment editor. When you click **Save**, Vaxtly writes the updated variables to Vault. Only enabled variables with non-empty keys are saved.

### Using Variables in Requests

Vault-synced variables work identically to regular variables. Use `@{{variable_name}}` syntax in your requests — URLs, headers, query params, body, and auth fields all support variable substitution.

The active environment's variables are resolved at request time, fetching from Vault if needed.

---

## Vault Paths

Each environment maps to a path in Vault. By default, the path is derived from the environment name:

| Environment Name | Vault Path |
|---|---|
| Production | `production` |
| Staging | `staging` |
| My Dev Environment | `my-dev-environment` |
| API Keys (v2) | `api-keys-v2` |

The path is a URL-safe slug (lowercase, hyphens replacing spaces and special characters).

### Custom Paths

If you have existing secrets in Vault that don't follow this naming convention, Vaxtly stores and uses whatever path was discovered via "Pull from Vault". You can also set a custom path by renaming the environment to match your desired slug.

### Full Vault Path

The complete path in Vault is: `{engine_path}/data/{environment_path}`

For example:
- Engine path: `secret`
- Environment: "Production"
- Full path: `secret/data/production`

---

## Pull from Vault

**Pull from Vault** (in Settings > Vault) discovers secrets already stored in your Vault engine and creates Vaxtly environments for them.

### How It Works

1. Vaxtly lists all secrets at the engine root path
2. For each secret found that doesn't already have a matching Vaxtly environment:
   - Creates a new environment with:
     - Name derived from the secret path (titleized: `my-api` becomes `My Api`)
     - Vault sync enabled
     - Variables fetched from Vault on demand
3. Reports how many environments were created

This is useful when:
- You already have secrets in Vault from another tool
- You set up Vaxtly on a new device and want to discover existing secrets
- A team member added secrets to Vault that you don't have locally

### Per-Environment Pull

In the environment editor, click **Pull from Vault** to refresh a single environment's variables from Vault. This replaces the current in-editor variables with whatever is in Vault.

---

## Push to Vault

### Push All (Settings)

In Settings > Vault, click **Push All to Vault** to push all Vault-synced environments' variables to Vault. This is useful for initial setup or bulk updates.

### Per-Environment Push

In the environment editor, click **Push to Vault** to push a single environment's variables. This happens automatically on save for Vault-synced environments.

---

## Renaming Environments

When you rename a Vault-synced environment, Vaxtly migrates the secrets in Vault:

1. Reads all secrets from the old path
2. Writes them to the new path
3. Deletes the old path
4. Updates the environment's stored Vault path

This happens automatically — you just rename the environment and save.

---

## Deleting Environments

When you delete a Vault-synced environment:

1. Vaxtly attempts to delete the secrets from Vault
2. If Vault deletion fails (e.g., Vault is unreachable), the environment is still deleted locally
3. You may need to manually clean up orphaned secrets in Vault if deletion failed

---

## Disabling Vault Sync

In the environment editor, click **Disable Sync** to stop using Vault for that environment.

**Important:** Disabling Vault sync does **not** copy variables back from Vault to your local database. The environment will have no variables after disabling. If you need to keep the variables:

1. Note down or export the current variables before disabling
2. Disable Vault sync
3. Re-enter the variables manually

The secrets remain in Vault after disabling sync — they are not deleted.

---

## Troubleshooting

### "Connection failed" on test

- Verify the Vault URL is correct and reachable from your machine
- Check the token hasn't expired
- For Token auth: ensure the token has the required policy
- For AppRole auth: verify both Role ID and Secret ID are correct
- For self-hosted Vault: ensure the server is running and the port is correct

### Variables not loading

- Check that Vault is reachable (test connection in Settings)
- Click **Refresh from Vault** to bypass the 60-second cache
- Verify the environment's Vault path matches an existing secret in Vault
- Check that the token/AppRole has `read` capability on the secret path

### "Permission denied" errors

Your token or AppRole lacks the necessary Vault policy. Ensure it has:
- `read` and `create`/`update` on `{engine}/data/*`
- `delete` on `{engine}/metadata/*` (for environment deletion)
- `list` on `{engine}/metadata` (for Pull from Vault)

### Variables disappear after disabling sync

This is expected behavior. Disabling Vault sync clears the local database copy. The variables still exist in Vault — re-enable sync to access them again.

### Vault token expired

Generate a new token and update it in Settings > Vault. If using AppRole, generate a new Secret ID.

### Namespace issues (Enterprise)

- Namespace is only used for AppRole authentication
- If using Token auth, the namespace field is not used
- Ensure the namespace matches exactly (case-sensitive)
