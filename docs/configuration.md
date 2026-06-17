# Configuration Reference

---

## config.php

Your **private** configuration file. Gitignored — never committed to version control.  
Only edit the database credentials. Everything else is in `app-defaults.php`.

```php
// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');     // Database host
define('DB_NAME',    'cms_db');        // Database name
define('DB_USER',    'root');          // Database username
define('DB_PASS',    '');              // Database password
define('DB_CHARSET', 'utf8mb4');       // Do not change

// BASE_URL is auto-detected — no manual configuration needed
```

> **BASE_URL** is automatically calculated from `$_SERVER['DOCUMENT_ROOT']` and `__DIR__`.  
> Works for root installs (`domain.com/`) and subfolder installs (`domain.com/vvcard/`) with no changes.

---

## app-defaults.php

**Versioned** — tracked by git, updates automatically when you replace project files.  
You should not need to edit this file. Override any constant in `config.php` if needed.

| Constant | Default | Description |
|---|---|---|
| `APP_NAME` | `'Virtual Visiting Card'` | Fallback site name (overridden by DB setting) |
| `APP_DESCRIPTION` | `'Create and share your digital visiting card.'` | Fallback description |
| `APP_DEBUG` | `true` | Set to `false` in production |
| `SESSION_LIFETIME` | `0` | Session cookie lifetime in seconds (0 = until browser closes) |
| `UPLOAD_DIR` | `/path/to/project/uploads/profiles/` | Absolute server path for uploads |
| `UPLOAD_URL` | `BASE_URL . 'uploads/profiles/'` | Public URL for uploaded images |
| `MAX_UPLOAD_SIZE` | `2097152` (2 MB) | Maximum profile image size in bytes |
| `ALLOWED_EXT` | `['jpg','jpeg','png','gif']` | Allowed image extensions |
| `ALLOWED_MIME` | `['image/jpeg','image/png','image/gif']` | Allowed MIME types (validated via finfo) |
| `DEFAULT_AVATAR` | `'default-avatar.svg'` | Filename of the fallback avatar |

### Overriding a constant
Add the `define()` call to `config.php` **before** the `require_once 'app-defaults.php'` line:
```php
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // Override to 5 MB
define('APP_DEBUG', false);                  // Disable debug in production

require_once __DIR__ . '/app-defaults.php';  // Always last
```

---

## Admin Settings (Database-driven)

All settings below are stored in the `settings` table and editable from **Admin → Settings**.

### General Tab

| Setting Key | Default | Description |
|---|---|---|
| `site_name` | `'Virtual Visiting Card'` | Site title — shown in navbar, emails, browser tabs, OG tags |
| `site_description` | `'Create and share...'` | Used in `<meta name="description">` and Open Graph |
| `registration_open` | `'1'` | `'1'` = open · `'0'` = closed (invite-only bypass still works) |

### SMTP Tab

| Setting Key | Default | Description |
|---|---|---|
| `smtp_host` | `''` | SMTP server hostname, e.g. `smtp.gmail.com` |
| `smtp_port` | `'587'` | Port — typically `587` (TLS) or `465` (SSL) |
| `smtp_username` | `''` | SMTP account username / email |
| `smtp_password` | `''` | SMTP account password or app password |
| `smtp_encryption` | `'tls'` | `'tls'`, `'ssl'`, or `'none'` |
| `smtp_from_email` | `''` | Sender email address |
| `smtp_from_name` | `''` | Sender display name |

**Common SMTP providers:**

| Provider | Host | Port | Encryption |
|---|---|---|---|
| Gmail | `smtp.gmail.com` | `587` | TLS |
| Outlook / Office 365 | `smtp.office365.com` | `587` | TLS |
| Mailgun | `smtp.mailgun.org` | `587` | TLS |
| SendGrid | `smtp.sendgrid.net` | `587` | TLS |
| Brevo (Sendinblue) | `smtp-relay.brevo.com` | `587` | TLS |

### Appearance Tab

| Setting Key | Default | Description |
|---|---|---|
| `theme_primary_color` | `#4f46e5` | Buttons, links, active states |
| `theme_accent_color` | `#7c3aed` | Gradients, secondary highlights |
| `theme_heading_color` | `#0f172a` | h1–h6, titles |
| `theme_text_color` | `#374151` | Body text, paragraphs |
| `theme_bg_color` | `#f8fafc` | Page background |
| `theme_surface_color` | `#ffffff` | Cards, navbar, inputs |
| `theme_border_radius` | `12` | Corner roundness in px (0–24) |
| `theme_font_heading` | `Space Grotesk` | Any Google Font name |
| `theme_font_body` | `system-ui` | Body font (Google Font or CSS value) |
| `theme_enable_animations` | `1` | `1` = on · `0` = disables all transitions/animations |

### SEO Tab

| Setting Key | Default | Description |
|---|---|---|
| `seo_global_noindex` | `'0'` | `'1'` = hides entire site from search engines |
| `robots_txt_custom` | `''` | Custom robots.txt content (leave empty for auto-generated) |

### Analytics Tab

| Setting Key | Description |
|---|---|
| `analytics_ga4_id` | Google Analytics 4 Measurement ID (e.g. `G-XXXXXXXXXX`) |
| `analytics_gtm_id` | Google Tag Manager Container ID (e.g. `GTM-XXXXXXX`) |
| `analytics_clarity_id` | Microsoft Clarity Project ID |
| `analytics_fb_pixel_id` | Meta (Facebook) Pixel ID |
| `analytics_hotjar_id` | Hotjar Site ID |
| `analytics_plausible_domain` | Your domain registered in Plausible |
| `analytics_custom_head` | Raw HTML injected before `</head>` |
| `analytics_custom_body` | Raw HTML injected after `<body>` |

> If both GA4 and GTM IDs are set, the GA4 snippet is suppressed automatically (configure GA4 inside GTM instead — the correct production setup).

---

## Per-Page / Per-Profile Settings

### meta_robots options

Set per page in **Admin → Pages → Edit** and per user in **Admin → Users → Edit**:

| Value | Meta Tag Output | Sitemap | Description |
|---|---|---|---|
| `index,follow` (default) | `<meta name="robots" content="index,follow">` | ✅ Included | Normal — indexed by search engines |
| `noindex,follow` | `<meta name="robots" content="noindex,follow">` | ❌ Excluded | Hidden from search, links still pass value |
| `index,nofollow` | `<meta name="robots" content="index,nofollow">` | ✅ Included | Indexed, but outbound links not followed |
| `noindex,nofollow` | `<meta name="robots" content="noindex,nofollow">` | ❌ Excluded | Fully hidden — no indexing, no link following |
