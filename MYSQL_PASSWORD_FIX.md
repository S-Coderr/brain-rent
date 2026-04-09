# Quick Fix: XAMPP MariaDB Password Issue

If you see `{"success":false,"error":"Database connection failed"}` or `Access denied for user 'root'@'localhost'`, it means your XAMPP MariaDB root user has a password set (or access is restricted).

## Option 1: If you know your root password

- Create or update `config/db.local.php` with your MySQL password:
  ```php
  <?php
  define('DB_USER', 'root');
  define('DB_PASSWORD', 'your_password');
  ```
- Then run: `php database/setup_database.php`

## Option 2: Reset root password (when you forgot it)

This uses a standard MariaDB recovery mode for LOCAL development.

1. Open XAMPP Control Panel
2. Stop **MySQL**
3. Click **Config** next to MySQL → **my.ini**
4. Under `[mysqld]`, add:
   ```
   skip-grant-tables
   ```
5. Save and Start **MySQL** again
6. Open a terminal and run:
   ```bash
   mysql -u root
   ```
7. Set a new password (example):
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'myNewPassword';
   FLUSH PRIVILEGES;
   ```
8. Exit MySQL, remove `skip-grant-tables` from `my.ini`
9. Restart **MySQL**
10. Update `config/db.local.php` with the new password and run: `php database/setup_database.php`

Security note: Do not leave `skip-grant-tables` enabled.
