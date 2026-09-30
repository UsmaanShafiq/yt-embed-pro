<?php
/**
 * Featured layout: hero player on the left, video list on the right.
 * Variables: $videos, $channel_id
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$featured = array_shift( $videos );
$rest     = $videos;
?>
<div class="yse-wrap yse-featured-wrap" data-channel-id="<?php echo esc_attr( $channel_id ); ?>" data-layout="featured">

	<div class="yse-featured-inner">

		<!-- Hero player -->
		<div class="yse-featured-hero">
			<div class="yse-featured-player">
				<iframe
					src="https://www.youtube.com/embed/<?php echo esc_attr( $featured['video_id'] ); ?>?rel=0&modestbranding=1"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
					title="<?php echo esc_attr( $featured['title'] ); ?>"
					class="yse-featured-iframe yse-hero-iframe"
				></iframe>
			</div>
			<p class="yse-featured-title yse-hero-title"><?php echo esc_html( $featured['title'] ); ?></p>
			<div class="yse-featured-meta">
				<?php
				$fv = YSE_Format::views( $featured['view_count'] ?? '' );
				$ft = YSE_Format::time_ago( $featured['published_at'] ?? '' );
				if ( $fv ) echo '<span>' . esc_html( $fv ) . '</span>';
				if ( $ft ) echo '<span>' . esc_html( $ft ) . '</span>';
				?>
			</div>
		</div>

		<!-- Side list -->
		<div class="yse-featured-list">
			<p class="yse-featured-list-label"><?php esc_html_e( 'Up next', 'youtube-shorts-embed' ); ?></p>
			<?php foreach ( $rest as $video ) :
				$views    = YSE_Format::views( $video['view_count'] ?? '' );
				$time_ago = YSE_Format::time_ago( $video['published_at'] ?? '' );
			?>
			<div class="yse-featured-item yse-featured-item-btn"
				data-video-id="<?php echo esc_attr( $video['video_id'] ); ?>"
				data-title="<?php echo esc_attr( $video['title'] ); ?>"
				role="button" tabindex="0"
				aria-label="<?php echo esc_attr( $video['title'] ); ?>"
			>
				<div class="yse-featured-item-thumb">
					<img
						src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . $video['video_id'] . '/mqdefault.jpg' ); ?>"
						alt="<?php echo esc_attr( $video['title'] ); ?>"
						loading="lazy"
						class="yse-tile-thumb"
						data-video-id="<?php echo esc_attr( $video['video_id'] ); ?>"
					/>
					<div class="yse-tile-overlay" aria-hidden="true">
						<span class="yse-play-btn" style="width:36px;height:36px;">
							<svg viewBox="0 0 24 24" fill="white" width="18" height="18"><path d="M8 5v14l11-7z"/></svg>
						</span>
					</div>
				</div>
				<div class="yse-featured-item-info">
					<p class="yse-featured-item-title"><?php echo esc_html( $video['title'] ); ?></p>
					<span class="yse-featured-item-meta">
						<?php echo esc_html( implode( ' · ', array_filter( array( $views, $time_ago ) ) ) ); ?>
					</span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

	</div>

	<?php include __DIR__ . '/lightbox.php'; ?>
</div>
