# BrainRent - Complete Setup Guide

## 📌 Project Overview

BrainRent is a comprehensive learning and expert consultation platform featuring:

- 📚 **Digital Library** - Upload and share e-books
- 📝 **Study Notes** - Share and download notes
- 🎥 **Problem Solving Videos** - Tutorial videos with comments
- 🧠 **Expert Consultation** - Connect with verified experts

---

## 🚀 Quick Start (Localhost Setup)

### Step 1: Prerequisites

Make sure you have installed:

- **XAMPP** (or WAMP/MAMP) with PHP 7.4+ and MySQL
- A web browser (Chrome, Firefox, etc.)

### Step 2: Project Setup

1. **Place the project in XAMPP's htdocs folder:**

   ```
   C:\xampp\htdocs\brain-rent\
   ```

2. **Your folder structure should look like:**
   ```
   C:\xampp\htdocs\brain-rent\
   ├── index.php
   ├── pages/
   ├── api/
   ├── config/
   ├── database/
   ├── assets/
   ├── uploads/
   └── includes/
   ```

### Step 3: Start XAMPP

1. Open **XAMPP Control Panel**
2. Start **Apache** (for PHP)
3. Start **MySQL** (for database)

### Step 4: Create Database

**Option A: Using phpMyAdmin (Recommended)**

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar
3. Database name: `brain_rent`
4. Collation: `utf8mb4_unicode_ci`
5. Click **"Create"**
6. Click on `brain_rent` database in the left sidebar
7. Click **"SQL"** tab at the top
8. Open `C:\xampp\htdocs\brain-rent\database\brain_rent_mysql.sql` in a text editor
9. Copy ALL the content and paste it into the SQL textarea
10. Click **"Go"** button at the bottom
11. **IMPORTANT:** Repeat steps 7-10 with `database/add_new_features.sql` file

**Option B: Using Command Line**

```bash
# Open Command Prompt
cd C:\xampp\mysql\bin

# Login to MySQL
mysql -u root -p

# Create database and import
CREATE DATABASE brain_rent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE brain_rent;
SOURCE C:/xampp/htdocs/brain-rent/database/brain_rent_mysql.sql;
SOURCE C:/xampp/htdocs/brain-rent/database/add_new_features.sql;
```

### Step 5: Configure Database Connection

The project should work out-of-the-box with default XAMPP settings. If you changed MySQL password:

1. Open `config/db.local.php`
2. Update your password:
   ```php
   <?php
   define('DB_PASSWORD', 'your_password_here');
   ```

### Step 6: Set Upload Permissions

The upload directories should already exist. Verify they're present:

```
uploads/
├── ebooks/
├── notes/
├── videos/
└── thumbnails/
```

### Step 7: Access the Website

Open your browser and go to:

**Main Access Points:**

- **Homepage:** `http://localhost/brain-rent/`
- **Direct:** `http://localhost/brain-rent/pages/index.php`
- **Library:** `http://localhost/brain-rent/pages/libraries.php`
- **Notes:** `http://localhost/brain-rent/pages/notes.php`
- **Videos:** `http://localhost/brain-rent/pages/problem-solving.php`
- **Login:** `http://localhost/brain-rent/pages/login.php`
- **Register:** `http://localhost/brain-rent/pages/register.php`

### Step 8: Create Your First Account

1. Go to: `http://localhost/brain-rent/pages/register.php`
2. Fill in your details:
   - Full Name
   - Email Address
   - Password (minimum 6 characters)
   - Account Type: Choose one:
     - **Learn & Access Resources** - Student/learner account
     - **Become an Expert/Tutor** - Expert account
     - **Both** - Full access
3. Click **"Create Account"**
4. You'll be automatically logged in

---

## 🎯 Features Guide

### 1. Digital Library (E-Books)

**Upload E-Books:**

- Login → Click "Library" → Click "Upload E-Book"
- Supported formats: PDF, EPUB, MOBI
- Maximum size: 50MB
- Optional cover image

**Browse & Download:**

- Search by title, author, or keywords
- Filter by category
- Click any book to view details
- Download or view online

### 2. Study Notes

**Upload Notes:**

- Login → Click "Notes" → Click "Upload Notes"
- Supported formats: PDF, DOC, DOCX, TXT, PPT, PPTX
- Maximum size: 25MB
- Categorize by subject

**Access Notes:**

- Search notes by title or subject
- Filter by subject and category
- Download or view in browser

### 3. Problem Solving Videos

**Upload Videos:**

- Login → Click "Problem Solving" → Click "Upload Video"
- Supported formats: MP4, WEBM, AVI, MOV, MKV
- Maximum size: 500MB
- Add thumbnail image (optional)
- Specify difficulty level

**Watch Videos:**

- Browse by problem type and difficulty
- Watch with built-in video player
- Leave comments
- View related videos

### 4. Expert Consultation

**For Students:**

- Browse expert profiles
- Submit problems
- Get voice + written responses
- Rate experts

**For Experts:**

- Create expert profile
- Set your rates
- Respond to requests
- Earn money

---

## ⚙️ Configuration

### Increase Upload Limits (Optional)

If you need to upload larger files:

1. Find your `php.ini` file:
   - **XAMPP:** `C:\xampp\php\php.ini`

2. Edit these values:

   ```ini
   upload_max_filesize = 500M
   post_max_size = 500M
   max_execution_time = 300
   memory_limit = 512M
   ```

3. Restart Apache in XAMPP Control Panel

### Custom Domain (Optional)

To use `http://brainrent.local` instead of localhost:

1. Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator)
2. Add line: `127.0.0.1 brainrent.local`
3. Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
4. Add:
   ```apache
   <VirtualHost *:80>
       ServerName brainrent.local
       DocumentRoot "C:/xampp/htdocs/brain-rent"
       <Directory "C:/xampp/htdocs/brain-rent">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
5. Restart Apache

---

## 🔧 Troubleshooting

### Issue: "Database connection failed"

**Solutions:**

1. Make sure MySQL is running in XAMPP
2. Check if database `brain_rent` exists
3. Verify credentials in `config/db.local.php`
4. Run both SQL files (brain_rent_mysql.sql AND add_new_features.sql)

### Issue: "Page not found" or 404 Error

**Solutions:**

1. Check the URL is correct: `http://localhost/brain-rent/`
2. Verify Apache is running in XAMPP
3. Make sure project is in `C:\xampp\htdocs\brain-rent\`
4. Clear browser cache

### Issue: "Failed to upload file"

**Solutions:**

1. Check upload directories exist:
   - `uploads/ebooks/`
   - `uploads/notes/`
   - `uploads/videos/`
   - `uploads/thumbnails/`
2. Check PHP upload limits in `php.ini`
3. Restart Apache after changing php.ini

### Issue: Tables missing or errors

**Solutions:**

1. Make sure you imported BOTH SQL files:
   - `database/brain_rent_mysql.sql` (main tables)
   - `database/add_new_features.sql` (new feature tables)
2. Check phpMyAdmin to verify tables exist

### Issue: "Call to undefined function"

**Solutions:**

1. Make sure PHP extensions are enabled in php.ini:
   - `extension=mysqli`
   - `extension=pdo_mysql`
2. Restart Apache

---

## 📁 Project Structure

```
brain-rent/
├── index.php                    # Root redirect
├── pages/                       # All page views
│   ├── index.php               # Homepage
│   ├── login.php               # Login page
│   ├── register.php            # Registration
│   ├── libraries.php           # E-books library
│   ├── upload-ebook.php        # Upload e-book
│   ├── notes.php               # Study notes
│   ├── upload-notes.php        # Upload notes
│   ├── problem-solving.php     # Videos list
│   ├── upload-video.php        # Upload video
│   ├── video-detail.php        # Video player
│   ├── browse.php              # Browse experts
│   ├── expert-profile.php      # Expert details
│   ├── dashboard-client.php    # Client dashboard
│   ├── dashboard-expert.php    # Expert dashboard
│   └── profile.php             # User profile
├── api/                         # API endpoints
│   ├── download-note.php       # Download notes
│   ├── view-note.php           # View notes
│   ├── dashboard.php           # Dashboard data
│   └── ...                     # Other APIs
├── config/                      # Configuration
│   ├── db.php                  # Database config
│   ├── db.local.php            # Local overrides
│   └── auth.php                # Authentication
├── database/                    # SQL files
│   ├── brain_rent_mysql.sql    # Main schema
│   ├── add_new_features.sql    # New features
│   └── setup_database.php      # Setup script
├── assets/                      # Static assets
│   ├── css/
│   │   └── custom.css          # Custom styles
│   └── js/
│       └── main.js             # JavaScript
├── includes/                    # Reusable components
│   ├── header.php              # Site header
│   └── footer.php              # Site footer
└── uploads/                     # User uploads
    ├── ebooks/                 # E-book files
    ├── notes/                  # Note files
    ├── videos/                 # Video files
    └── thumbnails/             # Cover images
```

---

## 🎓 Default Test Data

After running the SQL files, you'll have:

- 10 pre-defined categories
- Sample database structure

To add test content:

1. Register an account
2. Upload some e-books, notes, and videos
3. Create an expert profile (if you selected expert account type)

---

## 🔒 Security Notes

- Change default database password in production
- Never commit `db.local.php` to version control
- Enable HTTPS in production
- Implement CSRF protection for forms
- Validate all file uploads
- Scan uploaded files for viruses in production

---

## 📞 Support

If you encounter issues:

1. **Check Apache/PHP error logs:**
   - `C:\xampp\apache\logs\error.log`
   - `C:\xampp\php\logs\php_error_log`

2. **Check MySQL is running:**
   - Open XAMPP Control Panel
   - MySQL status should be green/running

3. **Verify database exists:**
   - Go to `http://localhost/phpmyadmin`
   - Check if `brain_rent` database is listed

4. **Clear browser cache:**
   - Press Ctrl+Shift+Delete
   - Clear cache and reload page

---

## ✅ System Requirements

**Minimum:**

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2+
- Apache 2.4+
- 100MB free disk space

**Recommended:**

- PHP 8.0+
- MySQL 8.0+ / MariaDB 10.5+
- 1GB free disk space (for uploads)
- Modern web browser

---

## 🎉 You're All Set!

Visit `http://localhost/brain-rent/` and start exploring!

**First Steps:**

1. Register an account
2. Explore the library
3. Upload some content
4. Connect with experts

Enjoy your BrainRent platform! 🚀📚🎓
