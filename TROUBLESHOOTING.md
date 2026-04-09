# 🚨 TROUBLESHOOTING GUIDE

## Issue: Website Not Opening / "This site can't be reached"

### ✅ Quick Checklist:

1. **Is XAMPP running?**
   - Open XAMPP Control Panel
   - Make sure **Apache** status is GREEN ✅
   - Make sure **MySQL** status is GREEN ✅
   - If not, click "Start" buttons

2. **Is the project in the correct location?**

   ```
   Correct location: C:\xampp\htdocs\brain-rent\

   Check that these files exist:
   - C:\xampp\htdocs\brain-rent\index.php ✅
   - C:\xampp\htdocs\brain-rent\pages\index.php ✅
   - C:\xampp\htdocs\brain-rent\setup.php ✅
   ```

3. **Use the correct URL:**
   ```
   ✅ CORRECT: http://localhost/brain-rent/
   ❌ WRONG: C:\xampp\htdocs\brain-rent\
   ❌ WRONG: file:///C:/xampp/htdocs/brain-rent/
   ```

---

## Issue: "Database connection failed"

### Solution:

1. **Run the automatic setup:**
   - Go to: `http://localhost/brain-rent/setup.php`
   - Click "Run Automatic Setup"
   - Wait for green checkmarks ✅

2. **Manual database setup (if automatic fails):**
   - Go to: `http://localhost/phpmyadmin`
   - Click "New" in left sidebar
   - Database name: `brain_rent`
   - Click "Create"
   - Click on `brain_rent` database
   - Click "SQL" tab
   - Copy/paste contents of `database/brain_rent_mysql.sql`
   - Click "Go"
   - **IMPORTANT:** Repeat with `database/add_new_features.sql`

---

## Issue: Still seeing old "Simple. Async. Powerful" text

### Solution:

**Clear your browser cache:**

- Press `Ctrl + Shift + Delete`
- Select "Cached images and files"
- Click "Clear data"
- Refresh page with `Ctrl + F5`

**OR use Incognito Mode:**

- Press `Ctrl + Shift + N` (Chrome)
- Go to `http://localhost/brain-rent/`

---

## Issue: Features not showing / "Table doesn't exist"

### Solution:

You forgot to import the NEW features SQL file!

1. Go to: `http://localhost/phpmyadmin`
2. Select `brain_rent` database
3. Click "SQL" tab
4. Open file: `database/add_new_features.sql`
5. Copy ALL content
6. Paste into SQL box
7. Click "Go"

---

## Issue: Upload fails / "Failed to upload file"

### Solution:

1. **Check folders exist:**

   ```
   C:\xampp\htdocs\brain-rent\uploads\ebooks\
   C:\xampp\htdocs\brain-rent\uploads\notes\
   C:\xampp\htdocs\brain-rent\uploads\videos\
   C:\xampp\htdocs\brain-rent\uploads\thumbnails\
   ```

2. **Increase upload limits:**
   - Open `C:\xampp\php\php.ini`
   - Find and change:
     ```ini
     upload_max_filesize = 500M
     post_max_size = 500M
     max_execution_time = 300
     ```
   - Save file
   - **Restart Apache** in XAMPP

---

## Issue: "404 Not Found"

### Solution:

1. **Check Apache is running** in XAMPP
2. **Verify project location:**
   - Must be in: `C:\xampp\htdocs\brain-rent\`
   - NOT in: Desktop, Downloads, or other folders
3. **Check URL is correct:**
   - Use: `http://localhost/brain-rent/`

---

## Issue: Login/Register not working

### Solution:

1. **Make sure database is set up:**
   - Run: `http://localhost/brain-rent/setup.php`
   - Check: `http://localhost/phpmyadmin` shows `brain_rent` database

2. **Check `users` table exists:**
   - Go to phpMyAdmin
   - Click `brain_rent` database
   - Look for `users` table in list

---

## 🎯 Step-by-Step Setup (Start Fresh)

If nothing works, follow these steps exactly:

### 1. Place Project

```
Move brain-rent folder to: C:\xampp\htdocs\
Final path: C:\xampp\htdocs\brain-rent\
```

### 2. Start Services

- Open XAMPP Control Panel
- Click "Start" for Apache
- Click "Start" for MySQL
- Wait for GREEN status

### 3. Run Setup

- Open browser
- Type: `http://localhost/brain-rent/setup.php`
- Press Enter
- Click "Run Automatic Setup"
- Wait for success message

### 4. Create Account

- Click "Create Your Account" button
- Or go to: `http://localhost/brain-rent/pages/register.php`
- Fill form and submit

### 5. Test Features

- Library: `http://localhost/brain-rent/pages/libraries.php`
- Notes: `http://localhost/brain-rent/pages/notes.php`
- Videos: `http://localhost/brain-rent/pages/problem-solving.php`

---

## 📱 Quick Access URLs

Copy and paste these into your browser:

**Setup & Access:**

- Setup Tool: `http://localhost/brain-rent/setup.php`
- Homepage: `http://localhost/brain-rent/`
- Register: `http://localhost/brain-rent/pages/register.php`
- Login: `http://localhost/brain-rent/pages/login.php`

**Features:**

- Library: `http://localhost/brain-rent/pages/libraries.php`
- Notes: `http://localhost/brain-rent/pages/notes.php`
- Videos: `http://localhost/brain-rent/pages/problem-solving.php`
- Experts: `http://localhost/brain-rent/pages/browse.php`

**Admin:**

- phpMyAdmin: `http://localhost/phpmyadmin`
- XAMPP Dashboard: `http://localhost/dashboard`

---

## 🔍 Check Apache/MySQL Logs

If still having issues:

**Apache Error Log:**

```
C:\xampp\apache\logs\error.log
```

**PHP Error Log:**

```
C:\xampp\php\logs\php_error_log
```

**MySQL Error Log:**

```
C:\xampp\mysql\data\mysql_error.log
```

---

## ⚡ Port Conflicts

If Apache won't start:

### Check if port 80 is used:

1. Open Command Prompt as Administrator
2. Run: `netstat -ano | findstr :80`
3. If something is using port 80, you need to stop it

### Common culprits:

- Skype
- IIS (Internet Information Services)
- Other web servers

### Solution:

- Close the program using port 80
- OR change Apache port in XAMPP
- OR use port forwarding

---

## 💡 Still Not Working?

1. **Restart Everything:**
   - Close XAMPP completely
   - Restart computer
   - Start XAMPP again
   - Try accessing website

2. **Check Windows Firewall:**
   - Allow Apache through firewall
   - Allow MySQL through firewall

3. **Try Different Browser:**
   - Chrome, Firefox, Edge
   - Try Incognito/Private mode

---

## ✅ Verify Everything Is Working

Run this checklist:

- [ ] Apache is GREEN in XAMPP
- [ ] MySQL is GREEN in XAMPP
- [ ] `http://localhost/` opens XAMPP dashboard
- [ ] `http://localhost/phpmyadmin` opens
- [ ] `brain_rent` database exists in phpMyAdmin
- [ ] Both SQL files imported successfully
- [ ] `http://localhost/brain-rent/` shows homepage
- [ ] Can register new account
- [ ] Can login
- [ ] Can access Library/Notes/Videos pages

If all checked ✅, your setup is perfect!

---

**Need More Help?**
Check the full documentation in `README.md` 📖
