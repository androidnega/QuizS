# SMS Allocation System - Quick Reference Guide

## ✅ System Status: DEPLOYED

All code and database migrations are complete. The SMS system is ready to use.

---

## 📋 Quick Setup Steps

### 1. Set SMS Allocation for Examiners

**As Super Admin:**
1. Go to **Dashboard → Users**
2. Click **Edit** on an examiner account
3. Find **"SMS allocation (for Examiner)"** field
4. Enter a number (e.g., `20`, `50`, `100`)
5. Click **Update user**

**Example:** Enter `20` = examiner gets 20 SMS credits to send login tokens to students.

---

### 2. View SMS Balance (Examiner)

**As Examiner:**
- Look at the **top right** of the dashboard header
- You'll see: **SMS: X / Y**
  - `X` = remaining SMS
  - `Y` = total allocation
- Example: `SMS: 20 / 20` means 20 remaining out of 20 total

---

### 3. How SMS Are Used

**When examiner uploads index numbers:**
1. Examiner goes to **Class Groups → [Select Group] → Students**
2. Uploads Excel/CSV with student index numbers
3. System automatically:
   - Generates 14-day login codes for each student
   - Sends SMS to students who have phone numbers
   - Deducts 1 SMS from examiner's balance per successful send
   - Shows message: "Login tokens sent by SMS to X student(s)"

**When student requests login code:**
1. Student enters index number
2. System checks if examiner has SMS balance
3. If yes: sends code, deducts 1 SMS
4. If no: shows friendly error message

---

## 🔧 Admin Functions

### Increase SMS Allocation
- **Dashboard → Users → Edit Examiner**
- Change the **SMS allocation** number
- Click **Update user**
- Examiner's balance increases immediately

### Check SMS Usage
- View examiner's SMS balance in the header (as examiner)
- Or check database: `users` table → `sms_allocation` and `sms_used` columns

### Reset SMS Usage (if needed)
- Edit examiner in database: set `sms_used = 0`
- Or increase `sms_allocation` to give more credits

---

## 📱 SMS Features

### Token Expiration
- **14 days** (configurable via `QUIZSNAP_OTP_TTL_SECONDS` in `.env`)
- Students can reuse the same code for 14 days
- After 14 days, they need a new code

### SMS Dependency
- **No SMS balance = No tokens sent**
- If examiner has 0 SMS:
  - Upload won't send any SMS
  - Students can't request login codes
  - Error: "We're unable to send your login code right now..."

### SMS Deduction
- **1 SMS per successful send**
- Only deducted when SMS is actually sent (not on failed attempts)
- Balance updates immediately

---

## 🎯 Common Scenarios

### Scenario 1: New Examiner
1. Super Admin creates examiner account
2. Sets SMS allocation (e.g., 50)
3. Examiner sees `SMS: 50 / 50` in header
4. Examiner uploads 30 students → `SMS: 20 / 50`
5. Students receive login codes via SMS

### Scenario 2: Examiner Runs Out
1. Examiner has `SMS: 0 / 50`
2. Tries to upload students → No SMS sent
3. Message: "No SMS balance—login tokens were not sent"
4. Super Admin increases allocation → `SMS: 50 / 100`
5. Now SMS sending works again

### Scenario 3: Student Login
1. Student enters index number
2. System checks examiner's SMS balance
3. If balance > 0: sends code, deducts 1 SMS
4. If balance = 0: shows error, no code sent

---

## 🔍 Troubleshooting

### SMS balance not showing?
- ✅ Check examiner is logged in (not Super Admin)
- ✅ Check `sms_allocation > 0` in database
- ✅ Clear browser cache
- ✅ Check browser console for errors

### SMS not being sent?
- ✅ Check examiner has `sms_remaining > 0`
- ✅ Check Arkesel API key is configured (Settings)
- ✅ Check student has valid phone number
- ✅ Check SMS service is working (test in Settings)

### Migration not run?
- Visit: `/run-migrate.php?key=QuizSnap2026Xk9m2p7&run=yes`
- Or check: `/check-sms-deployment.php`

---

## 📊 Database Structure

**users table:**
- `sms_allocation` (unsigned integer, default 0) - Total SMS credits
- `sms_used` (unsigned integer, default 0) - SMS already used
- `sms_remaining` (calculated: allocation - used) - Available SMS

---

## 🎉 You're All Set!

The SMS system is fully deployed and ready to use. Just set SMS allocations for your examiners and start uploading student lists!
