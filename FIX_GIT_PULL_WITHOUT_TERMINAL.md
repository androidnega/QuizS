# Fix "local changes would be overwritten by merge" without cPanel terminal

When cPanel Git Update fails with:
```text
error: Your local changes to the following files would be overwritten by merge:
    .env.example
Please commit your changes or stash them before you merge. Aborting
```

and you **cannot use cPanel Terminal**, use the one-time script **`public/fix-git-pull.php`**.

---

## Step 1: Get the script onto the server (you can’t pull yet)

Because pull is failing, you have to add the file manually:

1. **Download the file from GitHub**  
   Open:  
   `https://github.com/androidnega/QuizS/blob/main/public/fix-git-pull.php`  
   Click **Raw**, then **Save as** (or copy the contents).

2. **Upload via cPanel File Manager**  
   - Log in to **cPanel → File Manager**.  
   - Go to your app folder (where the site lives).  
   - Open the **`public`** folder (often `public_html` or `domains/quizsnap.ausweblabs.com/public_html`).  
   - **Upload** the saved `fix-git-pull.php` into that `public` folder.

---

## Step 2: Set a secret in the file

1. In File Manager, **Edit** `public/fix-git-pull.php`.  
2. Find the line:  
   `$secret = 'CHANGE_ME_BEFORE_UPLOAD';`  
3. Change it to a secret only you know, e.g.:  
   `$secret = 'mySecretFixPull123';`  
4. Save.

---

## Step 3: Run the fix once in the browser

Visit (use your real domain and the same secret you set):

```text
https://quizsnap.ausweblabs.com/fix-git-pull.php?key=mySecretFixPull123&run=yes
```

Replace `mySecretFixPull123` with your secret.

The page will:

- Discard local changes to `.env.example` on the server  
- Run `git pull`  
- Show the command output  
- Delete itself when done

After that, **cPanel Git Update** should work again for normal pulls.

---

## If the script can’t run `git` (e.g. disabled by host)

Then the only option is to **remove the conflicting file on the server** so Git doesn’t see “local changes”:

1. In **cPanel File Manager**, go to your app root (where `.env` and `artisan` are).  
2. Find **`.env.example`**.  
3. **Rename** it to **`.env.example.bak`** (or delete it).  
   The live site uses **`.env`**, not `.env.example`; renaming/deleting is safe.  
4. In cPanel, run **Git → Update** again.  
   The pull should succeed and the repo’s `.env.example` will be restored.
