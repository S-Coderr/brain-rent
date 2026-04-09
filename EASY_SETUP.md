# 🚀 EASY SETUP (XAMPP only — no phpMyAdmin needed)

This project includes a CLI setup script that creates/imports the MySQL database for you.

## Step 1: Start XAMPP

1. Open **XAMPP Control Panel**
2. Start **Apache** and **MySQL**

## Step 2: Fix MySQL credentials (only if needed)

If you get `Access denied for user 'root'@'localhost'` when running setup, follow [MYSQL_PASSWORD_FIX.md](MYSQL_PASSWORD_FIX.md).

## Step 3: Create the database (CLI)

From the `brain-rent` folder, run:

```bash
php database/setup_database.php
```

If it succeeds, the `brain_rent` database + tables will be created.

## Step 4: Configure Apache to serve the project

### Option A: Copy to htdocs (Recommended)

1. Copy the entire `brain-rent` folder to: `C:\xampp\htdocs\`
2. Open browser: **http://localhost/brain-rent/pages/index.php**

### Option B: Create Virtual Host

1. Edit: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Add:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/Users/Saurav Kumar/OneDrive/Desktop/php/brain-rent-project/brain-rent"
    ServerName brainrent.local
    <Directory "C:/Users/Saurav Kumar/OneDrive/Desktop/php/brain-rent-project/brain-rent">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Edit: `C:\Windows\System32\drivers\etc\hosts` (as Administrator)
4. Add: `127.0.0.1  brainrent.local`
5. Restart Apache
6. Visit: **http://brainrent.local/pages/index.php**

### Option C: PHP Built-in Server (Quick Test)

1. Update `config/db.php` line 6 to:
   ```php
   define('DB_SERVER',   '127.0.0.1');
   ```
2. Run from brain-rent directory:
   ```bash
   php -S localhost:8000
   ```
3. Visit: **http://localhost:8000/pages/index.php**

## Step 5: Test It!

Try opening the application - you should see the BrainRent landing page!

## Troubleshooting

**"Database connection failed":**

- Make sure MySQL is running in XAMPP
- Run: `php database/setup_database.php`
- If setup fails with Access Denied, use [MYSQL_PASSWORD_FIX.md](MYSQL_PASSWORD_FIX.md)

**"Page not found":**

- Make sure you're accessing the correct URL
- If using htdocs, the path should be: `http://localhost/brain-rent/pages/index.php`

**Still having issues?**

- Open phpMyAdmin and verify the `brain_rent` database exists
- Check that it has tables like `users`, `expert_profiles`, etc.
