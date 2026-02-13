# Troubleshooting: Directory Listing Still Shows (Document Root is Correct)

If your document root is set to `public` but you still see a directory listing, try these fixes:

---

## 1. Add `DirectoryIndex` to `.htaccess`

LiteSpeed sometimes needs an explicit `DirectoryIndex` directive. Make sure your `public/.htaccess` includes:

```apache
DirectoryIndex index.php
```

This tells the server to use `index.php` as the default file when accessing a directory.

**The updated `.htaccess` should have this at the top (after Options):**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    DirectoryIndex index.php

    RewriteEngine On
    ...
```

---

## 2. Verify `.htaccess` File Exists on Server

- **Check:** Log into cPanel File Manager
- **Navigate to:** `public/` folder (or wherever your document root is)
- **Confirm:** `.htaccess` file exists
- **If missing:** Upload the `.htaccess` file from your local project

**Note:** Files starting with `.` (dot) are hidden. In File Manager, enable "Show Hidden Files" to see `.htaccess`.

---

## 3. Check File Permissions

Ensure `.htaccess` and `index.php` have correct permissions:

- **`.htaccess`:** `644` (readable by web server)
- **`index.php`:** `644` (readable and executable)
- **`public/` folder:** `755` (readable and executable)

In cPanel File Manager:
- Right-click `.htaccess` → **Change Permissions** → Set to `644`
- Right-click `index.php` → **Change Permissions** → Set to `644`
- Right-click `public/` folder → **Change Permissions** → Set to `755`

---

## 4. Test if `.htaccess` is Being Processed

Create a test file `public/test-htaccess.php`:

```php
<?php
echo "PHP is working!";
```

- **If you can access:** `https://yourdomain.com/test-htaccess.php` → PHP works
- **If you see directory listing instead:** `.htaccess` is not being processed

**Delete the test file after checking.**

---

## 5. Check LiteSpeed Configuration

LiteSpeed should process `.htaccess` files by default, but verify:

1. **In cPanel:** Look for **LiteSpeed Cache** or **LiteSpeed Web Server** settings
2. **Check:** "Allow .htaccess Override" or similar setting is enabled
3. **If disabled:** Enable it (contact hosting support if you can't find it)

---

## 6. Try Accessing `index.php` Directly

Visit: `https://yourdomain.com/index.php`

- **If it works:** The problem is `.htaccess` not being processed
- **If it doesn't work:** There's a PHP or Laravel configuration issue

---

## 7. Check Error Logs

In cPanel → **Errors** or **Error Log**:

- Look for errors related to `.htaccess` parsing
- Look for PHP errors when accessing `index.php`
- Common errors:
  - "Invalid command" → `.htaccess` syntax error
  - "mod_rewrite not enabled" → Contact hosting support
  - PHP fatal errors → Check Laravel configuration

---

## 8. Verify Document Root is Actually `public`

Double-check in cPanel:

1. **Domains** → **Manage**
2. **Find your domain**
3. **Check Document Root path:**
   - Should end with `/public` (e.g., `/home/username/public_html/quizsnap/public`)
   - Should **NOT** be `/home/username/public_html/quizsnap` (missing `/public`)

**Sometimes the path looks correct but isn't saved properly. Try changing it and saving again.**

---

## 9. Temporary Workaround: Add `index.php` Redirect

If nothing else works, add this at the **very top** of `public/.htaccess` (before everything else):

```apache
DirectoryIndex index.php
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/$
RewriteRule ^(.*)$ index.php [L]
</IfModule>
```

Then add the rest of your Laravel rules below.

---

## 10. Contact Hosting Support

If none of the above works, contact ausweblabs.com support with:

- **Domain:** Your domain name
- **Issue:** Directory listing shows instead of Laravel app
- **Document Root:** Confirm it's set to `public` folder
- **Request:** Ask them to verify:
  - `.htaccess` processing is enabled
  - `mod_rewrite` is enabled
  - `DirectoryIndex index.php` is configured
  - No server-level restrictions blocking `.htaccess`

---

## Quick Checklist

- [ ] `DirectoryIndex index.php` added to `public/.htaccess`
- [ ] `.htaccess` file exists on server in `public/` folder
- [ ] `.htaccess` permissions: `644`
- [ ] `index.php` permissions: `644`
- [ ] `public/` folder permissions: `755`
- [ ] Document root confirmed to end with `/public`
- [ ] Tested accessing `index.php` directly
- [ ] Checked error logs for clues
- [ ] Cleared browser cache and tried again

Most common fix: Adding `DirectoryIndex index.php` to `.htaccess` resolves the issue on LiteSpeed servers.
