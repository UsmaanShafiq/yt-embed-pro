<?php
/**
 * Landscape video tile, used by the wall, tabs and playlist feeds.
 * Variables: $video (array), $index (int, optional)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$views    = YSE_Format::views( $video['view_count'] ?? '' );
$time_ago = YSE_Format::time_ago( $video['published_at'] ?? '' );
$duration = YSE_Format::duration( $video['duration'] ?? '' );
?>
<div class="yse-tile yse-vtile"
	data-video-id="<?php echo esc_attr( $video['video_id'] ); ?>"
	role="button"
	tabindex="0"
	aria-label="<?php echo esc_attr( $video['title'] ); ?>"
>
	<div class="yse-vtile-thumb-wrap">

		<img
			src="<?php echo esc_url( $video['thumbnail'] ); ?>"
			alt="<?php echo esc_attr( $video['title'] ); ?>"
			loading="lazy"
			class="yse-tile-thumb"
			data-video-id="<?php echo esc_attr( $video['video_id'] ); ?>"
		/>

		<div class="yse-tile-overlay" aria-hidden="true">
			<span class="yse-play-btn">
				<svg viewBox="0 0 24 24" fill="white" width="28" height="28"><path d="M8 5v14l11-7z"/></svg>
			</span>
		</div>

		<?php if ( $duration ) : ?>
		<span class="yse-vtile-duration"><?php echo esc_html( $duration ); ?></span>
		<?php endif; ?>

	</div>

	<div class="yse-vtile-info">
		<p class="yse-vtile-title"><?php echo esc_html( $video['title'] ); ?></p>
		<div class="yse-vtile-meta">
			<?php if ( $views )    : ?><span><?php echo esc_html( $views ); ?></span><?php endif; ?>
			<?php if ( $time_ago ) : ?><span><?php echo esc_html( $time_ago ); ?></span><?php endif; ?>
		</div>
	</div>
</div>
