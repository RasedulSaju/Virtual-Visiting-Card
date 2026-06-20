# Installation Guide

## Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.0 | 8.2+ |
| MySQL | 5.7 | 8.0+ |
| Apache | 2.4 (mod_rewrite) | 2.4 |
| Nginx | 1.18 | Latest |
| Disk Space | 5 MB | — |
| PHP Extensions | PDO, pdo_mysql, mbstring, fileinfo, zip, curl | All |

---

## Option 1 — cPanel (Shared Hosting)

### Step 1 — Upload Files
1. Download `virtual-visiting-card.zip`
2. In **cPanel → File Manager**, navigate to `public_html/`
3. Upload the zip and extract it — rename the extracted folder to `vvcard` (or any name you prefer)

### Step 2 — Create Database
1. In **cPanel → MySQL Databases**:
   - Create a new database, e.g. `youruser_vvc`
   - Create a database user with a strong password
   - Add the user to the database with **All Privileges**

### Step 3 — Import Database
1. In **cPanel → phpMyAdmin**:
   - Select your new database from the left panel
   - Click **Import**
   - Choose `install.sql` from your project folder
   - Click **Go**

### Step 4 — Configure
Edit `vvcard/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'youruser_vvc');    // ← your database name
define('DB_USER', 'youruser_dbuser'); // ← your database user
define('DB_PASS', 'strongpassword');  // ← your database password
```

> `BASE_URL` is auto-detected — no manual configuration needed.

### Step 5 — Create Admin Account
1. Visit `https://yourdomain.com/vvcard/setup.php`
2. Fill in username, email, and password
3. Click **Create Admin Account**
4. **Delete `setup.php` immediately** after success

### Step 6 — Install PHPMailer (Optional — for email)
1. Visit `https://yourdomain.com/vvcard/install_phpmailer.php` (must be logged in as admin)
2. Click **Download & Install PHPMailer**
3. Delete `install_phpmailer.php` after success
4. Configure SMTP in **Admin → Settings → SMTP**

---

## Option 2 — XAMPP (Windows, Local Development)

### Step 1 — Place Files
Copy the project folder into `C:\xampp\htdocs\vvcard\`

### Step 2 — Create Database
1. Open `http://localhost/phpmyadmin`
2. Create a new database: `vvc_db` (charset: `utf8mb4_unicode_ci`)
3. Select the database, click **Import**, choose `install.sql`

### Step 3 — Configure
Edit `vvcard/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vvc_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP has no password
```

### Step 4 — Enable mod_rewrite
1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find and uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Find `<Directory "C:/xampp/htdocs">` and change `AllowOverride None` to `AllowOverride All`
4. Restart Apache

### Step 5 — Create Admin Account
Visit `http://localhost/vvcard/setup.php` and create your admin account.

### Step 6 — Install PHPMailer (Optional)
```bat
cd C:\xampp\htdocs\vvcard
install.bat
```
Or visit `http://localhost/vvcard/install_phpmailer.php`

---

## Option 3 — Linux VPS / Dedicated Server (SSH)

### Step 1 — Upload Files
```bash
# Via SCP
scp virtual-visiting-card.zip user@yourserver.com:/var/www/html/

# On the server
cd /var/www/html
unzip virtual-visiting-card.zip
mv cms vvcard
```

### Step 2 — Set Permissions
```bash
chmod 755 /var/www/html/vvcard
chmod 755 /var/www/html/vvcard/uploads/profiles
chown -R www-data:www-data /var/www/html/vvcard
```

### Step 3 — Create Database
```bash
mysql -u root -p
```
```sql
CREATE DATABASE vvc_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vvc_user'@'localhost' IDENTIFIED BY 'strongpassword';
GRANT ALL PRIVILEGES ON vvc_db.* TO 'vvc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u vvc_user -p vvc_db < /var/www/html/vvcard/install.sql
```

### Step 4 — Configure
```bash
nano /var/www/html/vvcard/config.php
```

### Step 5 — Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/vvcard

    <Directory /var/www/html/vvcard>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Step 6 — Nginx (alternative)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/vvcard;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. { deny all; }
    location ~* \.(sql|env|log)$ { deny all; }
}
```

### Step 7 — Install PHPMailer
```bash
cd /var/www/html/vvcard
bash install.sh
```

---

## Post-Installation Checklist

```
☐ setup.php deleted
☐ install_phpmailer.php deleted (if used)
☐ APP_DEBUG set to false in app-defaults.php (production)
☐ uploads/profiles/ is writable (chmod 755)
☐ Site name set in Admin → Settings → General
☐ SMTP configured in Admin → Settings → SMTP (if using email)
☐ Analytics connected in Admin → Settings → Analytics
☐ SEO settings reviewed in Admin → Settings → SEO
```

---

## Directory Permissions Reference

| Path | Required Permission | Notes |
|---|---|---|
| `uploads/profiles/` | `755` (writable) | User avatar storage |
| All PHP files | `644` | Read-only for web server |
| `config.php` | `600` | Extra protection — owner only |
| `.htaccess` | `644` | Apache config |
