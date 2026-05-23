# True Random Post Widget

A lightweight WordPress plugin that displays a truly random post from your entire database on every page refresh—not just recent posts.

## Quick Start

1. **Upload** the `true-random-post-widget` folder to `/wp-content/plugins/`
2. **Activate** in WordPress Plugins admin page
3. **Use** the shortcode: `[true_random_post]`

## Key Features

- ✅ **True randomization** across entire post database
- ✅ **No recency bias** — older posts get equal exposure
- ✅ **Lightweight** — 2 efficient database queries
- ✅ **Flexible** — Control what displays (title, image, excerpt)
- ✅ **Customizable** — CSS classes for custom styling
- ✅ **No settings page** — Keep it simple

## Usage

Basic shortcode:
```
[true_random_post]
```

With options:
```
[true_random_post post_type="post" show_image="true" show_excerpt="true"]
```

See **TRUE_RANDOM_POST_WIDGET_GUIDE.md** for full documentation.

## Why This Plugin?

Most random post solutions only randomize between the most recent posts. If you have 5,000 posts, they might only check 100 of them.

This plugin uses proper statistical randomization to give every post an equal chance of being displayed, regardless of publication date.

## Installation Locations

### Standard Plugin
- Extract to: `/wp-content/plugins/true-random-post-widget/`
- Then activate via Plugins page

### Must-Use Plugin (always active)
- Place `true-random-post-widget.php` in `/wp-content/mu-plugins/`
- Create `/wp-content/mu-plugins/assets/` folder
- Place `style.css` there

## Requirements

- WordPress 5.0+
- PHP 7.2+

## License

GPL v3 - See LICENSE file

## Support

For issues:
1. Verify plugin folder contains both `true-random-post-widget.php` and `assets/style.css`
2. Check that you have published posts
3. Look in `/wp-content/debug.log` for errors