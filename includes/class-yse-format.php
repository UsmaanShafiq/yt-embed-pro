<?php
/**
 * Display formatters used by the templates.
 *
 * Kept apart from YSE_API so the API client only fetches data and the
 * templates decide how it looks.
 *
 * @package YT_Shorts_Embed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YSE_Format {

	/**
	 * "1.2M views", "3.4K views", "812 views".
	 *
	 * @param int|string $count View count, or '' when unknown.
	 * @return string
	 */
	public static function views( $count ) {
		if ( '' === $count || null === $count ) {
			return '';
		}
		$n = (int) $count;
		if ( $n >= 1000000 ) {
			return round( $n / 1000000, 1 ) . 'M views';
		}
		if ( $n >= 1000 ) {
			return round( $n / 1000, 1 ) . 'K views';
		}
		return $n . ' views';
	}

	/**
	 * Convert an ISO 8601 duration (PT1H2M3S) to "1:02:03" or "2:03".
	 *
	 * @param string $iso Duration from the API.
	 * @return string
	 */
	public static function duration( $iso ) {
		if ( ! preg_match( '/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', (string) $iso, $m ) ) {
			return '';
		}
		$h = (int) ( $m[1] ?? 0 );
		$i = (int) ( $m[2] ?? 0 );
		$s = (int) ( $m[3] ?? 0 );
		return $h ? sprintf( '%d:%02d:%02d', $h, $i, $s ) : sprintf( '%d:%02d', $i, $s );
	}

	/**
	 * "3 days ago", using WordPress core so it's translated with the site.
	 *
	 * @param string $iso_date Date from the API.
	 * @return string
	 */
	public static function time_ago( $iso_date ) {
		$timestamp = strtotime( (string) $iso_date );
		if ( ! $timestamp ) {
			return '';
		}
		/* translators: %s: time difference, e.g. "3 days". */
		return sprintf( __( '%s ago', 'youtube-shorts-embed' ), human_time_diff( $timestamp ) );
	}
}
