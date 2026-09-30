<?php
/**
 * Slider layout template.
 * Variables: $videos, $next_token, $channel_id
 * Optional overrides: $slider_per_view, $slider_autoplay, $slider_arrows, $slider_dots
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$settings        = yse_get_settings();
$slider_per_view = isset( $slider_per_view ) ? intval( $slider_per_view ) : intval( $settings['slider_per_view'] ?? 4 );
$slider_autoplay = isset( $slider_autoplay ) ? intval( $slider_autoplay ) : intval( $settings['slider_autoplay'] ?? 0 );
$slider_arrows   = isset( $slider_arrows )   ? intval( $slider_arrows )   : intval( $settings['slider_arrows'] ?? 1 );
$slider_dots     = isset( $slider_dots )     ? intval( $slider_dots )     : intval( $settings['slider_dots'] ?? 1 );
$slider_speed    = intval( $settings['slider_speed'] ?? 400 );
$show_header     = ! empty( $settings['show_section_header'] );
$sec_title       = $settings['section_title'] ?? '';
$sec_desc        = $settings['section_desc'] ?? '';
$view_all        = ! empty( $settings['show_view_all'] );
$view_all_txt    = $settings['view_all_text'] ?? __( 'View all shorts', 'youtube-shorts-embed' );
$channel_url     = 'https://www.youtube.com/channel/' . rawurlencode( $channel_id ) . '/shorts';
?>
<div class="yse-wrap yse-slider-wrap"
	data-channel-id="<?php echo esc_attr( $channel_id ); ?>"
	data-layout="slider"
	data-per-view="<?php echo esc_attr( $slider_per_view ); ?>"
	data-autoplay="<?php echo esc_attr( $slider_autoplay ); ?>"
	data-speed="<?php echo esc_attr( $slider_speed ); ?>"
	data-dots="<?php echo esc_attr( $slider_dots ); ?>"
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

	<div class="yse-slider-outer">

		<?php if ( $slider_arrows ) : ?>
		<button type="button" class="yse-slider-arrow yse-slider-prev" aria-label="<?php esc_attr_e( 'Previous', 'youtube-shorts-embed' ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
		</button>
		<?php endif; ?>

		<div class="yse-slider-viewport" style="--yse-slider-per-view:<?php echo esc_attr( $slider_per_view ); ?>">
			<div class="yse-slider-track">
				<?php
				$index = 1;
				foreach ( $videos as $video ) :
				?>
				<div class="yse-slide">
					<?php include __DIR__ . '/tile.php'; ?>
				</div>
				<?php
					$index++;
				endforeach;
				?>
			</div>
		</div>

		<?php if ( $slider_arrows ) : ?>
		<button type="button" class="yse-slider-arrow yse-slider-next" aria-label="<?php esc_attr_e( 'Next', 'youtube-shorts-embed' ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
		</button>
		<?php endif; ?>

	</div>

	<?php if ( $slider_dots ) : ?>
	<div class="yse-slider-dots"></div>
	<?php endif; ?>

	<?php include __DIR__ . '/lightbox.php'; ?>
</div>
