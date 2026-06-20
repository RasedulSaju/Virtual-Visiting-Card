# Admin Panel Guide

Access the admin panel at `yourdomain.com/vvcard/admin/`  
Only users with `role = 'admin'` can access it.

---

## Dashboard

**URL:** `/admin/`

Overview of the site at a glance:

| Card | Shows |
|---|---|
| Total Users | Count of all registered accounts |
| Total Pages | Count of all CMS pages |
| Active Fields | Count of active custom profile fields |
| New (7 days) | Users registered in the last week |

**Quick actions** — buttons for common tasks:
- New User · New Page · Send Invite · Add Profile Field

**Recent tables** — last 6 users and last 6 pages with direct edit links.

---

## Users

**URL:** `/admin/users/`

### User List
- Search by username or email
- Paginated (15 per page)
- Badges show role (`admin` / `user`) and profile editing status
- **Bulk actions** — select multiple users with checkboxes:
  - Enable profile editing
  - Disable profile editing
  - Set role → User
  - Delete selected
- You cannot select or delete your own logged-in account

### Create User
**URL:** `/admin/users/create.php`

| Field | Notes |
|---|---|
| Username | 3–50 chars, letters/numbers/underscores only. Becomes the profile URL slug |
| Email | Unique across all users |
| Password | Minimum 8 characters, bcrypt cost-12 hashed |
| Role | `user` (default) or `admin` |
| Allow profile editing | Toggle — controls whether user can edit their own profile |
| Bio | Optional introductory text shown on profile |
| Profile Picture | JPG/PNG/GIF, max 2 MB. Automatically renamed to `user_ID_timestamp.ext` |

### Edit User
**URL:** `/admin/users/edit.php?id=N`

Same fields as Create, plus:
- **New Password** — leave blank to keep existing password
- **Remove current image** — checkbox to reset to default avatar
- **Search Visibility** — per-profile `meta_robots` control
- Cannot change your own role (protection against accidental self-demotion)

### Delete User
- Available from the list page (individual) and via bulk actions
- Cascade-deletes all `user_field_values` for that user
- Uploaded profile image is also deleted from disk
- Cannot delete your own account

---

## Pages

**URL:** `/admin/pages/`

CMS pages are accessible at `domain.com/slug` (e.g. `domain.com/about-us`).

### Page List
Shows all pages with nav visibility badge and new SEO status badge (`indexed` / `noindex`).

### Create Page
**URL:** `/admin/pages/create.php`

| Field | Notes |
|---|---|
| Title | Display name of the page |
| Slug | URL-safe identifier (auto-generated from title, can be overridden). E.g. `about-us` → `domain.com/about-us` |
| Content | Raw HTML textarea. Supports any HTML, inline CSS, embedded images |
| Show in navigation | Toggle — whether this page appears in the navbar |
| Nav Order | Lower number = appears first in navbar |
| SEO Visibility | `noindex`/`nofollow` control — see [Configuration Reference](configuration.md) |

**Reserved slugs** (cannot be used as page slugs):
`login`, `logout`, `register`, `forgot-password`, `reset-password`, `edit-profile`, `change-password`, `admin`, `setup`, `members`, `sitemap.xml`, `robots.txt`, `404`

### Edit Page
Same fields as Create. Changing a slug updates the live URL — make sure to update any existing links.

### Delete Page
Permanent. Removes the page and all associated data.

---

## Profile Fields

**URL:** `/admin/fields/`

Custom fields that appear on **every** user profile. Users fill in their own values on their Edit Profile page.

### Field List
Shows all fields with type, icon, status, and sort order. Inactive fields are hidden from public profiles but their values are preserved.

### Create Field
**URL:** `/admin/fields/create.php`

| Field | Notes |
|---|---|
| Display Label | Shown on user profiles, e.g. `Twitter Handle` |
| Machine Name | Unique key, lowercase + underscores, e.g. `twitter_handle` (auto-generated from label) |
| Field Type | `text` = single line · `url` = validated URL with external link · `textarea` = multiline |
| Font Awesome Icon | Any FA class, e.g. `fab fa-twitter`, `fas fa-globe`, `fas fa-map-marker-alt` |
| Sort Order | Lower = appears first on profiles |
| Active | Inactive fields are hidden from public view |

**Live preview** updates as you type, showing exactly how the field will look on a profile.

### Edit Field
Same as Create. Changing the machine name doesn't affect existing stored values.

### Delete Field
**Warning:** Permanently deletes the field AND all user values for that field (cascaded via foreign key). This cannot be undone.

---

## Navigation Menu

**URL:** `/admin/nav/`

Controls which pages appear in the public navbar and in what order.

- **Drag rows** to reorder (updates order inputs automatically)
- **Show in Nav toggle** — per-page visibility switch
- **Order input** — manual number input for precise ordering
- **Live preview** panel updates as you toggle pages
- Save with the **Save Navigation** button

> Pages can also be toggled individually from the Pages edit form.

---

## Invitations

**URL:** `/admin/invitations/`

Send signed registration links that work even when public registration is closed.

### Send Invitation
1. Enter the recipient's email address
2. Click **Generate Invite Link**
3. If PHPMailer is configured, the email is sent automatically
4. If not configured (dev mode), the link is shown on screen — copy and send manually
5. Invite links expire in **48 hours** and are **single-use**

### Invitation List
Shows all invitations with status:
- **Pending** — not yet used, not expired
- **Used** — registration completed
- **Expired** — time limit passed

Pending invitations can be **Revoked** (deleted before use).

---

## Settings

**URL:** `/admin/settings/`

Five tabs — each saves independently.

### General Tab
- **Site Name** — shown in navbar, emails, browser tabs, OG tags
- **Site Description** — `<meta name="description">` and Open Graph description
- **Public Registration** — open/closed toggle

### SMTP Tab
Configure outgoing email for password resets and invitations.
See [Configuration Reference — SMTP](configuration.md#smtp-tab) for provider details.

**Test Email** — sends a test message to verify your SMTP config is working.

> If PHPMailer is not installed, a warning banner appears with a direct link to the installer.

### Appearance Tab
Live theme customization — changes preview in real time:
- **6 color pickers** — primary, accent, headings, body text, background, card surface
- **Heading font** — any Google Font name (e.g. `Poppins`, `Playfair Display`)
- **Body font** — Google Font or CSS value (e.g. `system-ui`, `Georgia`)
- **Corner roundness** — slider from 0 (sharp) to 24 (very rounded)
- **Animations** — toggle all CSS transitions/animations site-wide
- **Reset to Defaults** — restores original indigo/violet theme

### SEO Tab
- **Global noindex** — hides the entire site from search engines when ON (useful during development)
- **Custom robots.txt** — fully override the auto-generated robots.txt (leave empty for default)
- **Live preview** — shows what `/robots.txt` will output
- **Sitemap link** — direct link to view the live `sitemap.xml`

### Analytics Tab
Enter tracking IDs for analytics platforms.
See [Configuration Reference — Analytics](configuration.md#analytics-tab) for all options.

---

## Privacy & Visibility Controls

### Private Profile Fields
When creating or editing a Profile Field (**Admin → Profile Fields**), toggle **Public** off to make that field private. Private fields are only visible to:
- The profile owner (when logged in)
- Admins and superadmins

Everyone else viewing the public profile will not see that field at all. Useful for sensitive data like Date of Birth, phone numbers, or internal notes. A 🔒 icon marks private fields throughout the admin panel.

Field types now include **Date** (in addition to Text, URL, Textarea) — useful for birthdays, anniversaries, etc.

### Account Status — Resigned Watermark
In **Admin → Users → Edit**, set **Account Status** to **Resigned**. This:
- Displays a diagonal "RESIGNED" watermark stamp across their public profile page
- Shows a notice banner explaining the person is no longer associated with the site
- Hides their bio and custom field details from public visitors (admins and the user themselves can still see everything)
- Automatically excludes them from the Members directory
- Greys out their profile card slightly

This is useful for former employees/members whose profile link may still be shared but shouldn't appear active.

### Members Directory Visibility
Each user has a **Show in Members Directory** toggle in **Admin → Users → Edit**, independent from SEO settings:
- **ON** (default) — appears in `/members` listing
- **OFF** — hidden from `/members`, but their direct profile URL (`domain.com/username`) still works normally

This is different from the **Search Engine Visibility** (`meta_robots`) setting, which controls whether search engines index the page — not whether it shows in your own members list.

### Superadmin Role
A third role tier above Admin. Superadmin accounts are **completely invisible to regular admins**:
- Excluded from the Users list, dashboard recent users, and bulk action targets
- Direct URL access to edit/delete a superadmin is blocked for regular admins (returns "User not found")
- Only an existing superadmin can promote another account to superadmin (the role option only appears in the dropdown when you're logged in as superadmin)

**Creating the first superadmin:** Since no superadmin exists by default, promote an existing admin account directly in the database:
```sql
UPDATE users SET role = 'superadmin' WHERE username = 'your_admin_username';
```
After this, log out and back in — the Superadmin role option will appear in the Users → Edit dropdown for that account, and they'll be able to manage other superadmin accounts from the UI.

---

## Security Notes

- Admin panel pages always serve `<meta name="robots" content="noindex, nofollow">` — they are not indexed
- Sessions are regenerated on login (prevents session fixation)
- All forms include CSRF tokens
- Admin cannot delete their own account
- Admin cannot demote their own role
