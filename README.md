# Virtual Visiting Card

> A custom lightweight CMS and User Portal — PHP 8+, MySQL, MDB5 (Material Design Bootstrap).

---

## Documentation

| Guide | Description |
|---|---|
| [Installation](docs/installation.md) | cPanel, XAMPP, Linux VPS step-by-step |
| [Configuration](docs/configuration.md) | config.php, app-defaults.php, all admin settings |
| [Admin Panel Guide](docs/admin-guide.md) | Every admin section explained |
| [User Guide](docs/user-guide.md) | Registration, profiles, password reset |
| [Developer Guide](docs/developer.md) | Architecture, file structure, helpers reference |
| [Troubleshooting](docs/troubleshooting.md) | Common errors and fixes |

---

## Quick Install (3 steps)

```
1. Import install.sql into your MySQL database
2. Edit config.php — enter your DB credentials (4 lines)
3. Visit /setup.php → create admin → delete setup.php
```

---

## Features

| Area | Details |
|---|---|
| **Routing** | Clean URLs — `domain/username` → profile · `domain/slug` → page · auto 404 |
| **Auth** | Login, register, forgot/reset/change password, invite-only registration |
| **Profiles** | Avatar upload, bio, admin-defined custom fields (text/url/textarea) |
| **Admin Panel** | Full CRUD — users, pages, profile fields, navigation builder |
| **Registration Control** | Open / closed / invite-only · 48-hour signed invite links |
| **Email** | PHPMailer SMTP — password resets + invitations · browser installer for cPanel |
| **Appearance** | Live color pickers, Google Fonts, border radius slider, animation toggle |
| **Analytics** | GA4, GTM, Clarity, Meta Pixel, Hotjar, Plausible, custom code injection |
| **SEO** | Per-page/profile noindex+nofollow · editable robots.txt · dynamic sitemap.xml |
| **Dynamic** | BASE_URL auto-detected · site name/description editable from admin · zero hardcoded values |
| **Security** | PDO prepared statements · CSRF · bcrypt cost-12 · finfo upload validation · session hardening |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+ |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | MDB5 (Material Design Bootstrap) via CDN |
| Icons | Font Awesome 6 |
| Typography | Space Grotesk (Google Fonts) |
| Email | PHPMailer 6.x (optional) |

---

## File Structure

```
vvcard/
├── index.php              # Front controller / 4-tier URL router
├── config.php             # DB credentials + auto-detected BASE_URL (gitignored)
├── app-defaults.php       # Versioned app constants (auto-updates with project)
├── helpers.php            # All shared functions
├── db.php                 # PDO singleton
├── mailer.php             # PHPMailer SMTP wrapper
├── install.sql            # Complete DB schema + all seed data
├── setup.php              # One-time admin account creator (delete after use)
├── install_phpmailer.php  # Browser-based PHPMailer installer (delete after use)
├── install.sh             # Linux/SSH PHPMailer installer
├── install.bat            # Windows PHPMailer installer
│
├── templates/             # Public page templates
├── admin/                 # Admin panel (auth-protected)
├── assets/                # CSS, JS, images
├── uploads/profiles/      # User avatars (gitignored)
└── docs/                  # Documentation
```

---

## Configuration

`config.php` — the only file you ever need to edit:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
// BASE_URL is auto-detected — no manual config needed
```

All other constants live in `app-defaults.php` (tracked by git, never needs editing).

---

## Security

- PDO prepared statements with emulation off — no SQL injection
- `htmlspecialchars()` on all user output — no XSS  
- CSRF tokens on all POST forms
- bcrypt cost-12 password hashing
- `finfo` MIME + extension validation on file uploads
- Filenames fully sanitized on upload (`user_ID_timestamp.ext`)
- Session regeneration on login
- `httponly` + `samesite=Lax` session cookies
- Admin self-delete protected
- `.htaccess` blocks direct access to config, helper, and SQL files

---

## License

MIT
