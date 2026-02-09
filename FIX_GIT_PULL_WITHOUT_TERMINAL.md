# Do exactly what Git asks: “stash” local changes to .env.example (no terminal)

When cPanel Git Update fails with:

```text
error: Your local changes to the following files would be overwritten by merge:
    .env.example
Please commit your changes or stash them before you merge. Aborting
```

Git wants you to **stash** (or commit) the local changes to `.env.example` so the merge can run. You can do the same thing using **only cPanel File Manager**—no terminal.

---

## Steps (File Manager only)

### 1. Open your app folder

In **cPanel → File Manager**, go to the folder that contains **`.env`**, **`artisan`**, and **`.env.example`** (your app root, not inside `public`).

### 2. “Stash” the server’s .env.example (save it, then remove it)

- **Option A – Rename (recommended)**  
  - Right‑click **`.env.example`** → **Rename**.  
  - New name: **`.env.example.stashed`** (or **`.env.example.bak`**).  
  - Click **Rename**.  
  So: local “changes” are gone from Git’s point of view (the tracked file no longer exists), and you still have a copy.

- **Option B – Delete**  
  - Right‑click **`.env.example`** → **Delete**.  
  Your live site uses **`.env`**, not `.env.example`, so this is safe. You just lose the server’s current `.env.example` copy.

### 3. Run Git Update again

In cPanel go to **Git Version Control** (or **Deploy**) and run **Update** / **Pull** again.

The merge will no longer see “local changes” to `.env.example`, so it will succeed and the repo’s **`.env.example`** will be restored in your app folder.

---

## Summary

| What Git is asking | What you did (no terminal) |
|--------------------|-----------------------------|
| “Stash your changes” | Renamed `.env.example` → `.env.example.stashed` (or deleted it). |
| Result | Merge can run; pull succeeds; repo’s `.env.example` is back. |

You did exactly what Git asked—you stashed the local changes (by moving/removing the file) so the merge can proceed.
