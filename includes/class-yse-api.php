<?php
/**
 * YouTube Data API v3 client.
 *
 * Every request runs on the server, so the API key is never sent to the
 * browser. Responses are cached in transients to stay inside YouTube's
 * daily quota (10,000 units by default).
 *
 * @package YT_Shorts_Embed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches videos from YouTube and returns them in one consistent shape:
 *
 *     array(
 *         'videos'        => array( array( 'video_id', 'title', 'thumbnail', 'published_at', 'view_count', 'duration', 'type' ), … ),
 *         'nextPageToken' => string|null,
 *     )
 *
 * or a WP_Error when YouTube can't be reached or rejects the request.
 */
class YSE_API {

	const API_BASE = 'https://www.googleapis.com/youtube/v3/';

	/** Option holding the cache version. Increasing it invalidates every cached response. */
	const CACHE_VERSION_OPTION = 'yse_cache_version';

	/** Maximum YouTube pages fetched in a single request when building the Popular Shorts pool. */
	const MAX_POOL_PAGES = 5;

	/** @var string */
	private $api_key;

	/** @var int Cache lifetime in seconds. 0 turns caching off. */
	private $cache_ttl;

	public function __construct() {
		$settings        = yse_get_settings();
		$this->api_key   = sanitize_text_field( $settings['api_key'] );
		$this->cache_ttl = absint( $settings['cache_hours'] ) * HOUR_IN_SECONDS;
	}

	/* ── Public fetch methods ───────────────────────────────────────────── */

	/**
	 * Fetch a channel's Shorts.
	 *
	 * YouTube keeps an auto-generated, Shorts-only playlist for every channel:
	 * "UUSH" followed by the channel ID without its "UC" prefix. Reading that
	 * playlist is exact and costs 1 quota unit. Guessing Shorts by video length
	 * would be less reliable and the Search API costs 100 units per call.
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param int    $per_page   Videos per page, 1 to 50.
	 * @param string $page_token Token from a previous response, for the next page.
	 * @return array|WP_Error
	 */
	public function fetch_shorts( $channel_id, $per_page = 12, $page_token = '' ) {
		$playlist_id = self::channel_playlist( $channel_id, 'UUSH' );
		if ( is_wp_error( $playlist_id ) ) {
			return $playlist_id;
		}

		// oar2.jpg is YouTube's native 9:16 cover image for Shorts.
		return $this->fetch_playlist_items( $playlist_id, $per_page, $page_token, 'shorts', 'oar2.jpg' );
	}

	/**
	 * Fetch all uploads of a channel from its "UU" uploads playlist.
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param int    $per_page   Videos per page, 1 to 50.
	 * @param string $page_token Token for the next page.
	 * @return array|WP_Error
	 */
	public function fetch_videos( $channel_id, $per_page = 12, $page_token = '' ) {
		$playlist_id = self::channel_playlist( $channel_id, 'UU' );
		if ( is_wp_error( $playlist_id ) ) {
			return $playlist_id;
		}

		return $this->fetch_playlist_items( $playlist_id, $per_page, $page_token, 'videos', 'maxresdefault.jpg' );
	}

	/**
	 * Fetch videos from any public playlist.
	 *
	 * @param string $playlist_id Playlist ID (PL…).
	 * @param int    $per_page    Videos per page, 1 to 50.
	 * @param string $page_token  Token for the next page.
	 * @return array|WP_Error
	 */
	public function fetch_playlist( $playlist_id, $per_page = 12, $page_token = '' ) {
		if ( empty( $playlist_id ) ) {
			return new WP_Error( 'yse_no_playlist', __( 'Playlist ID is required.', 'youtube-shorts-embed' ) );
		}

		return $this->fetch_playlist_items( $playlist_id, $per_page, $page_token, 'playlist', 'maxresdefault.jpg' );
	}

	/**
	 * Fetch a channel's Shorts or videos in "recent" or "popular" order.
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param int    $per_page   Videos per page, 1 to 50.
	 * @param string $source     'shorts' or 'videos'.
	 * @param string $order      'date' or 'viewCount'.
	 * @param string $page_token Token for the next page.
	 * @return array|WP_Error
	 */
	public function fetch_by_sort( $channel_id, $per_page, $source, $order, $page_token = '' ) {
		if ( 'shorts' !== $source ) {
			return $this->search_channel( $channel_id, $per_page, $order, $page_token );
		}

		return ( 'viewCount' === $order )
			? $this->fetch_popular_shorts( $channel_id, $per_page, $page_token )
			: $this->fetch_shorts( $channel_id, $per_page, $page_token );
	}

	/**
	 * Check an API key and channel ID from the settings page before they are saved.
	 *
	 * @param string $api_key    API key typed in the settings form.
	 * @param string $channel_id Channel ID typed in the settings form.
	 * @return array|WP_Error { found: int } Number of Shorts on the channel.
	 */
	public function test_connection( $api_key, $channel_id ) {
		$playlist_id = self::channel_playlist( $channel_id, 'UUSH' );
		if ( is_wp_error( $playlist_id ) ) {
			return $playlist_id;
		}

		$body = $this->request(
			'playlistItems',
			array(
				'part'       => 'id',
				'playlistId' => $playlist_id,
				'maxResults' => 1,
				'key'        => $api_key,
			)
		);
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		return array( 'found' => (int) ( $body['pageInfo']['totalResults'] ?? 0 ) );
	}

	/* ── Cache ──────────────────────────────────────────────────────────── */

	/**
	 * Invalidate every cached response.
	 *
	 * Instead of deleting transient rows with SQL, this bumps a version number
	 * that is part of every cache key. That works with any cache backend,
	 * including persistent object caches where transients never reach the
	 * options table. Old entries simply expire on their own.
	 */
	public static function flush_cache() {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 1 );
		update_option( self::CACHE_VERSION_OPTION, $version + 1, false );
	}

	/**
	 * Build a transient name that changes whenever the cache is flushed.
	 *
	 * @param string $name Short label for the kind of data.
	 * @param array  $args Everything that makes this response unique.
	 * @return string
	 */
	private static function cache_key( $name, $args ) {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 1 );
		return 'yse_' . $version . '_' . md5( $name . wp_json_encode( $args ) );
	}

	/**
	 * Return a cached result, or run $callback and cache what it returns.
	 * Errors are never cached, so a temporary failure doesn't stick for hours.
	 *
	 * @param string   $name     Short label for the kind of data.
	 * @param array    $args     Everything that makes this response unique.
	 * @param callable $callback Produces the result on a cache miss.
	 * @return array|WP_Error
	 */
	private function remember( $name, $args, $callback ) {
		if ( ! $this->cache_ttl ) {
			return $callback();
		}

		$key    = self::cache_key( $name, $args );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result = $callback();
		if ( ! is_wp_error( $result ) ) {
			set_transient( $key, $result, $this->cache_ttl );
		}
		return $result;
	}

	/* ── Private helpers ────────────────────────────────────────────────── */

	/**
	 * Read one page of a playlist and add view counts and durations.
	 *
	 * @param string $playlist_id Playlist to read.
	 * @param int    $per_page    Videos per page.
	 * @param string $page_token  Token for the next page.
	 * @param string $type        Stored on each video: shorts | videos | playlist.
	 * @param string $thumbnail   Thumbnail file name on i.ytimg.com.
	 * @return array|WP_Error
	 */
	private function fetch_playlist_items( $playlist_id, $per_page, $page_token, $type, $thumbnail ) {
		$args = array(
			'part'       => 'snippet,status',
			'playlistId' => $playlist_id,
			'maxResults' => self::clamp_per_page( $per_page ),
		);
		if ( $page_token ) {
			$args['pageToken'] = $page_token;
		}

		return $this->remember(
			'items_' . $type,
			$args,
			function () use ( $args, $type, $thumbnail ) {
				$body = $this->request( 'playlistItems', $args );
				if ( is_wp_error( $body ) ) {
					return $body;
				}

				$videos = array();
				foreach ( $body['items'] ?? array() as $item ) {
					// Private and deleted videos stay listed in playlists, so skip them.
					if ( 'public' !== ( $item['status']['privacyStatus'] ?? 'public' ) ) {
						continue;
					}
					$video_id = $item['snippet']['resourceId']['videoId'] ?? '';
					if ( $video_id ) {
						$videos[] = self::make_video( $video_id, $item['snippet'], $type, $thumbnail );
					}
				}

				return array(
					'videos'        => $this->add_stats( $videos ),
					'nextPageToken' => $body['nextPageToken'] ?? null,
				);
			}
		);
	}

	/**
	 * Search a channel's videos in date or view-count order.
	 * search.list costs 100 quota units per call, so caching matters most here.
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param int    $per_page   Videos per page.
	 * @param string $order      'date' or 'viewCount'.
	 * @param string $page_token Token for the next page.
	 * @return array|WP_Error
	 */
	private function search_channel( $channel_id, $per_page, $order, $page_token ) {
		$args = array(
			'part'       => 'snippet',
			'channelId'  => $channel_id,
			'type'       => 'video',
			'order'      => ( 'viewCount' === $order ) ? 'viewCount' : 'date',
			'maxResults' => self::clamp_per_page( $per_page ),
		);
		if ( $page_token ) {
			$args['pageToken'] = $page_token;
		}

		return $this->remember(
			'search',
			$args,
			function () use ( $args ) {
				$body = $this->request( 'search', $args );
				if ( is_wp_error( $body ) ) {
					return $body;
				}

				$videos = array();
				foreach ( $body['items'] ?? array() as $item ) {
					$video_id = $item['id']['videoId'] ?? '';
					if ( $video_id ) {
						$videos[] = self::make_video( $video_id, $item['snippet'], 'videos', 'maxresdefault.jpg' );
					}
				}

				return array(
					'videos'        => $this->add_stats( $videos ),
					'nextPageToken' => $body['nextPageToken'] ?? null,
				);
			}
		);
	}

	/**
	 * Shorts sorted by view count.
	 *
	 * The Search API has no Shorts-only filter, so this reads the Shorts
	 * playlist, sorts it by views and pages through that sorted pool by
	 * offset. The pool is kept in a transient so "Load More" continues in the
	 * same order. Page tokens look like "pop:24".
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param int    $per_page   Videos per page.
	 * @param string $page_token "pop:OFFSET" for the next page, empty for the first.
	 * @return array|WP_Error
	 */
	private function fetch_popular_shorts( $channel_id, $per_page, $page_token ) {
		$pool_key = self::cache_key( 'popular_shorts', array( $channel_id ) );
		$per_page = self::clamp_per_page( $per_page );
		$offset   = 0;
		$pool     = false;

		// A first load always rebuilds the pool so the order is current. If the
		// pool expired between "Load More" clicks, it is rebuilt and extended
		// below until it covers the requested offset again.
		if ( 0 === strpos( $page_token, 'pop:' ) ) {
			$offset = absint( substr( $page_token, 4 ) );
			$pool   = get_transient( $pool_key );
		}

		if ( ! is_array( $pool ) ) {
			$first = $this->fetch_shorts( $channel_id, YSE_MAX_PER_PAGE );
			if ( is_wp_error( $first ) ) {
				return $first;
			}
			$pool   = array(
				'videos'   => $first['videos'],
				'yt_token' => $first['nextPageToken'],
			);
		}

		// Pull more pages until the pool covers the requested slice. The page
		// limit keeps one request from spending a large part of the quota.
		$pages = 0;
		while ( count( $pool['videos'] ) < $offset + $per_page && ! empty( $pool['yt_token'] ) && $pages++ < self::MAX_POOL_PAGES ) {
			$more = $this->fetch_shorts( $channel_id, YSE_MAX_PER_PAGE, $pool['yt_token'] );
			if ( is_wp_error( $more ) ) {
				break;
			}
			$pool['videos']   = array_merge( $pool['videos'], $more['videos'] );
			$pool['yt_token'] = $more['nextPageToken'];
		}

		usort(
			$pool['videos'],
			function ( $a, $b ) {
				return (int) $b['view_count'] - (int) $a['view_count'];
			}
		);
		set_transient( $pool_key, $pool, 2 * HOUR_IN_SECONDS );

		$next_offset = $offset + $per_page;
		$has_more    = count( $pool['videos'] ) > $next_offset || ! empty( $pool['yt_token'] );

		return array(
			'videos'        => array_slice( $pool['videos'], $offset, $per_page ),
			'nextPageToken' => $has_more ? 'pop:' . $next_offset : null,
		);
	}

	/**
	 * Add view counts and durations. One videos.list call covers up to 50 IDs,
	 * so a whole page costs 1 quota unit instead of 1 per video.
	 *
	 * @param array $videos Videos built by make_video().
	 * @return array
	 */
	private function add_stats( $videos ) {
		if ( empty( $videos ) ) {
			return $videos;
		}

		$body = $this->request(
			'videos',
			array(
				'part' => 'statistics,contentDetails',
				'id'   => implode( ',', wp_list_pluck( $videos, 'video_id' ) ),
			)
		);

		// Stats are nice to have. If this call fails, show the videos without them.
		if ( is_wp_error( $body ) ) {
			return $videos;
		}

		$details = array_column( $body['items'] ?? array(), null, 'id' );
		foreach ( $videos as &$video ) {
			$item = $details[ $video['video_id'] ] ?? null;
			if ( $item ) {
				$video['view_count'] = (int) ( $item['statistics']['viewCount'] ?? 0 );
				$video['duration']   = $item['contentDetails']['duration'] ?? '';
			}
		}
		unset( $video );

		return $videos;
	}

	/**
	 * Send a GET request to the API and return the decoded JSON body.
	 *
	 * @param string $endpoint Endpoint name, e.g. 'playlistItems'.
	 * @param array  $args     Query parameters. The saved API key is used unless 'key' is given.
	 * @return array|WP_Error
	 */
	private function request( $endpoint, $args ) {
		$args += array( 'key' => $this->api_key );
		if ( empty( $args['key'] ) ) {
			return new WP_Error( 'yse_no_api_key', __( 'YouTube API key is not set. Add it under YT Embed Pro → General.', 'youtube-shorts-embed' ) );
		}

		$url      = add_query_arg( rawurlencode_deep( $args ), self::API_BASE . $endpoint );
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			/* translators: %d: HTTP status code. */
			$message = $body['error']['message'] ?? sprintf( __( 'YouTube API error %d.', 'youtube-shorts-embed' ), $code );
			return new WP_Error( 'yse_api_error', $message );
		}

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Turn a channel ID into one of its auto-generated playlists:
	 * "UU" for all uploads, "UUSH" for Shorts only.
	 *
	 * @param string $channel_id Channel ID (UC…).
	 * @param string $prefix     'UU' or 'UUSH'.
	 * @return string|WP_Error
	 */
	private static function channel_playlist( $channel_id, $prefix ) {
		if ( ! preg_match( '/^UC[\w-]{22}$/', (string) $channel_id ) ) {
			return new WP_Error( 'yse_invalid_channel', __( 'Channel ID must start with "UC" and be 24 characters long.', 'youtube-shorts-embed' ) );
		}
		return $prefix . substr( $channel_id, 2 );
	}

	/**
	 * Keep a page size inside what the API accepts.
	 *
	 * @param int $per_page Requested page size.
	 * @return int
	 */
	private static function clamp_per_page( $per_page ) {
		return max( 1, min( YSE_MAX_PER_PAGE, absint( $per_page ) ) );
	}

	/**
	 * Build a video in the shape the templates expect.
	 *
	 * @param string $video_id  YouTube video ID.
	 * @param array  $snippet   The item's "snippet" object from the API.
	 * @param string $type      shorts | videos | playlist.
	 * @param string $thumbnail Thumbnail file name on i.ytimg.com.
	 * @return array
	 */
	private static function make_video( $video_id, $snippet, $type, $thumbnail ) {
		return array(
			'video_id'     => $video_id,
			'title'        => $snippet['title'] ?? '',
			'thumbnail'    => 'https://i.ytimg.com/vi/' . rawurlencode( $video_id ) . '/' . $thumbnail,
			'published_at' => $snippet['publishedAt'] ?? '',
			'view_count'   => '',
			'duration'     => '',
			'type'         => $type,
		);
	}
}
