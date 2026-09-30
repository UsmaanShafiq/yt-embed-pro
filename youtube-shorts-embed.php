<?php
/**
 * Plugin Name:       YouTube Shorts Embed
 * Plugin URI:        https://github.com/UsmaanShafiq/yt-embed-pro
 * Description:       Display YouTube Shorts and videos from any channel in grid, slider, wall, featured or tabbed layouts with a lightbox player. Works as a shortcode or an Elementor widget.
 * Version:           1.3.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Usman Shafiq
 * Author URI:        https://usmaaan.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       youtube-shorts-embed
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YSE_VERSION', '1.3.0' );
define( 'YSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// The YouTube Data API returns at most 50 items per request.
define( 'YSE_MAX_PER_PAGE', 50 );

// The Elementor widget is loaded separately, only once Elementor is ready.
require_once YSE_PLUGIN_DIR . 'includes/class-yse-api.php';
require_once YSE_PLUGIN_DIR . 'includes/class-yse-format.php';
require_once YSE_PLUGIN_DIR . 'includes/class-yse-shortcode.php';
require_once YSE_PLUGIN_DIR . 'admin/class-yse-admin.php';

register_activation_hook( __FILE__, 'yse_activate' );
register_deactivation_hook( __FILE__, array( 'YSE_API', 'flush_cache' ) );

/**
 * Store the default settings on first activation.
 */
function yse_activate() {
	add_option( 'yse_settings', yse_default_settings() );
}

/**
 * Default plugin settings.
 *
 * @return array
 */
function yse_default_settings() {
	return array(
		'api_key'             => '',
		'channel_id'          => '',
		'layout'              => 'grid',
		'columns'             => 4,
		'per_page'            => 12,
		'cache_hours'         => 2,
		'border_radius'       => 12,
		'gap'                 => 12,
		'tile_bg'             => '#1a1a1a',
		'overlay_color'       => 'rgba(0,0,0,0.85)',
		'btn_text'            => 'Load More',
		'btn_bg'              => '#FF0000',
		'btn_color'           => '#ffffff',
		'slider_autoplay'     => 0,
		'slider_speed'        => 400,
		'slider_arrows'       => 1,
		'slider_dots'         => 1,
		'slider_per_view'     => 4,
		'tile_border_width'   => 0,
		'tile_border_color'   => '#ffffff',
		'show_section_header' => 0,
		'section_title'       => '',
		'section_desc'        => '',
		'show_view_all'       => 0,
		'view_all_text'       => 'View all shorts',
	);
}

/**
 * Saved settings merged with defaults, so a key added in a newer version
 * never shows up as undefined on sites that saved settings earlier.
 *
 * @return array
 */
function yse_get_settings() {
	return wp_parse_args( (array) get_option( 'yse_settings', array() ), yse_default_settings() );
}

/**
 * Sign the channel and playlist a feed was rendered with.
 *
 * The public "Load More" and sort endpoints need to know which channel to
 * fetch, but they must not accept any channel a visitor sends. Otherwise
 * anyone could use this site's YouTube API key and daily quota for their own
 * requests. The server signs the IDs when it renders a feed and the AJAX
 * handlers only accept IDs that carry a valid signature.
 *
 * @param string $channel_id  Channel ID (UC…).
 * @param string $playlist_id Playlist ID (PL…), if the feed is a playlist.
 * @return string
 */
function yse_source_signature( $channel_id, $playlist_id = '' ) {
	return wp_hash( 'yse_source|' . $channel_id . '|' . $playlist_id );
}

/**
 * Error text that is safe to show on the front end.
 *
 * Admins see the real reason (missing key, quota exceeded) so they can fix it.
 * Visitors get a neutral message that doesn't reveal how the site is set up.
 *
 * @param WP_Error $error Error returned by YSE_API.
 * @return string
 */
function yse_public_error_message( WP_Error $error ) {
	if ( current_user_can( 'manage_options' ) ) {
		return $error->get_error_message();
	}
	return __( 'Videos could not be loaded right now. Please try again later.', 'youtube-shorts-embed' );
}

/**
 * Wire up hooks.
 */
function yse_bootstrap() {
	( new YSE_Admin() )->init();
	( new YSE_Shortcode() )->init();

	add_action( 'wp_enqueue_scripts', 'yse_enqueue_frontend_assets' );

	// Public AJAX endpoints, available to logged-in and logged-out visitors.
	add_action( 'wp_ajax_yse_load_more', 'yse_ajax_load_more' );
	add_action( 'wp_ajax_nopriv_yse_load_more', 'yse_ajax_load_more' );
	add_action( 'wp_ajax_yse_sort_feed', 'yse_ajax_sort_feed' );
	add_action( 'wp_ajax_nopriv_yse_sort_feed', 'yse_ajax_sort_feed' );

	add_action( 'elementor/widgets/register', 'yse_register_elementor_widget' );
}
add_action( 'plugins_loaded', 'yse_bootstrap' );

/**
 * Enqueue front-end assets and expose style settings as CSS custom properties,
 * so templates never need inline styles for colors and spacing.
 */
function yse_enqueue_frontend_assets() {
	$settings = yse_get_settings();

	wp_enqueue_style( 'yse-frontend', YSE_PLUGIN_URL . 'assets/css/frontend.css', array(), YSE_VERSION );

	// The overlay accepts rgba() for transparency, so it can't use sanitize_hex_color().
	$overlay = sanitize_text_field( $settings['overlay_color'] );
	if ( ! preg_match( '/^(rgba?\([\d\s,\.]+\)|#[0-9a-fA-F]{3,8}|[a-z]+)$/', $overlay ) ) {
		$overlay = 'rgba(0,0,0,0.85)';
	}

	$vars = array(
		'--yse-columns'           => absint( $settings['columns'] ),
		'--yse-gap'               => absint( $settings['gap'] ) . 'px',
		'--yse-border-radius'     => absint( $settings['border_radius'] ) . 'px',
		'--yse-tile-bg'           => sanitize_hex_color( $settings['tile_bg'] ) ?: '#1a1a1a',
		'--yse-overlay-color'     => $overlay,
		'--yse-btn-bg'            => sanitize_hex_color( $settings['btn_bg'] ) ?: '#FF0000',
		'--yse-btn-color'         => sanitize_hex_color( $settings['btn_color'] ) ?: '#ffffff',
		'--yse-tile-border-width' => absint( $settings['tile_border_width'] ) . 'px',
		'--yse-tile-border-color' => sanitize_hex_color( $settings['tile_border_color'] ) ?: '#ffffff',
	);

	$css = ':root{';
	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}
	wp_add_inline_style( 'yse-frontend', $css . '}' );

	// Plain JavaScript, no jQuery dependency. Loaded in the footer.
	wp_enqueue_script( 'yse-frontend', YSE_PLUGIN_URL . 'assets/js/frontend.js', array(), YSE_VERSION, true );
	wp_localize_script(
		'yse-frontend',
		'yseVars',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'yse_frontend' ),
		)
	);
}

/**
 * Register the Elementor widget. Only runs when Elementor is active.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function yse_register_elementor_widget( $widgets_manager ) {
	require_once YSE_PLUGIN_DIR . 'includes/class-yse-elementor.php';
	$widgets_manager->register( new YSE_Elementor_Widget() );
}

/* ── AJAX helpers ───────────────────────────────────────────────────────── */

/**
 * Read a text value from the POST body.
 *
 * @param string $key     Field name.
 * @param string $default Value when the field is missing.
 * @return string
 */
function yse_post_text( $key, $default = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is checked in yse_verify_ajax_request().
	return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
}

/**
 * Check the nonce and the feed signature of a public AJAX request.
 * Ends the request with a JSON error when either check fails.
 *
 * @return array { channel_id: string, playlist_id: string }
 */
function yse_verify_ajax_request() {
	if ( ! wp_verify_nonce( yse_post_text( 'nonce' ), 'yse_frontend' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'youtube-shorts-embed' ) ), 403 );
	}

	$channel_id  = yse_post_text( 'channel_id' );
	$playlist_id = yse_post_text( 'playlist_id' );

	if ( ! hash_equals( yse_source_signature( $channel_id, $playlist_id ), yse_post_text( 'sig' ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid feed.', 'youtube-shorts-embed' ) ), 403 );
	}

	return array(
		'channel_id'  => $channel_id,
		'playlist_id' => $playlist_id,
	);
}

/**
 * Render video tiles for an AJAX response.
 *
 * @param array  $videos      Videos from YSE_API.
 * @param string $source      shorts | videos | playlist.
 * @param int    $start_index Number shown on the first tile's badge.
 * @return string HTML.
 */
function yse_render_tiles( $videos, $source, $start_index = 1 ) {
	// Shorts use the portrait tile, everything else the landscape one.
	$template = ( 'shorts' === $source ) ? 'tile' : 'video-tile';
	$index    = $start_index;

	ob_start();
	foreach ( $videos as $video ) {
		include YSE_PLUGIN_DIR . 'templates/' . $template . '.php';
		$index++;
	}
	return ob_get_clean();
}

/**
 * Send a JSON error for a failed API call.
 *
 * @param WP_Error $error Error returned by YSE_API.
 */
function yse_send_api_error( WP_Error $error ) {
	wp_send_json_error( array( 'message' => yse_public_error_message( $error ) ), 502 );
}

/* ── AJAX handlers ──────────────────────────────────────────────────────── */

/**
 * "Load More" pagination for grid, wall and tabs layouts.
 */
function yse_ajax_load_more() {
	$feed       = yse_verify_ajax_request();
	$source     = sanitize_key( yse_post_text( 'source', 'shorts' ) );
	$page_token = yse_post_text( 'page_token' );
	$per_page   = min( YSE_MAX_PER_PAGE, max( 1, absint( yse_post_text( 'per_page', '12' ) ) ) );
	$offset     = absint( yse_post_text( 'offset', '0' ) );
	$api        = new YSE_API();

	if ( 'playlist' === $source ) {
		$result = $api->fetch_playlist( $feed['playlist_id'], $per_page, $page_token );
	} elseif ( 'videos' === $source ) {
		$result = $api->fetch_videos( $feed['channel_id'], $per_page, $page_token );
	} else {
		$source = 'shorts';
		$result = $api->fetch_shorts( $feed['channel_id'], $per_page, $page_token );
	}

	if ( is_wp_error( $result ) ) {
		yse_send_api_error( $result );
	}

	wp_send_json_success(
		array(
			'html'       => yse_render_tiles( $result['videos'], $source, $offset + 1 ),
			'next_token' => $result['nextPageToken'],
		)
	);
}

/**
 * Recent / Popular sort bar (filters="true").
 * Also serves "Load More" while the Popular sort is active.
 */
function yse_ajax_sort_feed() {
	$feed       = yse_verify_ajax_request();
	$source     = ( 'videos' === yse_post_text( 'source' ) ) ? 'videos' : 'shorts';
	$order      = ( 'popular' === yse_post_text( 'sort' ) ) ? 'viewCount' : 'date';
	$page_token = yse_post_text( 'page_token' );
	$per_page   = min( YSE_MAX_PER_PAGE, max( 1, absint( yse_post_text( 'per_page', '12' ) ) ) );
	$offset     = absint( yse_post_text( 'offset', '0' ) );

	$result = ( new YSE_API() )->fetch_by_sort( $feed['channel_id'], $per_page, $source, $order, $page_token );

	if ( is_wp_error( $result ) ) {
		yse_send_api_error( $result );
	}

	wp_send_json_success(
		array(
			'html'       => yse_render_tiles( $result['videos'], $source, $offset + 1 ),
			'next_token' => $result['nextPageToken'],
			'count'      => count( $result['videos'] ),
		)
	);
}
