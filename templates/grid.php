<?php
/**
 * Grid layout template.
 * Variables: $videos, $next_token, $channel_id, $columns, $per_page
 * Optional:  $source, $playlist_id, $show_filters
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$settings     = yse_get_settings();
$btn_text     = sanitize_text_field( $settings['btn_text'] ?? __( 'Load More', 'youtube-shorts-embed' ) );
$show_header  = ! empty( $settings['show_section_header'] );
$sec_title    = $settings['section_title'] ?? '';
$sec_desc     = $settings['section_desc'] ?? '';
$view_all     = ! empty( $settings['show_view_all'] );
$view_all_txt = $settings['view_all_text'] ?? __( 'View all shorts', 'youtube-shorts-embed' );
$channel_url  = 'https://www.youtube.com/channel/' . rawurlencode( $channel_id ) . '/shorts';
?>
<div class="yse-wrap yse-grid-wrap"
	data-channel-id="<?php echo esc_attr( $channel_id ); ?>"
	data-sig="<?php echo esc_attr( yse_source_signature( $channel_id, $playlist_id ?? '' ) ); ?>"
	data-per-page="<?php echo esc_attr( $per_page ); ?>"
	data-columns="<?php echo esc_attr( $columns ); ?>"
	data-layout="grid"
	data-source="<?php echo esc_attr( $source ?? 'shorts' ); ?>"
	data-sort="recent"
	data-next-token="<?php echo esc_attr( $next_token ?? '' ); ?>"
	data-loaded-count="<?php echo esc_attr( count( $videos ) ); ?>"
>
	<?php if ( $show_header && $sec_title ) : ?>
	<div class="yse-section-header">
		<div>
			<h2 class="yse-section-title"><?php echo esc_html( $sec_title ); ?></h2>
			<?php if ( $sec_desc ) : ?>
			<p class="yse-section-desc"><?php echo esc_html( $sec_desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $view_all ) : ?>
		<a href="<?php echo esc_url( $channel_url ); ?>" target="_blank" rel="noopener noreferrer" class="yse-view-all-btn">
			<?php echo esc_html( $view_all_txt ); ?> &rarr;
		</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>


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
	<div class="yse-grid" style="--yse-columns:<?php echo esc_attr( $columns ); ?>">
		<?php
		$index = 1;
		$tile_tpl = ( 'shorts' === ( $source ?? 'shorts' ) ) ? '/tile.php' : '/video-tile.php';
		foreach ( $videos as $video ) :
			include __DIR__ . $tile_tpl;
			$index++;
		endforeach;
		?>
	</div>

	<div class="yse-load-more-wrap"<?php echo empty( $next_token ) ? ' style="display:none"' : ''; ?>>
		<button type="button" class="yse-load-more-btn"><?php echo esc_html( $btn_text ); ?></button>
	</div>

	<?php include __DIR__ . '/lightbox.php'; ?>
</div>
