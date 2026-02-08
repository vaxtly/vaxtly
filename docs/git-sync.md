# Git Sync

Vaxtly can synchronize your collections to a GitHub or GitLab repository, enabling team collaboration and version-controlled backups. Each collection is stored as a directory of YAML files in your repository.

## Table of Contents

- [Overview](#overview)
- [Setting Up GitHub](#setting-up-github)
- [Setting Up GitLab](#setting-up-gitlab)
- [Configuring Vaxtly](#configuring-vaxtly)
- [How Sync Works](#how-sync-works)
- [Enabling Sync on a Collection](#enabling-sync-on-a-collection)
- [Pushing and Pulling](#pushing-and-pulling)
- [Auto-Sync](#auto-sync)
- [Conflict Resolution](#conflict-resolution)
- [Repository Structure](#repository-structure)
- [Troubleshooting](#troubleshooting)

---

## Overview

Git Sync turns your collections into version-controlled YAML files stored in a Git repository. This allows you to:

- **Back up** collections to a remote repository
- **Share** collections across devices or with team members
- **Track changes** over time with Git history
- **Restore** collections from remote if local data is lost

Sync is per-collection — you choose which collections to sync. Vaxtly handles serialization, conflict detection, and atomic commits automatically.

---

## Setting Up GitHub

### Step 1: Create a Repository

1. Go to [github.com/new](https://github.com/new)
2. Create a new repository (public or private)
3. You can leave it empty — Vaxtly will create the initial files

### Step 2: Create a Personal Access Token

Vaxtly needs a token to read and write files in your repository.

#### Option A: Fine-Grained Token (Recommended)

Fine-grained tokens give minimal, scoped access to specific repositories.

1. Go to **Settings > Developer settings > Personal access tokens > Fine-grained tokens**
2. Click **Generate new token**
3. Configure:
   - **Token name:** `Vaxtly` (or any name you'll remember)
   - **Expiration:** Choose based on your needs (90 days, 1 year, or no expiration)
   - **Repository access:** Select **Only select repositories** and pick your Vaxtly repository
   - **Permissions:** Under **Repository permissions**, set:
     - **Contents:** Read and write
4. Click **Generate token**
5. Copy the token immediately — it starts with `github_pat_` and won't be shown again

#### Option B: Classic Token

Classic tokens grant broader access but are simpler to set up.

1. Go to **Settings > Developer settings > Personal access tokens > Tokens (classic)**
2. Click **Generate new token (classic)**
3. Configure:
   - **Note:** `Vaxtly`
   - **Expiration:** Choose based on your needs
   - **Scopes:** Check `repo` (Full control of private repositories)
4. Click **Generate token**
5. Copy the token — it starts with `ghp_`

### What the Token Can Access

| Permission | Why It's Needed |
|---|---|
| Contents (read/write) | Read and write YAML collection files |
| Git Data API (implicit) | Create atomic multi-file commits |

The token **does not** need access to issues, pull requests, actions, or any other GitHub features.

---

## Setting Up GitLab

### Step 1: Create a Project

1. Go to your GitLab instance and create a new project (blank is fine)
2. Note the full project path — for example: `my-group/my-project` or `my-group/sub-group/my-project`

### Step 2: Create a Personal Access Token

1. Go to **Settings > Access Tokens** (in your user settings, not the project)
2. Click **Add new token**
3. Configure:
   - **Token name:** `Vaxtly`
   - **Expiration date:** Choose based on your needs
   - **Scopes:** Check `api`
4. Click **Create personal access token**
5. Copy the token — it starts with `glpat-`

### Why `api` Scope Is Required

GitLab's `api` scope is needed because Vaxtly uses the Commits API to create atomic multi-file commits. The more limited `read_repository` and `write_repository` scopes don't cover the Commits API endpoint.

| Permission | Why It's Needed |
|---|---|
| api | Read/write files, create commits, list repository tree |

### GitLab Self-Hosted

Vaxtly works with self-hosted GitLab instances. The base API URL is derived from the repository path — Vaxtly uses `https://gitlab.com/api/v4` by default. For self-hosted instances, ensure your GitLab API is accessible from your machine.

---

## Configuring Vaxtly

1. Open **Settings** (gear icon or `Cmd/Ctrl + ,`)
2. Go to the **Remote Sync** tab
3. Fill in:
   - **Provider:** GitHub or GitLab
   - **Repository:** Your repository path
     - GitHub: `owner/repo-name` (e.g., `johndoe/my-api-collections`)
     - GitLab: `group/project-name` or `group/subgroup/project-name`
   - **Token:** Paste your personal access token
   - **Branch:** The branch to sync to (default: `main`)
4. Click **Test Connection** to verify everything works
5. Click **Save**

### Connection Test

The test verifies that:
- Your token is valid and not expired
- The repository exists and is accessible
- The token has the necessary permissions

If the test fails, double-check:
- The repository path is spelled correctly (case-sensitive)
- The token hasn't expired
- The token has the correct scopes/permissions
- The branch exists in the repository

---

## How Sync Works

### Serialization

When you push a collection, Vaxtly converts it into a directory of YAML files:

```
collections/
  {collection-id}/
    _collection.yaml      # Collection metadata (name, description, variables)
    _manifest.yaml         # Ordering of root-level items
    {request-id}.yaml      # Root-level requests
    {folder-id}/
      _folder.yaml         # Folder metadata (name, environment settings)
      _manifest.yaml       # Ordering of folder items
      {request-id}.yaml    # Requests in this folder
      {subfolder-id}/
        ...                # Nested subfolders follow the same pattern
```

This structure is human-readable and produces clean Git diffs when individual requests change.

### Change Detection

Vaxtly uses a 3-way merge algorithm to detect changes:

1. **Base state:** What the files looked like after the last sync
2. **Local state:** Your current collection data
3. **Remote state:** What's currently in the repository

For each file, Vaxtly compares these three states:

| Local Changed | Remote Changed | Result |
|---|---|---|
| No | No | No action needed |
| Yes | No | Safe to push |
| No | Yes | Accept remote version |
| Yes | Yes | **Conflict** — user must resolve |

### Atomic Commits

All file changes in a push are committed atomically — either all files update or none do. This prevents partial updates that could leave the repository in an inconsistent state.

---

## Enabling Sync on a Collection

1. Right-click a collection (or click the 3-dot menu)
2. Go to **Sync > Enable sync**
3. The collection will immediately push to your repository

Once enabled, you'll see a sync status icon next to the collection name:
- **Green arrows:** Synced and up to date
- **Yellow arrows:** Local changes pending push

---

## Pushing and Pulling

### Manual Push/Pull

Right-click a synced collection and use:
- **Sync > Push** — Upload local changes to the repository
- **Sync > Pull** — Download remote changes to your local copy

### Granular Sync

When you save a request in a synced collection, Vaxtly automatically pushes just that request file to the repository (single-file push). This is faster than a full collection push and creates smaller commits.

If the granular push fails (e.g., due to a conflict), the collection is marked as dirty for a full push later.

### Push All / Pull All

In **Settings > Remote Sync**, you can:
- **Pull Now** — Pull all synced collections at once
- **Push All** — Push all synced collections with pending changes

---

## Auto-Sync

Enable **Auto-sync on startup** in Settings > Remote Sync. When enabled, Vaxtly automatically pulls remote changes when the app starts. This keeps your collections up to date if someone else pushed changes.

Auto-sync only pulls — it never pushes automatically.

---

## Conflict Resolution

A conflict occurs when both you and someone else (or you on another device) modified the same collection since the last sync.

When a conflict is detected, Vaxtly shows a resolution modal with two options:

### Keep Local (Force Push)

Overwrites the remote version with your local data. Use this when:
- You know your local version is the correct one
- Someone else's changes should be discarded

### Keep Remote (Force Pull)

Overwrites your local data with the remote version. Use this when:
- The remote version is more up to date
- You want to discard your local changes

There is no automatic merge — you choose one version or the other.

---

## Repository Structure

A typical Vaxtly repository looks like this:

```
collections/
  9a1b2c3d-4e5f-6789-abcd-ef0123456789/
    _collection.yaml
    _manifest.yaml
    a1b2c3d4-e5f6-7890-abcd-ef0123456789.yaml
    f1234567-89ab-cdef-0123-456789abcdef/
      _folder.yaml
      _manifest.yaml
      b2c3d4e5-f6a7-890b-cdef-0123456789ab.yaml
```

### `_collection.yaml`

```yaml
id: 9a1b2c3d-4e5f-6789-abcd-ef0123456789
name: My API Collection
description: API endpoints for my service
variables:
  - { key: base_url, value: 'https://api.example.com', enabled: true }
environment_ids:
  - env-uuid-1
  - env-uuid-2
default_environment_id: env-uuid-1
```

### `_folder.yaml`

```yaml
id: f1234567-89ab-cdef-0123-456789abcdef
name: Users
environment_ids:
  - env-uuid-3
default_environment_id: env-uuid-3
```

### Request YAML

```yaml
id: a1b2c3d4-e5f6-7890-abcd-ef0123456789
name: Get Users
method: GET
url: '@{{base_url}}/users'
headers:
  - { key: Accept, value: application/json, enabled: true }
query_params: []
body: ''
body_type: none
```

---

## Troubleshooting

### "Connection failed" on test

- Verify the repository path is correct and case-sensitive
- Check that the token hasn't expired
- For GitHub: ensure the token has `Contents` read/write permission on the specific repository
- For GitLab: ensure the token has the `api` scope
- For self-hosted GitLab: ensure the API endpoint is reachable

### "Conflict detected" on push

This means the remote repository has changes that conflict with your local changes. Use the conflict resolution modal to choose which version to keep.

### Push succeeds but no files appear in repository

- Check you're looking at the correct branch
- Verify the `collections/` directory in the repository root

### Sync icon stays yellow after push

The collection may have been modified during the push. Try pushing again. If it persists, check the app logs for errors.

### Token expired

GitHub and GitLab tokens can expire. Generate a new token and update it in Settings > Remote Sync.

### Rate limiting

GitHub allows 5,000 API requests per hour for authenticated users. GitLab has similar limits. Normal Vaxtly usage stays well within these limits. If you hit rate limits, wait and try again.
