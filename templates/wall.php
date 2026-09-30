<?php
/**
 * Wall layout: landscape grid for regular videos and playlists.
 * Variables: $videos, $next_token, $channel_id, $columns, $per_page, $source
 * Optional:  $playlist_id, $show_filters
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = yse_get_settings();
$btn_text = sanitize_text_field( $settings['btn_text'] ?? __( 'Load More', 'youtube-shorts-embed' ) );
?>
<div class="yse-wrap yse-wall-wrap"
	data-channel-id="<?php echo esc_attr( $channel_id ); ?>"
	data-playlist-id="<?php echo esc_attr( $playlist_id ?? '' ); ?>"
	data-sig="<?php echo esc_attr( yse_source_signature( $channel_id, $playlist_id ?? '' ) ); ?>"
	data-source="<?php echo esc_attr( $source ?? 'videos' ); ?>"
	data-per-page="<?php echo esc_attr( $per_page ); ?>"
	data-columns="<?php echo esc_attr( $columns ); ?>"
	data-layout="wall"
	data-sort="recent"
	data-next-token="<?php echo esc_attr( $next_token ?? '' ); ?>"
	data-loaded-count="<?php echo esc_attr( count( $videos ) ); ?>"
	data-aspect="landscape"
>

	<?php if ( ! empty( $show_filters ) ) : ?>
	<div class="yse-sort-bar" data-sort="recent">
		<button type="button" class="yse-sort-btn is-active" data-sort="recent">
			<span class="yse-sort-icon">&#128337;</span> <?php esc_html_e( 'Recent', 'youtube-shorts-embed' ); ?>
		</button>
		<button type="button" class="yse-sort-btn" data-sort="popular">
			<span class="yse-sort-icon">&#128293;</span> <?php esc_html_e( 'Popular', 'youtube-shorts-embed' ); ?>
		</button>
	</div>
	<?php endif; ?>
	<div class="yse-wall" style="--yse-columns:<?php echo esc_attr( $columns ); ?>">
		<?php
		$index = 1;
		foreach ( $videos as $video ) :
			include __DIR__ . '/video-tile.php';
			$index++;
		endforeach;
		?>
	</div>

	<div class="yse-load-more-wrap"<?php echo empty( $next_token ) ? ' style="display:none"' : ''; ?>>
		<button type="button" class="yse-load-more-btn"><?php echo esc_html( $btn_text ); ?></button>
	</div>

	<?php include __DIR__ . '/lightbox.php'; ?>
</div>
