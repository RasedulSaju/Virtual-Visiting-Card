# Virtual Visiting Card — Documentation

> A custom lightweight CMS and User Portal built with PHP 8+, MySQL, and MDB5.

---

## Table of Contents

| # | Document | Description |
|---|---|---|
| 1 | [Installation Guide](installation.md) | Fresh install, XAMPP, cPanel step-by-step |
| 2 | [Configuration Reference](configuration.md) | `config.php`, `app-defaults.php`, all settings |
| 3 | [Admin Panel Guide](admin-guide.md) | Every admin section explained |
| 4 | [User Guide](user-guide.md) | Registration, profiles, password reset |
| 5 | [Developer Guide](developer.md) | Architecture, file structure, extending |
| 6 | [Troubleshooting](troubleshooting.md) | Common errors and fixes |

---

## Quick Start

```
1. Import install.sql into your MySQL database
2. Edit config.php — enter your DB credentials (4 lines)
3. Visit /setup.php — create your admin account
4. Delete setup.php
5. Log in at /login
```

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

## Key Features at a Glance

- **Clean URL routing** — `domain/username` → profile · `domain/slug` → CMS page
- **Admin panel** — full CRUD for users, pages, fields, navigation, settings
- **Registration control** — open / invite-only / closed, 48-hour signed invite links
- **SMTP email** — password resets and invitations via PHPMailer
- **Theme system** — colors, fonts, border radius, animations — all editable from admin
- **Analytics** — GA4, GTM, Clarity, Meta Pixel, Hotjar, Plausible
- **SEO** — per-page `noindex`/`nofollow`, editable `robots.txt`, dynamic `sitemap.xml`
- **Zero hardcoded values** — `BASE_URL` auto-detected, site name editable from admin
