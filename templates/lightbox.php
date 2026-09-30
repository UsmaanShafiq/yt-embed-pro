<?php
/**
 * Lightbox overlay — included INSIDE .yse-wrap so JS can find it via wrap.querySelector().
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="yse-lightbox" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Video player', 'youtube-shorts-embed' ); ?>" hidden>
	<div class="yse-lightbox-backdrop"></div>
	<div class="yse-lightbox-content">
		<button type="button" class="yse-lightbox-close" aria-label="<?php esc_attr_e( 'Close', 'youtube-shorts-embed' ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
		</button>
		<div class="yse-lightbox-iframe-wrap">
			<!-- Use class, not id, so multiple widgets on the same page don't conflict -->
			<iframe
				class="yse-lightbox-iframe"
				src=""
				frameborder="0"
				allow="autoplay; encrypted-media; fullscreen"
				allowfullscreen
				title="<?php esc_attr_e( 'YouTube Short', 'youtube-shorts-embed' ); ?>"
			></iframe>
		</div>
	</div>
</div>
