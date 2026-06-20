# Developer Guide

---

## Architecture Overview

```
Browser → Apache/Nginx → .htaccess → index.php (Front Controller)
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                       │
              System Routes          Pages Table             Users Table
           (login, register,       (CMS pages by          (profiles by
            forgot-password, etc.)      slug)                 username)
                    │                      │                       │
              PHP file              templates/             templates/
              included              page.php               profile.php
                                        │                       │
                                layout_header.php + layout_footer.php
```

**4-tier routing priority:**
1. Hardcoded system routes (`login`, `register`, `forgot-password`, etc.)
2. `pages` table — match slug against URL
3. `users` table — match username against URL
4. 404 fallback

---

## File Structure

```
vvcard/
├── index.php                  # Front controller — URL parsing + routing
├── config.php                 # DB credentials + BASE_URL (gitignored)
├── app-defaults.php           # Versioned app constants
├── db.php                     # PDO singleton (getDB())
├── helpers.php                # All shared functions
├── mailer.php                 # PHPMailer wrapper class
├── install.sql                # Complete DB schema + seed data
├── setup.php                  # One-time admin creator (delete after use)
├── install_phpmailer.php      # Browser-based PHPMailer installer
├── install.sh                 # Linux/SSH PHPMailer installer
├── install.bat                # Windows PHPMailer installer
├── robots.php                 # Dynamic robots.txt output
├── sitemap.php                # Dynamic sitemap.xml output
│
├── login.php                  # Login form + handler
├── logout.php                 # Session destroy
├── register.php               # Registration (invite-aware)
├── forgot_password.php        # Password reset token generation
├── reset_password.php         # Password reset form + handler
├── edit_profile.php           # User self-edit (bio, image, fields)
├── change_password.php        # Logged-in password change
├── members.php                # Public members directory
│
├── templates/
│   ├── layout_header.php      # Public navbar + analytics + OG tags + theme
│   ├── layout_footer.php      # Footer + MDB5 JS + dropdown/collapse init
│   ├── page.php               # CMS page renderer
│   ├── profile.php            # User profile card + custom fields
│   └── 404.php                # 404 error page
│
├── admin/
│   ├── auth_check.php         # Admin guard (require helpers.php first)
│   ├── layout_header.php      # Admin sidebar + topbar + theme injection
│   ├── layout_footer.php      # Admin footer + JS
│   ├── index.php              # Dashboard
│   ├── users/
│   │   ├── index.php          # User list + bulk actions
│   │   ├── create.php         # Create user
│   │   ├── edit.php           # Edit user
│   │   └── delete.php         # Delete handler (POST only)
│   ├── pages/
│   │   ├── index.php          # Page list
│   │   ├── create.php         # Create page
│   │   ├── edit.php           # Edit page
│   │   └── delete.php         # Delete handler (POST only)
│   ├── fields/
│   │   ├── index.php          # Field list
│   │   ├── create.php         # Create field
│   │   ├── edit.php           # Edit field
│   │   └── delete.php         # Delete handler
│   ├── nav/
│   │   └── index.php          # Navigation menu builder
│   ├── invitations/
│   │   └── index.php          # Send + manage invitations
│   └── settings/
│       └── index.php          # All settings tabs (General, SMTP, Appearance, SEO, Analytics)
│
├── assets/
│   ├── css/
│   │   ├── custom.css         # Public design system + CSS variables
│   │   └── admin.css          # Admin panel layout + sidebar
│   ├── js/
│   │   └── custom.js          # Shared JS (floating labels, dropdown, avatar preview)
│   └── img/
│       └── default-avatar.png # Default user avatar
│
└── uploads/
    └── profiles/              # User-uploaded profile images (gitignored)
        └── .gitkeep
```

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `username` | VARCHAR(50) UNIQUE | Used as URL slug |
| `email` | VARCHAR(150) UNIQUE | |
| `password_hash` | VARCHAR(255) | bcrypt cost-12 |
| `role` | ENUM('admin','user') | |
| `can_edit_profile` | TINYINT(1) | Admin-controlled |
| `meta_robots` | VARCHAR(20) | `index,follow` default |
| `profile_image` | VARCHAR(255) | Filename in uploads/profiles/ |
| `bio` | TEXT | |
| `reset_token` | VARCHAR(64) | Null when not in reset flow |
| `reset_expires` | DATETIME | 1-hour expiry |
| `created_at` | DATETIME | |

### `pages`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `slug` | VARCHAR(200) UNIQUE | URL path segment |
| `title` | VARCHAR(300) | |
| `content` | LONGTEXT | Raw HTML (admin-trusted) |
| `show_in_nav` | TINYINT(1) | Navbar visibility |
| `nav_order` | INT | Lower = first |
| `meta_robots` | VARCHAR(20) | `index,follow` default |
| `updated_at` | DATETIME | Auto-updated |

### `profile_fields`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `field_name` | VARCHAR(100) UNIQUE | Machine key |
| `field_label` | VARCHAR(150) | Display name |
| `field_type` | ENUM('text','url','textarea') | |
| `field_icon` | VARCHAR(100) | Font Awesome class |
| `sort_order` | INT | Display order |
| `is_active` | TINYINT(1) | |
| `created_at` | DATETIME | |

### `user_field_values`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `user_id` | INT UNSIGNED FK | CASCADE DELETE |
| `field_id` | INT UNSIGNED FK | CASCADE DELETE |
| `field_value` | TEXT | |

### `settings`
| Column | Type | Notes |
|---|---|---|
| `skey` | VARCHAR(100) PK | Setting key |
| `value` | TEXT | Setting value |
| `updated_at` | DATETIME | Auto-updated |

### `invitations`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `email` | VARCHAR(150) | Recipient |
| `token` | VARCHAR(64) UNIQUE | 32-byte hex (bin2hex random_bytes) |
| `invited_by` | INT UNSIGNED FK | Admin user ID |
| `used` | TINYINT(1) | 0 = pending, 1 = used |
| `expires_at` | DATETIME | 48 hours from creation |
| `created_at` | DATETIME | |

---

## helpers.php — Function Reference

### Output / Security
```php
e(string $s): string
// htmlspecialchars() wrapper — always use this for user output
echo e($user['username']);
```

### Flash Messages
```php
flash(string $type, string $msg): void
// Types: 'success', 'error', 'warning', 'info'
flash('success', 'Profile updated.');

renderFlash(): string
// Outputs the MDB5 alert HTML and clears the flash
echo renderFlash();
```

### Navigation
```php
redirect(string $path): never
// Redirects to BASE_URL . $path and exits
redirect('admin/users/');

isLoggedIn(): bool
isAdmin(): bool
requireLogin(): void  // redirects to login if not logged in
requireAdmin(): void  // redirects to login if not admin
```

### CSRF
```php
csrfToken(): string      // Returns or creates session CSRF token
csrfField(): string      // Returns hidden input HTML
verifyCsrf(): void       // Validates POST token, exits 403 on failure
```

### Database
```php
getDB(): PDO
// Returns PDO singleton with ERRMODE_EXCEPTION, EMULATE_PREPARES=false
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
```

### Site Identity
```php
siteName(): string         // Admin-editable site name (cached)
siteDescription(): string  // Admin-editable site description (cached)
siteUrl(): string          // Returns BASE_URL
```

### Settings
```php
getSetting(string $key, string $default = ''): string
setSetting(string $key, string $value): void
```

### Theme
```php
getTheme(): array          // All theme settings as associative array
darkenColor(string $hex, int $percent = 15): string
lightenColor(string $hex, int $percent = 90): string
```

### SEO
```php
resolveMetaRobots(string $itemDirective = 'index,follow'): string
// Combines per-item setting with global noindex toggle
metaRobotsLabel(string $directive): string
buildRobotsTxt(): string   // Generates robots.txt content from settings
```

### Navigation
```php
getNavPages(): array
// Returns pages WHERE show_in_nav = 1 ORDER BY nav_order ASC
```

### Utility
```php
slugify(string $text): string
// 'About Us' → 'about-us'

truncate(string $str, int $length = 60): string
// Truncates with ellipsis

avatarUrl(string $filename): string
// Returns full URL, falls back to default avatar if file missing

uploadProfileImage(array $file, int $userId): string
// Validates and moves uploaded image, returns new filename
// Throws RuntimeException on validation failure

deleteProfileImage(string $filename): void
// Deletes file from disk (skips DEFAULT_AVATAR)
```

---

## Adding a New System Route

In `index.php`, add to the `$systemRoutes` array:
```php
$systemRoutes = [
    // ... existing routes ...
    'my-new-page' => __DIR__ . '/my_new_page.php',
];
```
Then create `my_new_page.php` in the project root.

---

## Adding a New Admin Section

1. Create directory: `admin/mysection/`
2. Create `admin/mysection/index.php`:
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pageTitle = 'My Section';
$activeNav = 'mysection'; // matches sidebar key
require_once __DIR__ . '/../layout_header.php';
?>

<!-- your content here -->

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
```
3. Add to `admin/layout_header.php` sidebar:
```php
<?= _adminNavLink(BASE_URL . 'admin/mysection/', 'fas fa-star', 'My Section', 'mysection', $activeNav) ?>
```

---

## Adding a New Theme Variable

1. Add to `migration_005_theme_settings.sql` (or `install.sql`):
```sql
INSERT INTO settings (skey, value) VALUES ('theme_my_var', '#ffffff')
ON DUPLICATE KEY UPDATE value = VALUES(value);
```
2. Add to `getTheme()` in `helpers.php`:
```php
$defaults = [
    // ... existing ...
    'my_var' => '#ffffff',
];
```
3. Inject CSS in `layout_header.php` theme block:
```php
--cms-my-var: <?= e($_theme['my_var']) ?>;
```
4. Use in `custom.css`:
```css
.my-element { color: var(--cms-my-var); }
```

---

## Security Model

| Threat | Defense |
|---|---|
| SQL Injection | PDO prepared statements, emulation off |
| XSS | `e()` (htmlspecialchars) on all user output |
| CSRF | `csrfToken()` / `verifyCsrf()` on all POST forms |
| File upload attacks | finfo MIME check + extension whitelist + filename sanitization |
| Session fixation | `session_regenerate_id(true)` on login |
| Password brute force | bcrypt cost-12, no enumeration (consistent response for unknown email) |
| Directory traversal | All filenames replaced with `user_ID_timestamp.ext` |
| Admin access | `requireAdmin()` guard on every admin file |
| Sensitive files | `.htaccess` blocks direct access to `.php` config/helper files and `.sql` files |
