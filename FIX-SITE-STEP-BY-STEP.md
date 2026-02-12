# How to Fix QuizSnap Site – Step by Step

Follow these steps in order. Do not skip steps.

---

## STEP 1: Get the Clean Files

**Option A – Use the zip (easiest)**  
1. On your Mac, open **Finder**.  
2. Press **Cmd + Shift + G** (Go to Folder).  
3. Paste: `/Applications/XAMPP/xamppfiles/htdocs/`  
4. Press **Enter**.  
5. Find **QuizSnap-clean-deploy.zip**.  
6. Copy this file to your Desktop (or somewhere easy to find).  
7. You will upload this zip to cPanel in Step 3.

**Option B – Download from GitHub**  
1. Open: https://github.com/androidnega/QuizS  
2. Click the green **Code** button.  
3. Click **Download ZIP**.  
4. Save and extract the ZIP.  
5. If you use this option, you must run **composer** on the server later (Step 7).

---

## STEP 2: Log Into cPanel

1. Open your browser.  
2. Go to your hosting login (e.g. `https://ausweblabs.com:2083` or your host’s cPanel URL).  
3. Log in with your cPanel username and password.

---

## STEP 3: Remove Old Site and Upload New One

### 3a. Back up (optional but recommended)

1. In cPanel, open **File Manager**.  
2. Go to the folder where QuizSnap is (e.g. `home2/auswebl6/`).  
3. If you see a folder named **quizsnap** (or similar), right‑click it.  
4. Choose **Compress** → create a **.zip** backup.  
5. Rename the zip to something like `quizsnap-backup-2026.zip`.  
6. You can leave it there or download it to your computer.

### 3b. Delete the old quizsnap folder

1. Still in File Manager, go to the same folder (e.g. `home2/auswebl6/`).  
2. Find the **quizsnap** folder (the one that’s giving 403 or errors).  
3. Right‑click **quizsnap** → **Delete**.  
4. Confirm deletion.

### 3c. Upload the clean zip

1. Stay in the same folder (e.g. `home2/auswebl6/`).  
2. Click **Upload**.  
3. Select **QuizSnap-clean-deploy.zip** from your computer (from Step 1).  
4. Wait until the upload finishes (may take a few minutes).

### 3d. Extract the zip

1. In File Manager, find **QuizSnap-clean-deploy.zip**.  
2. Right‑click it → **Extract**.  
3. Extract into the current folder (e.g. `home2/auswebl6/`).  
4. After extraction you should see a folder named **QuizSnap**.  
5. (Optional) Rename **QuizSnap** to **quizsnap** if you want the URL to stay the same.  
6. Delete **QuizSnap-clean-deploy.zip** after extraction to save space.

---

## STEP 4: Set the Document Root

1. In cPanel, open **Domains** (or **Domains** → **Domains** / **Subdomains**).  
2. Find **quizsnap.ausweblabs.com** (or your domain).  
3. Click **Manage** or **Edit**.  
4. Find **Document Root** (or “Root Domain”).  
5. Set it to the **public** folder inside your project, for example:  
   - `quizsnap/public`  
   - or `public_html/quizsnap/public`  
   (Exact path depends on your host; it must end with `/public`.)  
6. Save.

---

## STEP 5: Create the .env File

1. In File Manager, go inside your project folder (e.g. **quizsnap**).  
2. Look for **env.example.dist** (or **.env.example**).  
3. Right‑click it → **Copy**.  
4. Paste in the same folder.  
5. Rename the copy to **.env** (exactly, with the dot at the start).  
6. Right‑click **.env** → **Edit**.  
7. Set at least these (use your real values):

   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://quizsnap.ausweblabs.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

8. If there is an **APP_KEY=** line, leave it; if empty, you can generate one later.  
9. Save and close.

---

## STEP 6: Set Folder Permissions

1. In File Manager, go inside your project folder (e.g. **quizsnap**).  
2. Right‑click the **storage** folder → **Change Permissions**.  
3. Set to **755** (or check Read, Write, Execute for Owner and Group, Read + Execute for World).  
4. Check **“Recurse into subdirectories”** (if available).  
5. Click **Change Permissions**.  
6. Do the same for **bootstrap/cache**: right‑click → Change Permissions → **755** → Recurse → OK.

---

## STEP 7: Install Dependencies (Only If You Used GitHub ZIP)

Do this **only** if you did **not** use **QuizSnap-clean-deploy.zip** (i.e. you used the GitHub ZIP).

1. In cPanel, open **Terminal** (or **SSH Access**).  
2. Run:

   ```bash
   cd ~/quizsnap
   composer install --no-dev --optimize-autoloader
   ```

3. If it says “composer: command not found”, ask your host to run the same command for you, or use the **QuizSnap-clean-deploy.zip** from Step 1 instead (it already has `vendor/`).

---

## STEP 8: Run Migrations

1. Open your browser.  
2. Go to (use your real domain and the correct key if you changed it):

   ```
   https://quizsnap.ausweblabs.com/migrate-all.php?key=QuizSnapMigrations2026&run=yes
   ```

3. You should see a success message.  
4. If you get 404, check that the document root is really **quizsnap/public** (Step 4).

---

## STEP 9: Test the Site

1. Visit: **https://quizsnap.ausweblabs.com**  
2. You should see the QuizSnap landing page (no 403).  
3. Try the login page and log in.  
4. If anything still shows 403, go to Step 10.

---

## STEP 10: If You Still Get 403

1. **Check .htaccess**  
   - In File Manager go to **quizsnap/public/**.  
   - Open **.htaccess** (you may need to enable “Show Hidden Files”).  
   - It should contain **RewriteEngine On** and the **RewriteRule ^ index.php [L]** block.  
   - If the file is missing or looks wrong, say so and we can restore the correct content.

2. **Check document root again**  
   - It must point to the **public** folder (e.g. `quizsnap/public`), not `quizsnap` alone.

3. **Ask your host**  
   - “Please ensure mod_rewrite is enabled for my account.”  
   - “My Laravel app in quizsnap/public returns 403 – can you check permissions and document root?”

---

## Quick Checklist

- [ ] Old quizsnap folder deleted  
- [ ] Clean zip uploaded and extracted  
- [ ] Document root = `quizsnap/public`  
- [ ] `.env` created and database settings filled  
- [ ] `storage/` and `bootstrap/cache/` set to 755  
- [ ] Migrations run (`migrate-all.php?key=...&run=yes`)  
- [ ] Site opens without 403  
- [ ] Login works  

If you tell me which step you’re on and what you see (e.g. “403 on login”, “migrate-all.php not found”), I can give you the exact fix for that step.
