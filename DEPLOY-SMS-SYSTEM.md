# Deploy SMS Allocation System - Step by Step

## What You Need to Do on Your Live Server

### Step 1: Pull Latest Code from Git
1. Go to **cPanel → Git Version Control**
2. Click **Pull** or **Update** to get the latest code
3. Verify it pulled successfully (should show latest commits including SMS features)

### Step 2: Run Database Migration
The migration adds `sms_allocation` and `sms_used` columns to the `users` table.

**Option A: Using run-migrate.php (Recommended)**
1. Make sure `public/run-migrate.php` exists (it should after pulling)
2. Visit: `https://YOUR-DOMAIN.com/run-migrate.php?key=QuizSnap2026Xk9m2p7&run=yes`
3. The script will:
   - Run all pending migrations (including SMS columns)
   - Clear all caches
   - Delete itself after completion

**Option B: Using cPanel Terminal (if you have SSH access)**
```bash
cd /home2/auswebl6/quizsnap
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### Step 3: Verify Migration Ran
After running the migration, check:
1. Go to **cPanel → phpMyAdmin** (or your database tool)
2. Open the `users` table
3. Check if these columns exist:
   - `sms_allocation` (unsigned integer, default 0)
   - `sms_used` (unsigned integer, default 0)

### Step 4: Set SMS Allocation for Examiners
1. Log in as **Super Admin**
2. Go to **Dashboard → Users**
3. Click **Edit** on an examiner account
4. You'll see a new field: **"SMS allocation (for Examiner)"**
5. Enter a number (e.g., 20, 50, 100)
6. Click **Update user**

### Step 5: Verify SMS Display
1. Log in as an **Examiner** (with SMS allocation set)
2. Look at the **top right** of the dashboard header
3. You should see: **SMS: X / Y** (remaining / total)
   - Example: `SMS: 20 / 20` if allocation is 20 and none used

## Troubleshooting

### If SMS balance doesn't show:
- ✅ Check that you ran the migration (Step 2)
- ✅ Check that the examiner has `sms_allocation > 0` in the database
- ✅ Clear browser cache and refresh
- ✅ Check browser console for JavaScript errors

### If migration fails:
- Check database connection in `.env`
- Verify you have write permissions on the database
- Check Laravel logs: `storage/logs/laravel.log`

### If you see "We're unable to send your login code":
- This means the examiner's `sms_remaining` is 0
- Super Admin needs to increase `sms_allocation` for that examiner
- Or the examiner has used all their allocated SMS

## What Changed

1. **Database**: Added `sms_allocation` and `sms_used` to `users` table
2. **Admin UI**: SMS allocation field in User edit/create forms
3. **Examiner Header**: SMS balance display (remaining / total)
4. **Upload Flow**: Sends SMS to students when index numbers are uploaded
5. **Login Flow**: Checks SMS balance before sending OTP codes
6. **OTP Expiry**: Changed from 24 hours to 14 days

## Files Modified
- `database/migrations/2026_02_12_000001_add_sms_allocation_to_users_table.php` (NEW)
- `app/Models/User.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Controllers/Admin/ClassGroupController.php`
- `app/Http/Controllers/Student/StudentLoginController.php`
- `app/Http/Controllers/Student/StudentAccountController.php`
- `resources/views/layouts/examiner.blade.php`
- `resources/views/layouts/dashboard.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/create.blade.php`
- `config/quizsnap.php`
