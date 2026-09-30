<?php
/**
 * Tabs layout: Shorts tab and long-form Videos tab.
 * Variables: $shorts_videos, $shorts_next_token, $long_videos, $long_next_token,
 *            $channel_id, $columns, $per_page
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = yse_get_settings();
$btn_text = sanitize_text_field( $settings['btn_text'] ?? __( 'Load More', 'youtube-shorts-embed' ) );
$uid      = 'yse-tabs-' . wp_unique_id();
?>
<div class="yse-wrap yse-tabs-wrap"
	data-channel-id="<?php echo esc_attr( $channel_id ); ?>"
	data-sig="<?php echo esc_attr( yse_source_signature( $channel_id, $playlist_id ?? '' ) ); ?>"
	data-layout="tabs"
>

	<!-- Tab buttons -->
	<div class="yse-tabs-nav" role="tablist">
		<button type="button" class="yse-tab-btn is-active"
			role="tab" aria-selected="true"
			aria-controls="<?php echo esc_attr( $uid ); ?>-shorts"
			data-tab="shorts">
			<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/></svg>
			<?php esc_html_e( 'Shorts', 'youtube-shorts-embed' ); ?>
		</button>
		<button type="button" class="yse-tab-btn"
			role="tab" aria-selected="false"
			aria-controls="<?php echo esc_attr( $uid ); ?>-videos"
			data-tab="videos">
			<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/></svg>
			<?php esc_html_e( 'Videos', 'youtube-shorts-embed' ); ?>
		</button>
	</div>

	<!-- Shorts panel -->
	<div id="<?php echo esc_attr( $uid ); ?>-shorts" class="yse-tab-panel is-active"
		role="tabpanel"
		data-source="shorts"
		data-per-page="<?php echo esc_attr( $per_page ); ?>"
		data-columns="<?php echo esc_attr( $columns ); ?>"
		data-next-token="<?php echo esc_attr( $shorts_next_token ?? '' ); ?>"
		data-loaded-count="<?php echo esc_attr( count( $shorts_videos ) ); ?>">

		<div class="yse-grid" style="--yse-columns:<?php echo esc_attr( $columns ); ?>">
			<?php
			$index = 1;
			foreach ( $shorts_videos as $video ) :
				include __DIR__ . '/tile.php';
				$index++;
			endforeach;
			?>
		</div>

		<?php if ( ! empty( $shorts_next_token ) ) : ?>
		<div class="yse-load-more-wrap">
			<button type="button" class="yse-load-more-btn"><?php echo esc_html( $btn_text ); ?></button>
		</div>
		<?php endif; ?>
	</div>

	<!-- Videos panel -->
	<div id="<?php echo esc_attr( $uid ); ?>-videos" class="yse-tab-panel"
		role="tabpanel"
		data-source="videos"
		data-per-page="<?php echo esc_attr( $per_page ); ?>"
		data-columns="<?php echo esc_attr( $columns ); ?>"
		data-next-token="<?php echo esc_attr( $long_next_token ?? '' ); ?>"
		data-loaded-count="<?php echo esc_attr( count( $long_videos ) ); ?>">

		<div class="yse-wall" style="--yse-columns:<?php echo esc_attr( $columns ); ?>">
			<?php
			$index = 1;
			foreach ( $long_videos as $video ) :
				include __DIR__ . '/video-tile.php';
				$index++;
			endforeach;
			?>
		</div>

		<?php if ( ! empty( $long_next_token ) ) : ?>
		<div class="yse-load-more-wrap">
			<button type="button" class="yse-load-more-btn"><?php echo esc_html( $btn_text ); ?></button>
		</div>
		<?php endif; ?>
	</div>

	<?php include __DIR__ . '/lightbox.php'; ?>
</div>
