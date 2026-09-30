# Editor guide: adding YouTube videos to a page

This guide is for people who edit the website. You don't need any coding knowledge. If the plugin isn't set up yet, ask your developer to follow the [README](../README.md) first.

## Add videos to a page

### With the block editor

1. Open the page and click **+** to add a block.
2. Choose the **Shortcode** block.
3. Paste one of the shortcodes from the table below.
4. Click **Update**, then view the page.

### With Elementor

1. Open the page with Elementor.
2. Search for the **YouTube Shorts** widget and drag it onto the page.
3. Pick **Grid** or **Slider**, then adjust columns and colors in the panel on the left.
4. Click **Update**.

## Which layout should I use?

| You want to show                          | Paste this                                       |
| ----------------------------------------- | ------------------------------------------------ |
| Shorts in a grid                          | `[yt_embed source="shorts" layout="grid"]`       |
| Shorts in a sliding row                   | `[yt_embed source="shorts" layout="slider"]`     |
| Regular videos in a grid                  | `[yt_embed source="videos" layout="wall"]`       |
| One big video with a list beside it       | `[yt_embed source="videos" layout="featured"]`   |
| Shorts and videos, in two tabs            | `[yt_embed tabs="true"]`                         |
| A specific playlist                       | `[yt_embed source="playlist" playlist_id="PL…"]` |

**To find a playlist ID:** open the playlist on YouTube and copy the part of the address after `list=`.

**To show more or fewer videos**, add `per_page`, for example `per_page="8"`. The maximum is 50.

**To let visitors sort by Recent or Popular**, add `filters="true"`.

## Change colors and spacing

Go to **YT Embed Pro → Style** in the WordPress admin menu. The changes apply to every shortcode feed on the site. Elementor widgets have their own style settings.

## Troubleshooting

**I uploaded a new video but it isn't showing.**
The plugin saves a copy of your videos for up to 2 hours to keep the site fast. Either wait, or go to **YT Embed Pro → General** and click **Clear Cache**.

**Visitors see "Videos could not be loaded right now."**
Log in and open the same page. As a logged-in admin, you'll see the actual reason:

| Message                                   | What to do                                                   |
| ----------------------------------------- | ------------------------------------------------------------ |
| YouTube API key is not set                | Add the key under **YT Embed Pro → General**.                |
| API key not valid                         | The key was deleted or mistyped. Ask your developer for a new one. |
| …exceeded your quota                      | The site used its daily YouTube allowance. It resets at midnight Pacific Time. Turning caching on or raising the cache duration helps. |
| Channel ID must start with "UC"           | Check the channel ID under **YT Embed Pro → General**.       |

**The page shows "No videos found for this channel."**
The channel has no public videos of that type. For example, it might have regular videos but no Shorts. Try `source="videos"`.
