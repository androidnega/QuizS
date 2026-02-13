# Fix "Index of /" Directory Listing on LiteSpeed Server

When you see **"Index of /"** with a directory listing, the server is showing folder contents instead of running Laravel. Here's how to fix it.

---

## Problem

**Symptom:** Browser shows a directory listing (folders like `app/`, `public/`, `routes/`, etc.) instead of your Laravel app.

**Cause:** The **document root** is pointing to the **project root** (the folder with `app/`, `public/`, etc.) instead of the **`public`** folder.

---

## Solution: Set Document Root to `public` Folder

### In cPanel (LiteSpeed):

1. **Go to:** cPanel → **Domains** → **Manage** (or **Subdomains** if using a subdomain)
2. **Find your domain:** `quiz.ausweblabs.com`
3. **Click:** **Change** or **Edit** next to Document Root
4. **Set Document Root to:** The **`public`** folder of your Laravel app

   **Example paths:**
   - If your app is in: `/home/username/public_html/quizsnap/`
   - Set document root to: `/home/username/public_html/quizsnap/public`
   - Or relative: `public_html/quizsnap/public`

5. **Save** and wait a few seconds for LiteSpeed to reload

---

## Alternative: If You Must Use Project Root as Document Root

If you **cannot** change the document root (e.g., shared hosting restrictions), ensure:

1. **Root `.htaccess` exists** (in the project root, same folder as `app/`, `public/`, etc.)
2. **LiteSpeed processes `.htaccess`** (usually enabled by default)
3. **`mod_rewrite` is enabled** (LiteSpeed usually has this)

The root `.htaccess` should rewrite everything to `public/index.php`. But **setting document root to `public` is the recommended Laravel setup**.

---

## Verify It's Fixed

After changing the document root to `public`:

1. **Visit:** `https://quiz.ausweblabs.com/`
2. **Expected:** You should see your Laravel app (QuizSnap landing page), **not** a directory listing
3. **If you still see directory listing:**
   - Wait 30–60 seconds (LiteSpeed may need to reload)
   - Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
   - Check that `public/index.php` exists and is readable (permissions `644`)

---

## Why This Happens

- **Laravel's web root is `public/`** — that's where `index.php` lives
- When document root = project root, the server sees folders (`app/`, `routes/`, etc.) and shows a directory listing if no index file is found or executed
- When document root = `public/`, the server finds `index.php` immediately and runs Laravel

---

## Quick Checklist

- [ ] Document root = `.../your-app/public` (not `.../your-app`)
- [ ] `public/index.php` exists
- [ ] `public/.htaccess` exists (with `Options -Indexes` to prevent directory listings)
- [ ] Permissions: folders `755`, files `644`
- [ ] Wait 30–60 seconds after changing document root
- [ ] Clear browser cache and test again

Once the document root points to `public`, the directory listing will disappear and your Laravel app will load.
