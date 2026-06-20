# Troubleshooting

---

## HTTP 500 — Internal Server Error

### Blank page or "500 Internal Server Error"

**Step 1 — Enable error display**

In `app-defaults.php`, set:
```php
define('APP_DEBUG', true);
```
Reload the page — the actual PHP error will now appear.

**Step 2 — Check Apache error log**

In cPanel: **Logs → Error Log**
On Linux: `tail -50 /var/log/apache2/error.log`

---

## Common Fatal Errors

### `Undefined constant "APP_DESCRIPTION"`
**Cause:** Your local `config.php` predates the `app-defaults.php` split — it still defines everything itself but is missing newer constants.

**Fix:** Replace `config.php` with the latest version from the zip, then re-enter your DB credentials (only the 4 `DB_*` lines at the top).

---

### `Undefined constant "SESSION_LIFETIME"`
Same cause as above.

**Fix:** Same fix — replace `config.php` and re-enter DB credentials. The new `config.php` only contains DB credentials + BASE_URL; everything else is now in `app-defaults.php` which updates automatically.

---

### `Call to undefined function siteName()`
**Cause:** A PHP file (commonly `setup.php`) includes `config.php` and `db.php` but not `helpers.php`, where `siteName()` is defined.

**Fix:** Already patched. Replace `setup.php` with the latest version.

---

### `Duplicate column name 'meta_robots'`
**Cause:** You imported `schema.sql` which already includes `meta_robots` in `CREATE TABLE`, then also ran `migration_006` which tried to `ALTER TABLE ADD COLUMN meta_robots` again.

**Fix:** Use `install.sql` for fresh installs — it merges everything correctly with no duplicate statements. If your database is in a partial state:
```sql
DROP DATABASE your_db;
CREATE DATABASE your_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Then import `install.sql`.

---

### `Access denied for user 'root'@'localhost'`
**Cause:** Wrong database credentials in `config.php`.

**Fix:** Verify `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in `config.php` match your database settings exactly. On cPanel, the database user must be added to the database with All Privileges.

---

### `SQLSTATE[42S02]: Base table or view not found`
**Cause:** Database not imported, or wrong database selected.

**Fix:** Import `install.sql` into the correct database. Verify `DB_NAME` in `config.php`.

---

### `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'skey'`
**Cause:** Running old `schema.sql` that used `key` instead of `skey` (MySQL reserved word).

**Fix:** Use `install.sql` which uses `skey` correctly from the start. Or run:
```sql
ALTER TABLE settings CHANGE COLUMN `key` `skey` VARCHAR(100) NOT NULL;
```

---

## setup.php Issues

### `403 Forbidden` on setup.php

**Cause:** An old version of `.htaccess` blocked `setup.php` with a `FilesMatch` rule.

**Fix:** Replace `.htaccess` with the latest version — `setup.php` is no longer in the blocked files list.

---

### setup.php says "Admin account already exists"
The admin was already created. You don't need setup.php anymore — **delete it**.

If you need to reset the admin password, use the Forgot Password flow at `/forgot-password`, or update it directly in the database:
```sql
-- Replace the hash with a new bcrypt hash
-- Use an online bcrypt generator or PHP: password_hash('newpassword', PASSWORD_BCRYPT, ['cost'=>12])
UPDATE users SET password_hash = '$2y$12$...' WHERE username = 'admin';
```

---

## Upload / Profile Image Issues

### `move_uploaded_file(): Unable to move ... No such file or directory`
**Cause 1:** The `uploads/profiles/` directory doesn't exist.
**Fix:** Create it manually via cPanel File Manager or:
```bash
mkdir -p /path/to/vvcard/uploads/profiles
chmod 755 /path/to/vvcard/uploads/profiles
```
The app will also auto-create it on the next request after this fix.

**Cause 2 (Windows/XAMPP):** Mixed path separators — `C:\xampp\htdocs\vvcard/uploads/...`
**Fix:** Already patched in `config.php` with `str_replace('\\', '/', __DIR__)`. Replace your `config.php` with the latest version.

---

### "Invalid file content. Only images are allowed."
**Cause:** The file's actual MIME type (read by `finfo`) doesn't match an allowed type, even if the extension is correct.

**Fix:** Ensure the file is a genuine JPG, PNG, or GIF — not a renamed file. Try re-saving it from an image editor.

---

### "File too large. Maximum size is 2 MB."
**Fix:** Compress the image before uploading. The limit is set in `app-defaults.php`:
```php
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // change to 5 MB
```
Also check PHP's own limits in `php.ini`:
```ini
upload_max_filesize = 5M
post_max_size = 6M
```

---

## Form / UI Issues

### Form labels overlap with filled text
**Cause:** Old MDB5 version required JavaScript (`new mdb.Input(el).init()`) to activate floating labels. When MDB JS failed to load or initialize, labels would sit on top of values.

**Fix:** Already patched — floating labels now use pure CSS (`:placeholder-shown` selector) with no JS dependency. Replace `assets/css/custom.css` and `assets/js/custom.js` with the latest versions.

---

### Dropdown menus don't open (logout not accessible)
**Cause:** The MDB5 CDN JS doesn't always auto-initialize `data-bs-toggle="dropdown"` components reliably.

**Fix:** Already patched — both the public navbar and admin navbar now use manual JavaScript click handlers instead of Bootstrap/MDB data attributes. Replace `templates/layout_footer.php` and `admin/layout_footer.php`.

---

### Mobile navbar toggle doesn't work
Same cause as dropdown issue above. **Fix:** Same fix — replace both layout footer files.

---

## Routing Issues

### Every page shows 404
**Cause 1:** `mod_rewrite` is not enabled on Apache.
**Fix:** Enable `mod_rewrite` and set `AllowOverride All` for your directory. See [Installation Guide](installation.md).

**Cause 2:** Wrong `RewriteBase` in `.htaccess`.
**Fix:** The new `.htaccess` doesn't use `RewriteBase` at all — routing is handled via `REQUEST_URI` in `index.php`. Replace `.htaccess` with the latest version.

---

### Admin panel returns 404 for `/admin/`
**Cause:** The admin panel uses direct PHP file access, not the router. The URL must end in `/` or point to `admin/index.php`.

**Fix:** Visit `yourdomain.com/vvcard/admin/` (with trailing slash), not `yourdomain.com/vvcard/admin`.

---

### `sitemap.xml` or `robots.txt` returns 404
**Cause:** These are routed through `index.php` as system routes. If the `.htaccess` rewrite isn't working, they won't resolve.

**Fix:** Ensure Apache `mod_rewrite` is working. Test with `/login` — if that works, the router is fine.

---

## Email Issues

### Password reset / invitation link shown on screen instead of emailed
**This is expected behavior** when PHPMailer is not installed or SMTP is not configured.

**Fix:** 
1. Install PHPMailer (visit `/install_phpmailer.php` as admin)
2. Configure SMTP in **Admin → Settings → SMTP**
3. Use the **Send Test Email** button to verify it's working

---

### SMTP test email fails: "Could not connect to SMTP host"
Common causes:
- Wrong host/port combination
- Firewall blocking outbound port 587 on your server
- Gmail: need to use an **App Password** (not your regular password) if 2FA is enabled
- Gmail: may need to enable "Less secure app access" for non-OAuth connections

---

### PHPMailer not found after installation
**Cause:** The `vendor/autoload.php` wasn't created, or PHPMailer files are in the wrong directory.

**Fix:** Visit `/install_phpmailer.php` and click **Create vendor/autoload.php** (Option B). Verify files exist at:
```
vendor/
└── phpmailer/
    └── phpmailer/
        └── src/
            ├── PHPMailer.php
            ├── SMTP.php
            └── Exception.php
```

---

## Database Issues

### Changes in admin not saving
**Cause:** PDO exceptions are being silently swallowed, or the `settings` table is missing.

**Fix:** Set `APP_DEBUG = true`, attempt the save again, and look for the error message. If the `settings` table is missing, re-import `install.sql` (this will **wipe all data** — backup first).

---

### "Too many connections" error
**Fix:** Most shared hosting has connection limits. Ensure `getDB()` is only called when needed — it uses a singleton pattern so only one connection is made per request. If the issue persists, contact your host.

---

## cPanel-Specific Issues

### `.htaccess` not working on cPanel
**Cause:** Apache's `AllowOverride` is set to `None` for your directory.

**Fix:** In cPanel → **Apache Handlers** or contact your host to enable `AllowOverride All` for your directory. Most cPanel hosts have this enabled by default.

---

### File permissions error on upload
**Fix:** In cPanel → File Manager, right-click `uploads/profiles/` → **Change Permissions** → set to `755`.

---

### `install.bat` doesn't work on cPanel
`install.bat` is for Windows (XAMPP). For cPanel:
1. Use the browser installer: `/install_phpmailer.php`
2. Or use cPanel Terminal: `bash install.sh`
3. Or use cPanel File Manager for manual upload (see [Installation Guide](installation.md))

---

## Still Stuck?

1. Enable `APP_DEBUG = true` in `app-defaults.php`
2. Check the error message carefully — it includes the file path and line number
3. Check your PHP error log (cPanel → Logs → Error Log)
4. Verify your `config.php` DB credentials by connecting with phpMyAdmin using the same credentials
