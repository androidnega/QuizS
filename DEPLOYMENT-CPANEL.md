# QuizSnap - cPanel Deployment Guide

Clean installation instructions for uploading to cPanel.

## 1. Download Clean Package

### Option A: Download from GitHub
1. Go to: https://github.com/androidnega/QuizS
2. Click **Code** → **Download ZIP**
3. Extract the ZIP on your computer

### Option B: Clone (if you have Git)
```bash
git clone https://github.com/androidnega/QuizS.git QuizSnap
cd QuizSnap
```

## 2. Prepare for Upload

Before uploading:

1. **Copy `.env.example` to `.env`** (in project root)
   - Rename `env.example.dist` to `.env` if `.env.example` doesn't exist

2. **Edit `.env`** with your settings:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://quizsnap.ausweblabs.com`
   - Database credentials (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
   - Other settings as needed

3. **Run locally (optional)** to generate dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   If you can't run composer locally, skip this - see Step 5 below.

## 3. Upload to cPanel

1. Log into cPanel
2. Open **File Manager**
3. Go to `public_html` (or your domain folder)
4. **Important:** Your Laravel `public` folder contents must be in the web root

### Correct structure for cPanel:

**Option A: Domain points to subdomain/subfolder**
```
/home2/auswebl6/
└── quizsnap/           ← Upload entire project here
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/         ← Document root should point HERE
    │   ├── .htaccess
    │   ├── index.php
    │   ├── css/
    │   ├── js/
    │   └── ...
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/         ← Must exist (run composer)
    ├── .env
    └── ...
```

**Option B: Document root is public_html**
- Upload project contents so that `public/` contents go in `public_html/`
- Upload everything else (app, bootstrap, etc.) in the parent folder
- Edit `public_html/index.php` to point to correct paths (Laravel default)

5. **Set document root** in cPanel Domains:
   - Point your domain to `quizsnap/public` (not just `quizsnap`)

## 4. Set Permissions

In File Manager, set these permissions:
- `storage/` → 755 (recursive)
- `bootstrap/cache/` → 755 (recursive)

## 5. Install Dependencies (Composer)

If `vendor/` folder is not uploaded:

**Option A: cPanel Terminal**
1. cPanel → Terminal
2. Run:
   ```bash
   cd ~/quizsnap
   composer install --no-dev --optimize-autoloader
   ```

**Option B: Ask Hosting Provider**
Ask them to run:
```bash
cd /home2/auswebl6/quizsnap
composer install --no-dev --optimize-autoloader
```

**Option C: Upload vendor locally**
If you ran `composer install` on your computer, include the `vendor/` folder when uploading (it may be large).

## 6. Run Migrations

Visit (after site is live):
```
https://quizsnap.ausweblabs.com/migrate-all.php?key=QuizSnapMigrations2026&run=yes
```

Or use cPanel Terminal:
```bash
cd ~/quizsnap
php artisan migrate --force
```

## 7. Create Admin Account

If needed:
```
https://quizsnap.ausweblabs.com/run-seed-admin.php
```

## 8. Verify .htaccess

Ensure `public/.htaccess` exists and contains:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## 9. Clean Up (Security)

After deployment, delete these files from `public/`:
- migrate-all.php
- run-seed-admin.php
- Any other one-time scripts

Or restrict access via .htaccess if you need them occasionally.

---

## DOCX Export (PhpWord)

The DOCX export requires PhpWord. After `composer install` runs on the server, PhpWord will be installed automatically (it's in composer.json).

If DOCX export shows "PhpWord not installed", ensure `composer install` was run on the server so the `vendor/` folder includes PhpWord.
