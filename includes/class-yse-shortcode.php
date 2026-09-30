<?php
/**
 * Shortcode handler.
 *
 *   [yt_embed source="shorts|videos|playlist" layout="grid|slider|wall|featured"
 *             channel_id="" playlist_id="" columns="4" per_page="12"
 *             tabs="false" filters="false"]
 *
 * tabs="true"    wraps the feed in a Shorts / Videos tab switcher.
 * filters="true" adds a Recent / Popular sort bar.
 *
 * [yt_shorts] is kept as an alias so older pages keep working.
 *
 * @package YT_Shorts_Embed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YSE_Shortcode {

	/** Layouts each source supports. The first one is the fallback. */
	const LAYOUTS = array(
		'shorts'   => array( 'grid', 'slider' ),
		'videos'   => array( 'wall', 'featured' ),
		'playlist' => array( 'wall', 'featured' ),
	);

	public function init() {
		add_shortcode( 'yt_embed', array( $this, 'render' ) );
		add_shortcode( 'yt_shorts', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string HTML.
	 */
	public function render( $atts ) {
		$settings = yse_get_settings();
		$atts     = shortcode_atts(
			array(
				'source'      => 'shorts',
				'channel_id'  => $settings['channel_id'],
				'playlist_id' => '',
				'layout'      => $settings['layout'],
				'columns'     => $settings['columns'],
				'per_page'    => $settings['per_page'],
				'tabs'        => 'false',
				'filters'     => 'false',
			),
			$atts,
			current_filter()
		);

		$source = sanitize_key( $atts['source'] );
		$source = isset( self::LAYOUTS[ $source ] ) ? $source : 'shorts';
		$layout = sanitize_key( $atts['layout'] );
		$layout = in_array( $layout, self::LAYOUTS[ $source ], true ) ? $layout : self::LAYOUTS[ $source ][0];

		$channel_id  = sanitize_text_field( $atts['channel_id'] );
		$playlist_id = sanitize_text_field( $atts['playlist_id'] );
		$columns     = max( 2, min( 6, absint( $atts['columns'] ) ) );

		// Round up to full rows so the last row is never half empty,
		// without going over the API's 50 videos per request.
		$per_page = (int) ceil( max( 1, absint( $atts['per_page'] ) ) / $columns ) * $columns;
		$per_page = min( $per_page, (int) floor( YSE_MAX_PER_PAGE / $columns ) * $columns );

		if ( self::is_true( $atts['tabs'] ) ) {
			return $channel_id
				? $this->render_tabs( $channel_id, $columns, $per_page )
				: self::message( __( 'Please set a Channel ID in Settings or with the channel_id attribute.', 'youtube-shorts-embed' ) );
		}

		// Sorting reads a channel's uploads, so it doesn't apply to a single playlist.
		$show_filters = self::is_true( $atts['filters'] ) && 'playlist' !== $source;

		if ( 'playlist' === $source && empty( $playlist_id ) ) {
			return self::message( __( 'Please add a playlist_id attribute.', 'youtube-shorts-embed' ) );
		}
		if ( 'playlist' !== $source && empty( $channel_id ) ) {
			return self::message( __( 'Please set a Channel ID in Settings or with the channel_id attribute.', 'youtube-shorts-embed' ) );
		}

		$api = new YSE_API();
		if ( 'shorts' === $source ) {
			$result = $api->fetch_shorts( $channel_id, $per_page );
		} elseif ( 'playlist' === $source ) {
			$result = $api->fetch_playlist( $playlist_id, $per_page );
		} else {
			$result = $api->fetch_videos( $channel_id, $per_page );
		}

		if ( is_wp_error( $result ) ) {
			return self::message( yse_public_error_message( $result ) );
		}
		if ( empty( $result['videos'] ) ) {
			return self::message( __( 'No videos found for this channel.', 'youtube-shorts-embed' ), 'yse-empty' );
		}

		$videos     = $result['videos'];
		$next_token = $result['nextPageToken'];

		ob_start();
		include YSE_PLUGIN_DIR . 'templates/' . $layout . '.php';
		return ob_get_clean();
	}

	/**
	 * Shorts and long-form videos of one channel, in two tabs.
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param int    $columns    Grid columns.
	 * @param int    $per_page   Videos per tab.
	 * @return string HTML.
	 */
	private function render_tabs( $channel_id, $columns, $per_page ) {
		$api    = new YSE_API();
		$shorts = $api->fetch_shorts( $channel_id, $per_page );
		$long   = $api->fetch_videos( $channel_id, $per_page );

		foreach ( array( $shorts, $long ) as $result ) {
			if ( is_wp_error( $result ) ) {
				return self::message( yse_public_error_message( $result ) );
			}
		}

		$shorts_videos     = $shorts['videos'];
		$shorts_next_token = $shorts['nextPageToken'];
		$long_videos       = $long['videos'];
		$long_next_token   = $long['nextPageToken'];

		ob_start();
		include YSE_PLUGIN_DIR . 'templates/tabs.php';
		return ob_get_clean();
	}

	/**
	 * Accept "true", "1" and "yes" for boolean attributes.
	 *
	 * @param mixed $value Attribute value.
	 * @return bool
	 */
	private static function is_true( $value ) {
		return in_array( strtolower( (string) $value ), array( 'true', '1', 'yes' ), true );
	}

	/**
	 * Error or notice paragraph.
	 *
	 * @param string $text  Message.
	 * @param string $class CSS class.
	 * @return string
	 */
	private static function message( $text, $class = 'yse-error' ) {
		return '<p class="' . esc_attr( $class ) . '">' . esc_html( $text ) . '</p>';
	}
}
