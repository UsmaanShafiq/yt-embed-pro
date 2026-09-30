# YT Embed Pro

A WordPress plugin that shows YouTube Shorts and videos from any channel on your site. It has four layouts, a Shorts/Videos tab switcher, and a popup player, so visitors watch without leaving your page. Every feature is free. There is no paid tier.

**Requirements:** WordPress 5.8+, PHP 7.4+, a YouTube Data API v3 key. Elementor is optional.

## Contents

- [Installation](#installation)
- [Getting an API key](#getting-an-api-key)
- [Usage](#usage)
- [Settings](#settings)
- [How it works](#how-it-works)
- [Known limitations](#known-limitations)
- [Changelog](#changelog)

For site editors who just want to add videos to a page, see the [Editor guide](docs/editor-guide.md).

## Installation

1. Download this repo as a zip (**Code → Download ZIP**).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, upload the zip and activate it.
3. Open **YT Embed Pro → General**, enter your API key and channel ID, and click **Test Connection**.

If the test says "Connected", you're ready to add a feed to a page.

## Getting an API key

1. Open the [Google Cloud Console](https://console.cloud.google.com/) and create a project.
2. Go to **APIs & Services → Library**, find **YouTube Data API v3** and enable it.
3. Go to **APIs & Services → Credentials → Create credentials → API key**.
4. Restrict the key to the YouTube Data API v3.

The plugin calls YouTube from your server, not from the visitor's browser. That means an **HTTP referrer** restriction won't work. If you want to lock the key down further, use an **IP address** restriction with your server's IP.

**Finding your channel ID:** YouTube Studio → Settings → Channel → Advanced settings. It starts with `UC` and is 24 characters long.

## Usage

### Shortcode

```
[yt_embed]
```

With no attributes, this shows the channel from Settings in the default layout. `[yt_shorts]` works too, for older pages.

| Attribute     | Values                                                    | Default          |
| ------------- | --------------------------------------------------------- | ---------------- |
| `source`      | `shorts`, `videos`, `playlist`                            | `shorts`         |
| `layout`      | `grid`, `slider` for Shorts. `wall`, `featured` for videos and playlists | From Settings |
| `channel_id`  | Channel ID (`UC…`)                                        | From Settings    |
| `playlist_id` | Playlist ID (`PL…`). Required when `source="playlist"`    | None             |
| `columns`     | 2 to 6                                                    | From Settings (4) |
| `per_page`    | 1 to 50. Rounded up to fill complete rows                 | From Settings (12) |
| `tabs`        | `true` shows Shorts and Videos in two tabs                | `false`          |
| `filters`     | `true` adds a Recent / Popular sort bar. Not available for playlists | `false` |

If a layout doesn't fit the source (for example `source="shorts" layout="wall"`), the plugin falls back to the first layout for that source.

**Examples**

```
[yt_embed source="shorts" layout="grid" columns="4" per_page="12"]
[yt_embed source="shorts" layout="slider"]
[yt_embed source="videos" layout="wall" columns="3" filters="true"]
[yt_embed source="videos" layout="featured"]
[yt_embed source="playlist" playlist_id="PLxxxxxxxx" layout="wall"]
[yt_embed tabs="true"]
```

### Elementor

Search for the **YouTube Shorts** widget. It supports the Shorts grid and slider layouts, and each widget can have its own columns, gap, border radius, colors and slider options. For the other layouts, use the shortcode inside Elementor's Shortcode widget.

## Settings

| Tab     | What you can change                                                         |
| ------- | --------------------------------------------------------------------------- |
| General | API key, channel ID, default layout, cache duration, Clear Cache button      |
| Grid    | Columns, videos per page, Load More button text                              |
| Slider  | Visible slides, transition speed, autoplay, arrows, dots                     |
| Style   | Tile gap, border radius, tile border, tile background, popup overlay, button colors |

Style settings are output as CSS custom properties (`--yse-gap`, `--yse-border-radius` and so on). A theme can override them without editing the plugin.

## How it works

### File structure

```
youtube-shorts-embed.php   Bootstrap, settings helpers, AJAX handlers
includes/
  class-yse-api.php        YouTube Data API client and caching
  class-yse-format.php     View count, duration and date formatting
  class-yse-shortcode.php  [yt_embed] shortcode
  class-yse-elementor.php  Elementor widget (loaded only when Elementor is active)
admin/
  class-yse-admin.php      Settings page
templates/                 One template per layout, plus tiles and the popup
assets/                    Front-end and admin CSS and JavaScript (no jQuery on the front end)
uninstall.php              Removes settings and cached data
```

### Request flow

1. The shortcode or widget asks `YSE_API` for a page of videos.
2. `YSE_API` returns a cached result, or calls YouTube and caches the answer.
3. A template renders the videos. The feed's wrapper stores the channel ID, playlist ID and a server signature.
4. **Load More** and the sort bar send those values back through `admin-ajax.php`. The server checks the nonce and the signature before calling YouTube.

### API quota

YouTube gives each project 10,000 quota units per day by default. The plugin is built to spend as few as possible:

| Call                 | Cost      | Used for                                                  |
| -------------------- | --------- | --------------------------------------------------------- |
| `playlistItems.list` | 1 unit    | Shorts, uploads and playlists                             |
| `videos.list`        | 1 unit    | View counts and durations for a whole page in one request |
| `search.list`        | 100 units | Only the Recent / Popular sort for long-form videos       |

Shorts come from YouTube's auto-generated Shorts playlist for each channel (`UUSH` plus the channel ID without `UC`). That is exact, and much cheaper than searching.

### Caching

Responses are stored as transients for the time set under **Cache Duration** (2 hours by default). To clear the cache, the plugin increases a version number that is part of every cache key, instead of deleting database rows. This works with any cache backend, including persistent object caches. Old entries expire on their own.

The cache is cleared when you save settings, click **Clear Cache**, or deactivate the plugin.

### Security

- The API key stays on the server and is never sent to the browser.
- Public AJAX requests need a valid nonce **and** a signature for the channel and playlist the page was rendered with. A visitor can't point the endpoint at another channel and spend your quota.
- All output in templates is escaped. Settings are sanitized and limited to allowed values.
- Admin actions check the `manage_options` capability and a nonce.
- Visitors see a neutral error message. Admins see the real reason, for example an invalid key.

### Accessibility

- Video tiles can be reached with Tab and opened with Enter or Space.
- The popup is marked as a dialog, closes with Escape, keeps focus inside while it's open, and returns focus to the video that opened it.
- Thumbnails use the video title as alt text.

## Known limitations

- **Popular Shorts** sorts the Shorts loaded so far. The first view ranks the channel's 50 most recent Shorts, and more are added as visitors click Load More.
- The Recent / Popular sort for long-form videos uses `search.list` at 100 units per call. Keep caching on when you use it.
- The Elementor widget covers the Shorts grid and slider. Use the shortcode for the other layouts.

## Changelog

**1.3.0**
- Removed the Pro license. All layouts and Load More are available to everyone.
- Public AJAX requests now require a signed channel and playlist ID.
- Rewrote the API client with one shared request method and one caching method.
- Cache clearing now uses a version number instead of SQL deletes.
- Popup focus returns to the video that opened it and stays inside the popup while it's open.
- Fixed Load More on playlist feeds.
- Removed unused code: an unrendered filter bar, an unreachable live-streams feature and old Pro page styles.

## License

GPL-2.0 or later. Built by [Usman Shafiq](https://usmaaan.com).
