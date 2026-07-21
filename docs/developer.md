# Developer Guide

---

## Architecture Overview

```
Browser Request
     │
     ▼
.htaccess  ──► index.php (Front Controller)
                    │
        ┌───────────┼───────────────┐
        ▼           ▼               ▼
  System Routes  pages table   users table
  (login, etc.)  (by slug)     (by username)
        │           │               │
        ▼           ▼               ▼
   PHP files    page.php        profile.php
                    │               │
              layout_header.php + layout_footer.php
```

**Routing priority (4-tier):**
1. Hardcoded system routes — login, logout, register, forgot-password, etc.
2. `pages` table — match URL slug against `pages.slug`
3. `users` table — match URL against `users.username`
4. 404 — everything else

URL is parsed from `$_SERVER['REQUEST_URI']` with `BASE_URL`'s path stripped — no `?url=` query string, no `RewriteBase` needed, works at root or any subfolder automatically.

---

## Project File Structure

```
vvcard/
├── index.php                    # Front controller — URL routing
├── config.php                   # DB credentials + BASE_URL (gitignored)
├── app-defaults.php             # Versioned constants (upload limits, debug, etc.)
├── db.php                       # PDO singleton: getDB()
├── helpers.php                  # All shared PHP functions
├── mailer.php                   # PHPMailer SMTP wrapper class
├── robots.php                   # Dynamic robots.txt output
├── sitemap.php                  # Dynamic sitemap.xml output
├── install.sql                  # Complete DB schema + seed data
├── _account_recovery.php        # Hidden, key-gated superadmin tool
│
├── login.php                    # Login form + session handler
├── logout.php                   # Session destroy
├── register.php                 # Registration (open/invite-aware)
├── forgot_password.php          # Password reset token generation
├── reset_password.php           # New password form + handler
├── change_password.php          # Logged-in password change
├── edit_profile.php             # User self-edit (bio, avatar, fields)
├── members.php                  # Public members directory
│
├── templates/
│   ├── layout_header.php        # Public navbar, meta, analytics, theme CSS vars, MDB Pro
│   ├── layout_footer.php        # Footer, MDB JS, Pro module JS, custom JS
│   ├── page.php                 # CMS page renderer
│   ├── profile.php              # User profile card + custom fields + animations
│   └── 404.php                  # 404 page
│
├── admin/
│   ├── auth_check.php           # Admin session guard
│   ├── layout_header.php        # Admin sidebar, topbar, theme vars, MDB Pro
│   ├── layout_footer.php        # Admin JS, Pro module JS
│   ├── index.php                # Dashboard
│   ├── users/
│   │   ├── index.php            # User list (bulk actions, filters)
│   │   ├── create.php           # Create user form
│   │   ├── edit.php             # Edit user form
│   │   └── delete.php           # POST-only delete handler
│   ├── pages/
│   │   ├── index.php            # Page list
│   │   ├── create.php           # Create page (HTML editor + image upload)
│   │   ├── edit.php             # Edit page
│   │   └── delete.php           # POST-only delete handler
│   ├── fields/
│   │   ├── index.php            # Profile field list
│   │   ├── create.php           # Create field
│   │   ├── edit.php             # Edit field
│   │   └── delete.php           # POST-only delete handler
│   ├── nav/
│   │   └── index.php            # Navigation builder (drag + toggle)
│   ├── invitations/
│   │   └── index.php            # Send + manage invitations
│   ├── media/
│   │   └── upload.php           # AJAX endpoint: page image uploads
│   └── settings/
│       └── index.php            # Tabs: General, SMTP, Appearance, SEO, Analytics
│
├── assets/
│   ├── css/
│   │   ├── custom.css           # Public design system + CSS variables + watermark
│   │   └── admin.css            # Admin sidebar layout + topbar + dropdown
│   ├── js/
│   │   └── custom.js            # Floating labels, dropdown, collapse, confirm
│   └── mdb-pro/                 # MDB Pro files (gitignored — drop in your files)
│       ├── mdb.min.css
│       ├── mdb.min.js
│       └── modules/
│           ├── animate.min.css
│           ├── animate.min.js
│           └── [other modules]
│
├── uploads/
│   ├── profiles/                # User avatars + default-avatar.png
│   └── pages/                   # Images uploaded via page editor
│
└── docs/                        # This documentation
```

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `username` | VARCHAR(50) UNIQUE | URL slug — `domain.com/username` |
| `full_name` | VARCHAR(150) NULL | Profile title if set, falls back to username via `displayName()` |
| `email` | VARCHAR(150) UNIQUE | |
| `password_hash` | VARCHAR(255) | bcrypt cost-12 |
| `role` | ENUM('user','admin','superadmin') | |
| `account_status` | ENUM('active','resigned') | resigned → watermark on profile |
| `can_edit_profile` | TINYINT(1) | admin-controlled per user |
| `meta_robots` | VARCHAR(20) | `index,follow` default |
| `show_in_directory` | TINYINT(1) | visible on /members page |
| `profile_image` | VARCHAR(255) | filename in uploads/profiles/ |
| `bio` | TEXT | |
| `reset_token` | VARCHAR(64) | null when not in reset flow |
| `reset_expires` | DATETIME | 1-hour expiry |
| `created_at` | DATETIME | |

### `pages`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `slug` | VARCHAR(200) UNIQUE | URL path — `domain.com/slug` |
| `title` | VARCHAR(300) | |
| `content` | LONGTEXT | Raw HTML (admin-trusted) |
| `show_in_nav` | TINYINT(1) | appears in navbar |
| `nav_order` | INT | lower = first |
| `meta_robots` | VARCHAR(20) | `index,follow` default |
| `updated_at` | DATETIME | auto-updated |

### `profile_fields`
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AI PK | |
| `field_name` | VARCHAR(100) UNIQUE | machine key e.g. `twitter_handle` |
| `field_label` | VARCHAR(150) | display name |
| `field_type` | ENUM('text','url','textarea','date','select') | |
| `field_icon` | VARCHAR(100) | Font Awesome class |
| `field_options` | TEXT NULL | For `select` type — one option per line |
| `edit_permission` | ENUM('user','admin') | `user` = self-service; `admin` = only admin sets it, member sees read-only |
| `lock_after_set` | TINYINT(1) | Once member sets a value, it becomes read-only for them (admins unaffected) |
| `is_repeatable` | TINYINT(1) | Allows multiple values for this field (e.g. two phone numbers) |
| `group_key` | VARCHAR(50) NULL | Fields sharing this key repeat together as a set (e.g. Position + Company) |
| `group_label` | VARCHAR(150) NULL | Heading shown above each repeated group instance |
| `sort_order` | INT | display order on profiles |
| `is_active` | TINYINT(1) | inactive = hidden from all profiles |
| `is_public` | TINYINT(1) | private = owner + admins only |
| `created_at` | DATETIME | |

### `user_field_values`
| Column | Type | Notes |
|---|---|---|
| `user_id` | INT UNSIGNED FK | CASCADE DELETE |
| `field_id` | INT UNSIGNED FK | CASCADE DELETE |
| `instance` | INT UNSIGNED | Row index for repeatable/grouped fields — 0 for single-value fields |
| `field_value` | TEXT | |

Unique key is `(user_id, field_id, instance)` — this is what allows a single
field to store multiple values per user (e.g. instance 0 = work phone,
instance 1 = cell phone), and lets grouped fields align by instance number
(e.g. Position[0] pairs with Company[0], Position[1] with Company[1]).

### `settings`
Key/value store. Primary key is `skey`.

### `invitations`
| Column | Type | Notes |
|---|---|---|
| `token` | VARCHAR(64) UNIQUE | 32-byte hex |
| `invited_by` | INT UNSIGNED FK | admin user ID |
| `used` | TINYINT(1) | 0=pending, 1=used |
| `expires_at` | DATETIME | 48 hours from creation |

---

## helpers.php — Full Function Reference

### Output
```php
e(string $s): string
// Always wrap user-supplied values in e() before echoing
echo e($user['username']);
```

### Flash Messages
```php
flash('success', 'Profile saved.');
flash('error',   'Something went wrong.');
flash('warning', 'Check your email.');
flash('info',    'FYI message.');

echo renderFlash(); // outputs Bootstrap alert + clears the flash
```

### Session & Auth
```php
isLoggedIn(): bool
isAdmin(): bool        // true for admin AND superadmin
isSuperAdmin(): bool   // true only for superadmin

requireLogin(): void   // redirects to /login if not logged in
requireAdmin(): void   // redirects to /login if not admin
requireSuperAdmin(): void  // redirects to /admin/ if not superadmin

redirect('admin/users/');  // redirects to BASE_URL . path and exits
```

### CSRF
```php
echo csrfField();   // <input type="hidden" name="csrf_token" value="...">
verifyCsrf();       // validates POST token — call at top of every POST handler
```

### Database
```php
$pdo = getDB();
// Returns PDO singleton with:
// - ERRMODE_EXCEPTION (all errors throw exceptions)
// - FETCH_ASSOC (rows as associative arrays)
// - EMULATE_PREPARES = false (real prepared statements)
```

### Settings
```php
getSetting('site_name', 'Default');   // reads from settings table
setSetting('site_name', 'New Name');  // upserts into settings table
```

### Site Identity
```php
siteName(): string         // from settings, falls back to APP_NAME
siteDescription(): string  // from settings, falls back to APP_DESCRIPTION
siteUrl(): string          // BASE_URL
```

### Theme
```php
$theme = getTheme();
// Returns array with keys: primary_color, accent_color, heading_color,
// text_color, bg_color, surface_color, border_radius, font_heading,
// font_body, enable_animations

darkenColor('#4f46e5', 15):  string  // darken hex by 15%
lightenColor('#4f46e5', 90): string  // lighten hex by 90%
```

### SEO
```php
resolveMetaRobots('index,follow'): string
// Applies global noindex toggle if enabled — always use this

metaRobotsLabel('noindex,nofollow'): string  // human-readable label
buildRobotsTxt(): string  // generates full robots.txt content
```

### Files & Avatars
```php
avatarUrl('user_1_1234.jpg'): string
// Returns full URL, falls back to default avatar if file missing

uploadProfileImage($_FILES['image'], $userId): string
// Validates MIME, extension, size — moves to UPLOAD_DIR
// Returns new filename. Throws RuntimeException on failure.

deleteProfileImage('user_1_1234.jpg'): void
// Deletes file from disk (skips default avatar)
```

### Utilities
```php
slugify('About Us'): string     // 'about-us'
truncate('Long text...', 60): string  // truncates with ellipsis
getNavPages(): array  // pages WHERE show_in_nav=1 ORDER BY nav_order
```

---

## How To: Common Developer Tasks

### Add a New Public Route
In `index.php`, add to `$systemRoutes`:
```php
$systemRoutes = [
    // ... existing routes ...
    'my-page' => __DIR__ . '/my_page.php',
];
```
Then create `my_page.php` in the project root.

### Add a New Admin Section
1. Create `admin/mysection/index.php`:
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

// Optional: declare MDB Pro modules for this page
// $proModules = ['datatable'];

$pageTitle = 'My Section';
$activeNav = 'mysection';
require_once __DIR__ . '/../layout_header.php';
?>

<!-- your content here -->

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
```
2. Add to the sidebar in `admin/layout_header.php`:
```php
<?= _adminNavLink(BASE_URL . 'admin/mysection/', 'fas fa-star', 'My Section', 'mysection', $activeNav) ?>
```

### Add a New Settings Key
1. In the `settings` INSERT block in `install.sql`:
```sql
('my_new_setting', 'default_value'),
```
2. Read it anywhere with `getSetting('my_new_setting', 'fallback')`.
3. Update it with `setSetting('my_new_setting', $value)`.

### Add a New Theme Color Variable
1. Add to `getTheme()` in `helpers.php`:
```php
$defaults = [
    // ... existing ...
    'my_color' => '#ff0000',
];
```
2. Add to `install.sql` settings seed:
```sql
('theme_my_color', '#ff0000'),
```
3. Inject into both layout headers' `:root` block:
```php
--cms-my-color: <?= e($_theme['my_color']) ?>;
```
4. Use in CSS:
```css
.my-element { color: var(--cms-my-color); }
```
5. Add a colour picker in `admin/settings/index.php` Appearance tab.

### Add a New Profile Field Type
1. Update the ENUM in `install.sql`:
```sql
`field_type` ENUM('text','url','textarea','date','phone') NOT NULL DEFAULT 'text',
```
2. Add the option to `admin/fields/create.php` and `edit.php`:
```html
<option value="phone">Phone Number</option>
```
3. Add rendering in `templates/profile.php` and `edit_profile.php`:
```php
} elseif ($field['field_type'] === 'phone') {
    echo '<a href="tel:' . e($val) . '">' . e($val) . '</a>';
}
```

---

## MDB Framework Loading (Pro → Bundled Free → CDN)

### How It Works
The site loads MDB's core CSS/JS with a three-tier fallback, checked in this
exact order, with only ONE actually loading:

1. **`assets/mdb-pro/mdb.min.css` + `mdb.min.js`** — your paid Pro license,
   if you've uploaded it. Gitignored — never committed, since it's licensed.
2. **`assets/mdb-free/mdb.min.css` + `mdb.min.js`** — the free, MIT-licensed
   MDB build, bundled directly in this repo. Committed to git on purpose —
   this is what guarantees the site works immediately after cloning, with
   zero setup steps and zero dependency on an external CDN being reachable.
3. **CDN (`cdnjs.cloudflare.com`)** — only used if neither of the above
   exists on disk for some reason. A last-resort safety net, not the
   primary path.

This means a fresh install works correctly out of the box with no manual
download step, and continues working even if the Pro license is removed,
the bundled free files are somehow deleted, or the server has no outbound
internet access to reach a CDN (as long as the bundled free files exist).

Both `templates/layout_header.php` / `layout_footer.php` and
`admin/layout_header.php` / `layout_footer.php` implement this same
priority order independently.

### Updating the Bundled Free Version
The bundled free files came from the official `mdb-ui-kit` npm package.
To update them to a newer version:
```bash
npm pack mdb-ui-kit@<version>
tar -xzf mdb-ui-kit-<version>.tgz
cp package/css/mdb.min.css assets/mdb-free/
cp package/js/mdb.min.js   assets/mdb-free/
cp package/LICENSE         assets/mdb-free/LICENSE.txt
```
Commit the updated files — they're intentionally tracked by git (see the
note in `.gitignore`).

---

## MDB Pro Module System

### How It Works
Both public and admin layouts support a `$proModules` array. Declare it
before including `layout_header.php` and the matching CSS/JS files are
auto-loaded **only if they exist** in `assets/mdb-pro/modules/`. Modules
are Pro-only — there is no free/bundled equivalent for these, since the
free MDB build doesn't include them.

```php
// At the top of any page, BEFORE requiring layout_header.php:
$proModules = ['datatable', 'perfect-scrollbar'];
require_once __DIR__ . '/../layout_header.php';
```

- Pages that don't declare `$proModules` load zero extra files
- `animate` is always auto-requested on public pages (used on profile),
  but silently skipped if you haven't uploaded the Pro animate module
- Admin pages have no automatic modules — declare everything explicitly

### Available Modules (from MDB Pro 6.1.0)
Upload from `MDB5-STANDARD-UI-KIT-Pro-Advanced-6.1.0/css/modules/` and `js/modules/`
into `assets/mdb-pro/modules/`:

| Module name | CSS file | JS file | Use for |
|---|---|---|---|
| `animate` | ✅ | ✅ | Scroll/click/hover animations |
| `perfect-scrollbar` | ✅ | ✅ | Custom styled scrollbars |
| `datatable` | ✅ | ✅ | Sortable/searchable/paginated tables |
| `datepicker` | ✅ | ✅ | Date picker input |
| `timepicker` | ✅ | ✅ | Time picker input |
| `date-time-picker` | ✅ | ✅ | Combined date + time |
| `autocomplete` | ✅ | ✅ | Search-as-you-type input |
| `select` | ✅ | ✅ | Custom styled select dropdown |
| `lightbox` | ✅ | ✅ | Image gallery lightbox |
| `sidenav` | ✅ | ✅ | Slide-in side navigation |
| `stepper` | ✅ | ✅ | Multi-step form wizard |
| `rating` | ✅ | ✅ | Star rating input |
| `sticky` | ✅ | ✅ | Sticky elements on scroll |
| `navbar` | ✅ | ✅ | Scroll-aware navbar behaviour |
| `modal` | ✅ | ✅ | Enhanced modals |
| `chips` | ✅ | ✅ | Tag / chip inputs |
| `loading-management` | — | ✅ | Loading overlays / spinners |
| `smooth-scroll` | — | ✅ | Smooth anchor scrolling |
| `lazy-load` | — | ✅ | Lazy image loading |
| `infinite-scroll` | — | ✅ | Infinite scroll feed |
| `toast` | — | ✅ | Toast notifications |
| `perfect-scrollbar` | ✅ | ✅ | Custom scrollbars |
| `clipboard` | — | ✅ | Copy to clipboard |
| `touch` | — | ✅ | Touch / swipe gesture support |
| `charts` | — | ✅ | Chart.js Pro wrapper |

### Animation Usage (confirmed from Pro 6.1.0 source)
```html
<div data-mdb-toggle="animation"
     data-mdb-animation="fade-in-up"
     data-mdb-animation-start="onScroll"
     data-mdb-animation-on-scroll="once"
     data-mdb-animation-duration="500"
     data-mdb-animation-delay="100">
  Animated content
</div>
```

**`data-mdb-animation-start` options:** `onClick` · `onScroll` · `onLoad` · `onHover`
**`data-mdb-animation-on-scroll` options:** `once` · `repeat`

**All animation names:**
```
fade-in-up / fade-in-down / fade-in-left / fade-in-right
fade-out-up / fade-out-down / fade-out-left / fade-out-right
fly-in / fly-in-up / fly-in-down / fly-in-left / fly-in-right
slide-in-up / slide-in-down / slide-in-left / slide-in-right
zoom-in / zoom-out / drop-in / drop-out
flash / glow / jiggle / pulse / shake / tada
```

### Datatable Example
```php
<?php
$proModules = ['datatable', 'perfect-scrollbar'];
require_once __DIR__ . '/../layout_header.php';
?>

<div id="myTable" class="datatable"></div>

<script>
new mdb.Datatable(document.getElementById('myTable'), {
    columns: [
        { label: 'Name',  field: 'name',  sort: true },
        { label: 'Email', field: 'email', sort: false },
    ],
    rows: <?= json_encode($yourPhpDataArray) ?>,
    pagination: true,
    entries:    10,
    hover:      true,
    fixedHeader: true,
});
</script>
```

### Perfect Scrollbar Example
```html
<div data-mdb-perfect-scrollbar="true" style="height:400px; overflow:hidden;">
    Very long content...
</div>
```

### Smooth Scroll Example
```html
<a href="#section1" data-mdb-smooth-scroll="smooth-scroll"
   data-mdb-offset="80">Jump to section</a>
```
`data-mdb-offset="80"` accounts for the fixed navbar height.

### Lazy Load Images
```html
<img data-mdb-lazy-src="actual-image.jpg"
     data-mdb-lazy-animation="fade-in"
     data-mdb-lazy-delay="300"
     src="placeholder.jpg" alt="">

<script>
document.querySelectorAll('[data-mdb-lazy-src]').forEach(img => new mdb.LazyLoad(img));
</script>
```

### Toast Notification
```html
<div id="myToast" class="toast" role="alert">
    <div class="toast-header">
        <strong class="me-auto">Saved</strong>
        <button type="button" class="btn-close" data-mdb-dismiss="toast"></button>
    </div>
    <div class="toast-body">Changes saved successfully.</div>
</div>

<script>
new mdb.Toast(document.getElementById('myToast'), {
    autohide: true,
    delay:    3000,
    position: 'top-right',
    stacking: true,
}).show();
</script>
```

### Loading Overlay
```js
// Full page loader — no extra module needed (built into mdb.min.js)
const loader = new mdb.Loading(document.getElementById('pageLoader'), {
    backdrop:        true,
    backdropOpacity: 0.4,
    scroll:          false,
});
loader.show();
// ...after async work:
loader.hide();
```

---

## CSS Variable Reference

All theme values are injected as CSS custom properties in both layout headers.
Use them in any custom CSS to stay consistent with the admin-controlled theme.

```css
var(--cms-primary)       /* Primary brand color */
var(--cms-primary-dark)  /* Darkened primary (hover states) */
var(--cms-accent)        /* Accent/gradient color */
var(--cms-ink)           /* Heading text color */
var(--cms-body)          /* Body text color */
var(--cms-bg)            /* Page background */
var(--cms-surface)       /* Card / input background */
var(--cms-radius)        /* Border radius (px) */
var(--cms-font-display)  /* Heading font stack */
var(--cms-font-body)     /* Body font stack */
var(--cms-border)        /* Border color */
var(--cms-muted)         /* Muted / placeholder text */
var(--cms-shadow)        /* Default box shadow */
var(--cms-shadow-lg)     /* Large box shadow */
```

---

## Security Model

| Threat | Defence |
|---|---|
| SQL injection | PDO prepared statements, `EMULATE_PREPARES = false` |
| XSS | `e()` on all output — `htmlspecialchars(ENT_QUOTES)` |
| CSRF | `csrfField()` + `verifyCsrf()` on every POST form |
| File upload abuse | `finfo` MIME check + extension whitelist + sanitized filename |
| Directory traversal | All filenames replaced with `user_ID_timestamp.ext` |
| Session fixation | `session_regenerate_id(true)` on login |
| Cookie theft | `httponly`, `samesite=Lax`, optional `secure` flag |
| Admin self-delete | Blocked in both individual and bulk delete |
| Superadmin exposure | Filtered from every query, direct URLs blocked for regular admins |
| SMTP password exposure | Never rendered in HTML — not even as an input value |
| Sensitive file access | `.htaccess` blocks direct access to `config.php`, `db.php`, `helpers.php`, `mailer.php`, `*.sql`, `*.md`, `*.log` |
| Secret tool discovery | `_account_recovery.php` returns identical 404 without the correct key |

---

## Constants Reference (`app-defaults.php`)

| Constant | Default | Override in |
|---|---|---|
| `APP_NAME` | `'Virtual Visiting Card'` | `config.php` |
| `APP_DESCRIPTION` | `'Create and share...'` | `config.php` |
| `APP_DEBUG` | `true` | `config.php` — **set false in production** |
| `SESSION_LIFETIME` | `0` (until browser closes) | `config.php` |
| `UPLOAD_DIR` | `/path/to/vvcard/uploads/profiles/` | `config.php` |
| `UPLOAD_URL` | `BASE_URL . 'uploads/profiles/'` | `config.php` |
| `MAX_UPLOAD_SIZE` | `2 * 1024 * 1024` (2 MB) | `config.php` |
| `ALLOWED_EXT` | `['jpg','jpeg','png','gif']` | `config.php` |
| `ALLOWED_MIME` | `['image/jpeg','image/png','image/gif']` | `config.php` |
| `DEFAULT_AVATAR` | `'default-avatar.png'` | `config.php` |
| `UPLOAD_DIR_PAGES` | `/path/to/vvcard/uploads/pages/` | `config.php` |
| `UPLOAD_URL_PAGES` | `BASE_URL . 'uploads/pages/'` | `config.php` |
| `SUPERADMIN_SETUP_KEY` | *(undefined)* | `config.php` — **define to activate recovery tool** |
