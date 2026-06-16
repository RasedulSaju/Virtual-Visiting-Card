# Virtual Visiting Card

A custom lightweight CMS and User Portal built with **PHP 8+**, **MySQL**, and **MDB5 (Material Design Bootstrap)**.

## Features

### Public
- Clean URL routing — `domain/username` → user profile, `domain/about-us` → CMS page
- User registration (open/invite-only/closed)
- Password reset via secure token (email or dev-mode link)
- Public members directory (`/members`)
- Auto-generated XML sitemap (`/sitemap.xml`)
- Open Graph + Twitter card meta tags on all pages

### User
- Profile page with avatar, bio, and custom fields
- Self-managed profile editing (controlled per-user by admin)
- Custom profile fields (text, URL, textarea) defined by admin

### Admin Panel (`/admin`)
- Dashboard with stats and quick actions
- Full CRUD for users (create, edit, delete, bulk actions)
- Full CRUD for pages (slug, content, nav visibility)
- Profile fields builder (icon picker, field type, sort order)
- Navigation menu builder (drag-to-reorder, live preview)
- Invitation system (48hr signed tokens, bypass registration gate)
- Settings: registration toggle, SMTP email, analytics integrations

### Analytics (Admin > Settings > Analytics)
- Google Analytics 4
- Google Tag Manager
- Microsoft Clarity
- Meta (Facebook) Pixel
- Hotjar
- Plausible Analytics
- Custom `<head>` / `<body>` code injection

### Email (Admin > Settings > SMTP)
- PHPMailer integration with any SMTP provider
- Password reset emails
- Invitation emails
- Test-send from admin panel
- Falls back to dev-mode link display when unconfigured

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL 5.7+ / MariaDB 10+ |
| Frontend | MDB5 (Material Design Bootstrap) via CDN |
| Icons | Font Awesome 6 |
| Typography | Space Grotesk (Google Fonts) |
| Email | PHPMailer (optional) |

---

## Installation

### 1. Database

```sql
SOURCE install.sql;
```

This single file creates all tables, seed data, and default settings
(registration, SMTP, theme, SEO, etc.).

### 2. Configuration

```bash
cp config.php.example config.php
```

Edit `config.php` — only the database credentials need changing:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

`BASE_URL` is auto-detected from the server environment — no manual
configuration needed, works for root or subfolder installs.

> **Note:** `config.php` is in `.gitignore` — never commit credentials.
> All other app constants live in `app-defaults.php` (versioned,
> updates automatically — you never need to edit it).

### 3. Admin Account

Visit `http://yourdomain.com/setup.php` in your browser.  
**Delete `setup.php` immediately after creating your admin account.**

### 4. Directory Permissions

```bash
chmod 755 uploads/profiles
```

### 5. Email (Optional)

```bash
composer require phpmailer/phpmailer
```

Then configure SMTP in **Admin → Settings → SMTP**.

### 6. Web Server

**Apache** — `.htaccess` is included. Ensure `mod_rewrite` is enabled.

**Nginx** — add to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

---

## Project Structure

```
/
├── index.php                  # Front controller / router
├── config.php                 # DB credentials + BASE_URL (gitignored, edit once)
├── app-defaults.php           # All other app constants (versioned, auto-updates)
├── helpers.php                # Shared functions (session, CSRF, flash, upload, SEO, theme)
├── db.php                     # PDO singleton
├── mailer.php                 # PHPMailer wrapper
├── install.sql                # Complete DB schema + seed data (single file)
├── setup.php                  # One-time admin account creator (delete after use)
│
├── login.php / logout.php / register.php
├── forgot_password.php / reset_password.php
├── edit_profile.php           # User self-edit (bio, image, custom fields)
├── members.php                # Public members directory
├── sitemap.php                # Auto-generated sitemap.xml
│
├── templates/
│   ├── layout_header.php      # Global nav + analytics injection + OG tags
│   ├── layout_footer.php      # Footer + MDB5 JS
│   ├── page.php               # CMS page renderer
│   ├── profile.php            # User profile card + fields
│   └── 404.php
│
├── admin/
│   ├── auth_check.php         # Admin guard
│   ├── layout_header.php      # Dark sidebar layout
│   ├── layout_footer.php
│   ├── index.php              # Dashboard
│   ├── users/                 # CRUD + bulk actions
│   ├── pages/                 # CRUD
│   ├── fields/                # Profile field builder
│   ├── nav/                   # Navigation menu manager
│   ├── invitations/           # Invite system
│   └── settings/              # General, SMTP, Analytics tabs
│
├── assets/
│   ├── css/custom.css         # Public design system
│   ├── css/admin.css          # Admin panel styles
│   ├── js/custom.js           # Shared JS utilities
│   └── img/default-avatar.svg
│
└── uploads/
    └── profiles/              # User-uploaded images (gitignored)
```

---

## Security

- PDO prepared statements — no SQL injection
- `htmlspecialchars()` on all user output — no XSS
- CSRF tokens on all POST forms
- Bcrypt (cost 12) password hashing
- `finfo` MIME + extension validation on uploads
- Filenames fully sanitized on upload (`user_ID_timestamp.ext`)
- Session regeneration on login
- `httponly` + `samesite=Lax` session cookies
- Admin self-delete protected
- `.htaccess` blocks direct access to `.sql`, `.env`, `config.php`, `db.php`

---

## License

MIT
