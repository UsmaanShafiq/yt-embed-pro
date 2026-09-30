<?php
/**
 * Single video tile.
 * Variables: $video (array), $index (int, 1-based badge number, optional)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$views    = YSE_Format::views( $video['view_count'] ?? '' );
$time_ago = YSE_Format::time_ago( $video['published_at'] ?? '' );
?>
<div class="yse-tile"
	data-video-id="<?php echo esc_attr( $video['video_id'] ); ?>"
	role="button"
	tabindex="0"
	aria-label="<?php echo esc_attr( $video['title'] ); ?>"
>
	<div class="yse-tile-thumb-wrap">

		<?php if ( ! empty( $index ) ) : ?>
		<span class="yse-tile-badge" aria-hidden="true"><?php echo esc_html( $index ); ?></span>
		<?php endif; ?>

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

		<!-- Bottom info overlay: title + meta row -->
		<div class="yse-tile-info">
			<p class="yse-tile-title"><?php echo esc_html( $video['title'] ); ?></p>
			<div class="yse-tile-meta-row">
				<?php if ( $views ) : ?>
				<span class="yse-tile-views">
					<svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10"><path d="M8 5v14l11-7z"/></svg>
					<?php echo esc_html( $views ); ?>
				</span>
				<?php endif; ?>
				<?php if ( $time_ago ) : ?>
				<span class="yse-tile-time"><?php echo esc_html( $time_ago ); ?></span>
				<?php endif; ?>
			</div>
		</div>

	</div>
</div>
