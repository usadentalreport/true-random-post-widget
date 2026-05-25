# True Random Post Widget

A lightweight WordPress plugin that displays a truly random post from your entire database on every page refresh—not just recent posts. Includes a rich card layout with a featured image, excerpt, taxonomy footer label, and a customizable CTA button.

**Current version: 2.3.0**

---

## Quick Start

1. **Upload** the `true-random-post-widget` folder to `/wp-content/plugins/`
2. **Activate** in the WordPress Plugins admin page
3. **Configure** at **Settings → True Random Post**
4. **Use** the shortcode: `[true_random_post]`

---

## Key Features

- ✅ **True randomization** across the entire post database
- ✅ **No recency bias** — older posts get equal exposure
- ✅ **Publish-date filter** — optionally limit results to posts from the last 14–365 days
- ✅ **Card layout** — image, title, excerpt, and footer with taxonomy term + button
- ✅ **Flexible image source** — use each post's featured image or one global image
- ✅ **Taxonomy footer** — display a term name and circular logo in the card footer
- ✅ **SCF / ACF term logos** — configure a custom image field key for term logos
- ✅ **Customizable button** — set text, background color, and text color in admin
- ✅ **Excerpt fallback** — uses trimmed post content when no excerpt is set
- ✅ **Lightweight** — 2 efficient database queries, no external dependencies

---

## Admin Settings

Navigate to **Settings → True Random Post** to configure:

### Image Settings

| Option | Description |
|---|---|
| **Use post featured image** | Each card shows the featured image of the randomly selected post. |
| **Use a global image** | Every card shows the same image chosen from the WP media library. |

### Display Settings

| Option | Description |
|---|---|
| **Footer Taxonomy** | Choose any public taxonomy (e.g. *Category*, *Post Tag*, or a custom one). The first term assigned to the post is shown in the card footer, along with its logo if one has been set. |
| **Term Logo Field Key** | The SCF / ACF field name of an Image field attached to your chosen taxonomy. The plugin calls `get_field()` with this key on each term to retrieve the logo. Falls back to plain term meta if SCF / ACF is not active. |
| **Post Date Filter** | Limit the pool of eligible posts to those published within a rolling window: last 14, 30, 60, 90, 180, or 365 days. Defaults to **All time** (no filter). |

### Button Settings

| Option | Description |
|---|---|
| **Button Text** | Label shown on the CTA button (default: `Listen`). |
| **Button Background Color** | Picked via the WordPress color picker. |
| **Button Text Color** | Picked via the WordPress color picker. |

---

## Taxonomy Term Logos

Term logos are managed via **Secure Custom Fields (SCF)** or **Advanced Custom Fields (ACF)**:

1. Install and activate SCF or ACF.
2. Create a **Field Group** targeting your chosen taxonomy with an **Image** field.
3. Note the field name (e.g. `term_logo`).
4. Enter that field name in **Settings → True Random Post → Term Logo Field Key**.
5. Edit any term in that taxonomy and populate the image field.

The logo is displayed as a 48 × 48 px image in the card footer next to the term name.

> **Without SCF / ACF:** the field key is treated as a plain term meta key read via `get_term_meta()`.

---

## Shortcode

Basic usage — all display settings come from the admin panel:

```
[true_random_post]
```

Optional shortcode attributes:

| Attribute | Default | Description |
|---|---|---|
| `post_type` | `post` | Post type to pull from (e.g. `page`, `podcast`). |
| `image_required` | `false` | Set `true` to skip posts without a featured image. |
| `class` | _(empty)_ | Extra CSS class added to the wrapper element. |
| `date_range` | _(admin setting)_ | Days to look back from today. `0` = all time. Overrides the global admin setting for this instance only. |

Example:

```
[true_random_post post_type="podcast" image_required="true" class="my-widget"]
```

Limit to posts published in the last 30 days:

```
[true_random_post date_range="30"]
```

Force all-time on a specific instance even when the admin default is set to a window:

```
[true_random_post date_range="0"]
```

---

## Card HTML Structure

```html
<div class="true-random-post-widget [custom-class]">

  <!-- Hero image (global or post featured) -->
  <div class="true-random-post-widget__image">
    <img class="true-random-post-widget__img" … />
  </div>

  <!-- Title + excerpt -->
  <div class="true-random-post-widget__body">
    <h3 class="true-random-post-widget__title">Post Title</h3>
    <p  class="true-random-post-widget__excerpt">Excerpt or trimmed content…</p>
  </div>

  <!-- Footer: taxonomy term + CTA button -->
  <div class="true-random-post-widget__footer">
    <div class="true-random-post-widget__term">
      <span class="true-random-post-widget__term-logo"> <!-- term logo image --> </span>
      <span class="true-random-post-widget__term-name">Term Name</span>
    </div>
    <a class="true-random-post-widget__button" style="background-color:…;color:…;">Listen</a>
  </div>

</div>
```

---

## Installation Locations

### Standard Plugin
- Extract to `/wp-content/plugins/true-random-post-widget/`
- Activate via the Plugins page

### Must-Use Plugin (always active)
- Place `true-random-post-widget.php` in `/wp-content/mu-plugins/`
- Create `/wp-content/mu-plugins/assets/` and place `style.css` there
- Note: the admin settings page and `admin.js` will not load automatically in MU mode without additional adjustments

---

## Requirements

- WordPress 5.0+
- PHP 7.2+

---

## Changelog

### 2.3.0
- New **Post Date Filter** admin setting (Display Settings section)
- Filter options: All time, last 14 / 30 / 60 / 90 / 180 / 365 days
- Date filter applies to both the count query and the fetch query, preserving true randomization within the selected window
- New `date_range` shortcode attribute to override the global setting per-instance (e.g. `[true_random_post date_range="30"]`; `date_range="0"` forces all-time)

### 2.2.0
- Replaced built-in term logo field on taxonomy term edit screens with SCF / ACF integration
- New **Term Logo Field Key** admin setting — enter the SCF / ACF image field name for the chosen taxonomy
- Plugin calls `get_field()` when SCF / ACF is active; falls back to `get_term_meta()` otherwise
- Version bump to 2.2.0

### 2.1.0
- Version bump to 2.1.0

### 2.0.0
- Complete redesign of the shortcode card output (image → title → excerpt → footer)
- New **admin settings page** (Settings → True Random Post)
- Image source toggle: post featured image vs. global image (WP media library)
- Taxonomy footer: term name + circular logo from any public taxonomy
- Taxonomy term logo field on term edit screens (stored as term meta)
- Customizable button: text, background color, and text color via WP color picker
- Excerpt falls back to trimmed `post_content` when `post_excerpt` is empty
- New `assets/admin.js` for media library picker, color picker, and radio toggle
- Redesigned `assets/style.css` with aspect-ratio image, flex footer, and responsive collapse

### 1.0.0
- Initial release
- True random post selection via SQL offset
- Shortcode `[true_random_post]` with title, image, and excerpt display options

---

## License

GPL v3 — see LICENSE file

---

## Support

For issues:
1. Confirm the plugin folder contains `true-random-post-widget.php`, `assets/style.css`, and `assets/admin.js`
2. Check that you have published posts of the chosen post type
3. If using the date filter, verify posts exist within the selected window
4. Review `/wp-content/debug.log` for PHP errors
