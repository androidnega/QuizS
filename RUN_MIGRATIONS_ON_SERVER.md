# Run migrations on the server (when SSH/Terminal is not working)

Two options:

---

## Option 1: Run migrations via URL (one-time script on the server)

A file **`public/run-migrate.php`** runs migrations when you visit it with a secret key.

### Steps

1. **Set a secret key**  
   Open `public/run-migrate.php` and change the line:
   ```php
   $secret = 'CHANGE_ME_BEFORE_UPLOAD';
   ```
   to something only you know, e.g.:
   ```php
   $secret = 'a8f3k2m9x';  // use your own random string
   ```

2. **Deploy**  
   Push to Git and pull on the server (or upload `public/run-migrate.php` to the server’s `public/` folder).

3. **Run migrations**  
   In your browser, visit:
   ```
   https://quizsnap.ausweblabs.com/run-migrate.php?key=YOUR_SECRET&run=yes
   ```
   Replace `YOUR_SECRET` with the same string you put in the file (e.g. `a8f3k2m9x`).

4. **One-time use**  
   The script runs migrations and then **deletes itself**. If it can’t delete itself, remove `public/run-migrate.php` manually after use.

**Security:** Use a long, random key and don’t share the URL. After running, the file is gone (or delete it yourself).

---

## Option 2: Export SQL locally and import in phpMyAdmin on the server

Use this if Option 1 fails (e.g. host blocks `Artisan::call`) or you prefer not to run code via URL.

### Steps

1. **Run migrations locally (XAMPP MySQL)**  
   In `.env` set:
   ```
   DB_CONNECTION=mysql
   DB_DATABASE=quizsnap_local
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   Create the database `quizsnap_local` in phpMyAdmin, then in Terminal:
   ```bash
   /Applications/XAMPP/xamppfiles/bin/php artisan migrate --force
   ```

2. **Dump schema to SQL**  
   In Terminal (project folder):
   ```bash
   /Applications/XAMPP/xamppfiles/bin/php artisan schema:dump-sql --file=schema.sql
   ```
   This creates **`schema.sql`** in the project root.

3. **Import on the server**  
   - Log in to **cPanel → phpMyAdmin**.  
   - Select your **production database**.  
   - Open the **Import** tab.  
   - Choose file: **`schema.sql`** from your computer.  
   - Click **Go**.

All tables are created on the server. **`schema.sql`** is in `.gitignore`; use it only for this import.

---

## After migrations (admin user)

The `users` table will be empty. Create an admin user:

- **If you get terminal/SSH later:**  
  Set `ADMIN_USERNAME` and `ADMIN_PASSWORD` in production `.env`, then run on the server:
  ```bash
  php artisan db:seed --force
  ```
- **Otherwise:**  
  Insert a user manually in phpMyAdmin (password must be hashed with Laravel’s `Hash::make()`; you can generate one locally with `php artisan tinker` → `Hash::make('yourpassword')`).
