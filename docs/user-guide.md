# User Guide

---

## Public Features (No Account Required)

### Viewing Profiles
Every registered user has a public profile at:
```
https://yourdomain.com/username
```
Profiles show the user's avatar, bio, and any custom fields they've filled in (social links, location, etc.).

### Members Directory
Browse all registered users at:
```
https://yourdomain.com/members
```
- Search by username
- Paginated grid of profile cards
- Click any card to view the full profile

### Static Pages
Admin-created pages are accessible at their slug:
```
https://yourdomain.com/about-us
https://yourdomain.com/contact
```
Pages marked "Show in Navigation" appear in the top navbar automatically.

---

## Creating an Account

### Public Registration
If registration is open, visit:
```
https://yourdomain.com/register
```

| Field | Rules |
|---|---|
| Username | 3–50 characters · letters, numbers, underscores only · becomes your profile URL |
| Email | Valid email address, unique |
| Password | Minimum 8 characters |

> **Username tip:** Choose carefully — your username becomes your permanent profile URL (`domain.com/yourusername`). It can be changed by an admin if needed.

### Invite-Only Registration
If registration is closed, you need an invitation link from an admin. The link looks like:
```
https://yourdomain.com/register?invite=TOKEN
```
- Your email is pre-filled and locked
- The link expires in 48 hours
- Each link can only be used once

---

## Logging In

Visit `https://yourdomain.com/login`

- Enter your **username or email** and password
- Admins are redirected to `/admin/` after login
- Regular users are redirected to their profile page

---

## Forgot Password

1. Visit `https://yourdomain.com/forgot-password`
2. Enter your registered email address
3. If SMTP is configured, a reset link is sent to your email
4. Click the link in the email (valid for **1 hour**)
5. Enter and confirm your new password

> On development/staging installations without SMTP configured, the reset link is displayed on screen.

---

## Editing Your Profile

Visit `https://yourdomain.com/edit-profile` (must be logged in)

> **Note:** Profile editing must be enabled for your account by an admin. If it's disabled, you'll see a "Editing Disabled" message. Contact your administrator.

### What you can edit:

**Profile Picture**
- Accepted formats: JPG, PNG, GIF
- Maximum size: 2 MB
- Preview updates live before saving
- Click "Remove current image" (if available) to reset to the default avatar

**Bio**
- Free-form text about yourself
- Shown below your avatar on your profile

**Custom Fields**
- Filled in by you, defined by the admin
- May include: Website, Twitter, LinkedIn, GitHub, Location, etc.
- URL fields validate the format and display as clickable external links
- Textarea fields support multi-line text

---

## Changing Your Password

Visit `https://yourdomain.com/change-password` (must be logged in)

1. Enter your **current password** to verify it's you
2. Enter a **new password** (minimum 8 characters)
3. Confirm the new password
4. Click **Update Password**

> The strength indicator shows Weak / Fair / Good / Strong as you type.

---

## Signing Out

- Click your **username/avatar** in the top-right navbar → **Sign Out**
- Or click the **Sign Out** button directly (visible on mobile)
- Or visit `https://yourdomain.com/logout` directly

---

## Your Profile URL

Your profile is always accessible at:
```
https://yourdomain.com/YOUR_USERNAME
```

Share this URL as your digital visiting card. The page includes:
- Open Graph and Twitter Card meta tags for rich social previews
- Your full profile information visible to anyone

---

## Navigation

The top navbar contains:
- **Site logo/name** — links to homepage
- **Dynamic page links** — CMS pages marked "Show in Navigation"
- **Members** — link to the members directory
- **Your username/avatar** dropdown — profile links and sign out (desktop)
- **Sign Out button** — directly visible on mobile

The footer contains:
- Links to all navigation pages
- Account links (context-aware — shows login/signup when logged out, profile/edit/sign out when logged in)
- Sitemap link
