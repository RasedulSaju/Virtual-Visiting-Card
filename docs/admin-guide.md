# Admin Panel Guide

Access the admin panel at `yourdomain.com/admin/`
Only accounts with `role = 'admin'` or `role = 'superadmin'` can access it.

---

## Day-to-Day Operations

### Setting Up a Fresh Install

1. Import `install.sql` → Edit `config.php` → Visit `/setup.php` → Create admin → Delete `setup.php`
2. Log in at `/login` → you are redirected to `/admin/`
3. Go to **Settings → General** → Set your Site Name and Description
4. Go to **Settings → Appearance** → Set your brand colours, font, corner radius
5. Go to **Settings → SMTP** → Configure email (needed for password resets and invitations)
6. Go to **Profile Fields** → Create the custom fields you want on member profiles
7. Go to **Navigation** → Add any CMS pages you've created to the navbar
8. Done — share your site URL

---

### Common Admin Workflows

#### Adding a New User
1. **Admin → Users → New User**
2. Fill in username, email, password
3. Choose role: `User` (public member) or `Admin` (panel access)
4. Toggle **Allow profile editing** ON if they should manage their own profile
5. Toggle **Show in Members directory** ON/OFF as needed
6. Set **Search Engine Visibility** (default: Indexed)
7. Click **Create User**

The user's profile is immediately live at `domain.com/username`.

#### Inviting Someone (Registration Closed)
1. **Admin → Invitations → Enter email → Generate**
2. If SMTP is configured → email sends automatically
3. If SMTP is not configured → copy the link shown on screen and send manually
4. The link expires in 48 hours and can only be used once
5. Revoke unused invitations from the list if needed

#### Marking a User as Resigned
1. **Admin → Users → Edit** the user
2. Set **Account Status → Resigned**
3. Save → their public profile now shows a diagonal "RESIGNED" watermark
4. They are automatically removed from the Members directory
5. Their profile URL still works — it just shows the watermark and hides details

#### Hiding a User from Members Directory
1. **Admin → Users → Edit** the user
2. Toggle **Show in Members Directory** → OFF
3. Save → they disappear from `/members` but their direct URL still works

This is different from SEO noindex — this only hides them from your own members list, not from Google.

#### Creating a CMS Page
1. **Admin → Pages → Create Page**
2. Write a title — the slug auto-generates (e.g. `about-us`)
3. Write content in the HTML textarea. Use the toolbar for:
   - **B** Bold · *I* Italic · 🔗 Link · H Heading · ≡ List
   - 📷 **Image** — click to upload an image and insert it inline
4. Set **Navigation** → toggle ON + set order if you want it in the navbar
5. Set **SEO** visibility (default: Indexed)
6. Click **Save Page** → live at `domain.com/slug`

#### Editing the Navigation Menu
1. **Admin → Navigation**
2. Pages marked "Show in Nav" appear here
3. Drag rows to reorder
4. Toggle visibility per page
5. See live preview on the right as you make changes
6. Click **Save Navigation**

#### Managing Profile Fields
Fields appear on every user's profile. Users fill them in from their Edit Profile page.

**To add a private field (e.g. Date of Birth):**
1. **Admin → Profile Fields → Create Field**
2. Label: `Date of Birth`, Type: `Date`, Icon: `fas fa-birthday-cake`
3. Toggle **Public → OFF** → this field is only visible to the owner and admins
4. Save

**To add a social link field:**
1. Type: `URL`, Icon: `fab fa-linkedin-in`
2. Public: ON → visible on public profiles
3. Users with a value see a clickable external link on their profile

#### Changing Theme / Branding
1. **Admin → Settings → Appearance**
2. Use the **colour pickers** — 6 colours (primary, accent, headings, body, background, cards)
3. Adjust **Corner Roundness** with the slider (0 = sharp, 24 = very rounded)
4. Set **Heading Font** — any Google Font name (e.g. `Poppins`, `Playfair Display`)
5. Toggle **Animations** on/off
6. Watch the **live preview** update as you change things
7. Click **Save Appearance** → changes are live immediately site-wide
8. Changed your mind? → **Reset to Defaults**

#### Configuring Analytics
1. **Admin → Settings → Analytics**
2. Enter your tracking ID(s) — GA4, GTM, Clarity, Meta Pixel, Hotjar, Plausible
3. **Note:** If you enter both GA4 and GTM IDs, GA4 is suppressed — configure GA4 **inside** GTM instead
4. Use **Custom Head Code** for any script tag that doesn't have its own field
5. Save → scripts are injected only on public pages, never in the admin panel

#### Managing SEO
**Per page or per user:**
- Edit any page or user → find the **SEO / Search Visibility** selector
- `Indexed` (default) → appears in Google, included in sitemap
- `Noindex` → hidden from Google, excluded from sitemap

**Site-wide:**
- **Admin → Settings → SEO**
- Enable **Hide entire site** → sends noindex on everything, empties sitemap, blocks robots.txt
  (useful during development / staging)
- Customise **robots.txt** → leave blank for the auto-generated default

#### SMTP / Email Setup
1. **Admin → Settings → SMTP**
2. Fill in Host, Port, Username, Password, Encryption, From Email, From Name
3. If SMTP is configured, a diagnostic panel shows which requirement is failing
4. Click **Send Test Email** → sends a test to any address you enter
5. Once working: password resets and invitations send via email automatically
6. When not configured: reset links and invite links are shown on-screen (dev mode)

---

## Users Section

### User Roles

| Role | What they can do |
|---|---|
| `user` | Public profile, edit own profile (if allowed), change password |
| `admin` | Full admin panel access, manage users/pages/fields/settings |
| `superadmin` | Same as admin + invisible to regular admins + can promote others |

### Bulk Actions
Select multiple users with the checkboxes → choose an action:
- **Enable/Disable profile editing** — lock or unlock self-editing for a group
- **Set role → User** — demote a batch of admins to user
- **Delete** — permanently delete with confirmation

You cannot select your own account. Superadmin accounts are invisible to regular admins even via bulk actions.

### Superadmin Access
Superadmins are completely invisible in the admin panel for regular admins — they don't appear in any list, stat, or dropdown. Only another superadmin can see or manage them.

**To create the first superadmin:**
1. Add to your `config.php`: `define('SUPERADMIN_SETUP_KEY', 'your-long-secret-key');`
2. Visit: `yourdomain.com/_account_recovery.php?key=your-long-secret-key`
3. Enter the username to promote → click **Promote**
4. Log out and back in — the Superadmin role is now active

The tool returns a normal 404 for anyone without the correct key.

---

## Pages Section

### Reserved Slugs
These slugs are used by system routes and **cannot** be used for CMS pages:
```
login, logout, register, forgot-password, reset-password,
edit-profile, change-password, members, sitemap.xml, robots.txt
```

### Page Image Uploads
The page editor has an **Image** button in the toolbar:
1. Click **Image** → an upload panel appears above the textarea
2. Select a JPG/PNG/GIF → click **Upload & Insert**
3. The image is saved to `uploads/pages/` and an `<img>` tag is inserted at the cursor
4. Previously uploaded images appear as thumbnails — click any to re-insert
5. The upload limit follows **Admin → Settings → General → File Upload Limit**

---

## Settings Reference

### General Tab
| Setting | What it does |
|---|---|
| Site Name | Shown in navbar, browser tabs, emails, OG tags |
| Site Description | `<meta name="description">` and Open Graph description |
| Public Registration | Open / Closed (invited users can always register) |
| File Upload Limit | 1–20 MB per image upload (cannot exceed PHP server limits) |

### Appearance Tab
| Setting | What it does |
|---|---|
| Primary Color | Buttons, links, active nav items |
| Accent Color | Gradients, card banners, highlights |
| Heading Color | h1–h6 text |
| Body Text | Paragraphs, labels |
| Page Background | Behind cards |
| Card Surface | Cards, navbar, input backgrounds |
| Corner Roundness | 0px (sharp) to 24px (rounded) — applied to cards, buttons, badges |
| Heading Font | Any Google Font name |
| Body Font | Google Font or CSS value (e.g. `system-ui`, `Georgia`) |
| Animations | Disables all CSS transitions/animations sitewide when off |

### SMTP Tab
| Field | Notes |
|---|---|
| Host | e.g. `smtp.gmail.com` |
| Port | `587` (TLS) or `465` (SSL) |
| Username | Your SMTP account email/username |
| Password | Never shown in the page source — only a "Set/Not set" badge |
| Encryption | TLS, SSL, or None |
| From Email | The sender address recipients see |
| From Name | The sender name recipients see |

### SEO Tab
| Setting | What it does |
|---|---|
| Hide entire site | Sends `noindex,nofollow` everywhere, empties sitemap, blocks robots.txt |
| Custom robots.txt | Full override of auto-generated robots.txt |

---

## Privacy & Visibility Quick Reference

| Goal | Where to set it |
|---|---|
| Hide field from public (keep private) | Profile Fields → Edit → Public → OFF |
| Show resigned watermark on profile | Users → Edit → Account Status → Resigned |
| Remove from Members directory only | Users → Edit → Show in Members Directory → OFF |
| Hide from Google (profile) | Users → Edit → Search Engine Visibility → Noindex |
| Hide from Google (page) | Pages → Edit → SEO → Noindex |
| Hide entire site from Google | Settings → SEO → Hide entire site |

---

## Security Notes

- Admin panel pages always serve `noindex, nofollow` — never indexed
- Sessions are regenerated on login (prevents session fixation)
- All forms include CSRF tokens
- Admin cannot delete or demote their own account
- Superadmin accounts are protected from regular admin actions even via tampered URLs
- SMTP password is never rendered in page HTML — inspect-safe
