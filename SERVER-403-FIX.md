# Fix 403 Forbidden on Live Server (cPanel / Apache)

If **every page** returns **403 Forbidden** after uploading files or adding a DOCX/download script, use these steps.

---

## 1. Document root must point to `public`

Laravel’s web root is the **`public`** folder.

- **Correct:** Document root = `.../your-app/public`  
  So the URL’s document root is the folder that contains `index.php` and `.htaccess`.
- **Wrong:** Document root = `.../your-app` (project root).  
  That can work only if the **root** `.htaccess` is present and Apache follows it; many hosts expect the docroot to be `public`.

**In cPanel:** Domains → your domain → Document Root. Set it to the folder that contains `index.php`, i.e. `public` (e.g. `public_html/public` or `public_html/quizsnap/public`).

---

## 2. Remove any extra .htaccess added for DOCX

A script or rule added “for Office DOCX download” might have added an `.htaccess` that blocks access.

- Search the server for **all** `.htaccess` files (project root, `public/`, and any subfolders).
- Open each and look for:
  - `Require all denied`
  - `Deny from all`
  - `Order deny,allow` with `Deny from all`
- **Remove** those lines or delete that `.htaccess` if it was only for DOCX.  
  This app serves DOCX via Laravel routes (e.g. `exportQuestionsDocx`), not by exposing a folder with an .htaccess.

---

## 3. Permissions

Set permissions so the web server can read files and run `index.php`, and write to Laravel dirs:

- **Folders (including `public`, `storage`, `bootstrap`):** `755`
- **Files:** `644`
- **Laravel writable dirs:** `storage` and `bootstrap/cache` (and their subdirs): `755` or `775`; web user must be able to write (e.g. `chmod -R 775 storage bootstrap/cache` and set group to the web server user if needed).

From the app root:

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
```

(Adjust if your host requires different ownership/group.)

---

## 4. Root .htaccess (only if document root is project root)

If your document root is the **project root** (the folder that contains `app/`, `public/`, etc.), the **root** `.htaccess` must forward to `public/index.php`. For a request to `/`, Apache sees a directory so the usual “not a file, not a directory” conditions fail and the rewrite never runs (causing 403). So the root request must be handled explicitly:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Root URL (/) — request maps to directory so !-d blocks the next rule; send to Laravel explicitly
    RewriteRule ^/?$ public/index.php [L]

    # Serve static files from public/ if they exist
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} ^/(css|js|storage|images|fonts)/
    RewriteCond %{DOCUMENT_ROOT}/public%{REQUEST_URI} -f
    RewriteRule ^(.*)$ public/$1 [L]

    # All other requests go to public/index.php (single front controller)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ public/index.php [L]
</IfModule>
```

If you prefer to use `public` as document root (recommended), point the domain’s document root to `public` and you don’t rely on this file.

---

## 5. `public/.htaccess` (must be present)

The folder that is the document root (usually `public`) must have an `.htaccess` that sends requests to `index.php`. For a request to `/`, the document root is a directory so the usual “not file, not directory” rule never runs; add an explicit root rule. Do **not** add `Require all denied` or `Deny from all`. Use:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteRule ^/?$ index.php [L]
    RewriteRule ^favicon\.ico$ favicon.svg [L]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 6. “ErrorDocument to handle the request” 403

The message *“a 403 Forbidden error was encountered while trying to use an ErrorDocument to handle the request”* means the first 403 happened, then Apache tried to show a custom error page and that **also** returned 403 (e.g. error page in a forbidden path).

- In Apache/cPanel, avoid a custom **ErrorDocument 403** that points to a path under the same restricted tree.
- Or temporarily remove/comment any `ErrorDocument 403` line in `.htaccess` so Apache falls back to its default 403 page. Once the main 403 is fixed (docroot, permissions, or .htaccess), you can restore a safe ErrorDocument if needed.

---

## 7. Checklist

- [ ] Document root = folder that contains `index.php` (i.e. `public`).
- [ ] No `.htaccess` with `Require all denied` or `Deny from all` in the request path.
- [ ] Permissions: dirs `755`, files `644`, `storage` and `bootstrap/cache` writable.
- [ ] Root `.htaccess` present if docroot is project root; `public/.htaccess` present and correct.
- [ ] After changes, clear browser cache and test in a private/incognito window.

DOCX download in this app works via Laravel routes (e.g. Export as DOCX in the quiz admin); no extra “DOCX script” or blocking .htaccess is required.
