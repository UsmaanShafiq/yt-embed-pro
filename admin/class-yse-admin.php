<?php
/**
 * Admin settings page — premium UI.
 *
 * @package YT_Shorts_Embed
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class YSE_Admin {

	const OPTION_NAME  = 'yse_settings';
	const OPTION_GROUP = 'yse_settings_group';

	public function init() {
		add_action( 'admin_menu',            array( $this, 'add_options_page' ) );
		add_action( 'admin_init',            array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_yse_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_yse_clear_cache',     array( $this, 'ajax_clear_cache' ) );
	}

	public function add_options_page() {
		add_menu_page(
			esc_html__( 'YT Embed Pro', 'youtube-shorts-embed' ),
			esc_html__( 'YT Embed Pro', 'youtube-shorts-embed' ),
			'manage_options',
			'youtube-shorts-embed',
			array( $this, 'render_settings_page' ),
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#a7aaad" d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1C24 15.9 24 12 24 12s0-3.9-.5-5.8zM9.5 15.6V8.4L15.8 12l-6.3 3.6z"/></svg>' ),
			30
		);
	}

	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		/* General */
		add_settings_section( 'yse_section_general', '', '__return_false', 'yse-general' );
		add_settings_field( 'yse_api_key',       __( 'YouTube API Key',  'youtube-shorts-embed' ), array( $this, 'field_api_key' ),       'yse-general', 'yse_section_general' );
		add_settings_field( 'yse_channel_id',    __( 'Channel ID',       'youtube-shorts-embed' ), array( $this, 'field_channel_id' ),    'yse-general', 'yse_section_general' );
		add_settings_field( 'yse_default_layout',__( 'Default Layout',   'youtube-shorts-embed' ), array( $this, 'field_default_layout' ),'yse-general', 'yse_section_general' );
		add_settings_field( 'yse_cache_hours',   __( 'Cache Duration',   'youtube-shorts-embed' ), array( $this, 'field_cache_hours' ),   'yse-general', 'yse_section_general' );

		/* Grid */
		add_settings_section( 'yse_section_grid', '', '__return_false', 'yse-grid' );
		add_settings_field( 'yse_columns',  __( 'Columns',           'youtube-shorts-embed' ), array( $this, 'field_columns' ),  'yse-grid', 'yse_section_grid' );
		add_settings_field( 'yse_per_page', __( 'Videos per page',   'youtube-shorts-embed' ), array( $this, 'field_per_page' ), 'yse-grid', 'yse_section_grid' );
		add_settings_field( 'yse_btn_text', __( 'Load More text',    'youtube-shorts-embed' ), array( $this, 'field_btn_text' ), 'yse-grid', 'yse_section_grid' );

		/* Slider */
		add_settings_section( 'yse_section_slider', '', '__return_false', 'yse-slider' );
		add_settings_field( 'yse_slider_per_view', __( 'Visible Slides',   'youtube-shorts-embed' ), array( $this, 'field_slider_per_view' ), 'yse-slider', 'yse_section_slider' );
		add_settings_field( 'yse_slider_speed',    __( 'Transition Speed', 'youtube-shorts-embed' ), array( $this, 'field_slider_speed' ),    'yse-slider', 'yse_section_slider' );
		add_settings_field( 'yse_slider_autoplay', __( 'Autoplay',         'youtube-shorts-embed' ), array( $this, 'field_slider_autoplay' ), 'yse-slider', 'yse_section_slider' );
		add_settings_field( 'yse_slider_arrows',   __( 'Show Arrows',      'youtube-shorts-embed' ), array( $this, 'field_slider_arrows' ),   'yse-slider', 'yse_section_slider' );
		add_settings_field( 'yse_slider_dots',     __( 'Show Dots',        'youtube-shorts-embed' ), array( $this, 'field_slider_dots' ),     'yse-slider', 'yse_section_slider' );

		/* Style */
		add_settings_section( 'yse_section_style', '', '__return_false', 'yse-style' );
		add_settings_field( 'yse_gap',                __( 'Tile Gap (px)',            'youtube-shorts-embed' ), array( $this, 'field_gap' ),                'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_border_radius',     __( 'Border Radius (px)',       'youtube-shorts-embed' ), array( $this, 'field_border_radius' ),     'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_tile_border_width', __( 'Tile Border Width (px)',   'youtube-shorts-embed' ), array( $this, 'field_tile_border_width' ), 'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_tile_border_color', __( 'Tile Border Color',        'youtube-shorts-embed' ), array( $this, 'field_tile_border_color' ), 'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_tile_bg',           __( 'Tile Background',          'youtube-shorts-embed' ), array( $this, 'field_tile_bg' ),           'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_overlay_color',     __( 'Lightbox Overlay Color',   'youtube-shorts-embed' ), array( $this, 'field_overlay_color' ),     'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_btn_bg',            __( 'Button Color',             'youtube-shorts-embed' ), array( $this, 'field_btn_bg' ),            'yse-style', 'yse_section_style' );
		add_settings_field( 'yse_btn_color',         __( 'Button Text Color',        'youtube-shorts-embed' ), array( $this, 'field_btn_color' ),         'yse-style', 'yse_section_style' );
	}

	/* ── Field helpers ────────────────────────────────────────────────── */

	private function get( $key ) {
		$settings = yse_get_settings();
		return $settings[ $key ] ?? '';
	}

	public function field_api_key() { ?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[api_key]" value="<?php echo esc_attr( $this->get( 'api_key' ) ); ?>" class="regular-text" autocomplete="off" />
		<button type="button" id="yse-test-btn" class="button yse-inline-btn"><?php esc_html_e( 'Test Connection', 'youtube-shorts-embed' ); ?></button>
		<span id="yse-test-result" class="yse-test-result"></span>
		<p class="description"><?php esc_html_e( 'Get a free key at Google Cloud Console → YouTube Data API v3.', 'youtube-shorts-embed' ); ?></p>
	<?php }

	public function field_channel_id() {
		printf( '<input type="text" name="%s[channel_id]" value="%s" class="regular-text" placeholder="UCxxxxxxxxxxxxxxxx" /><p class="description">%s</p>',
			esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'channel_id' ) ),
			esc_html__( 'YouTube Studio → Settings → Channel → Advanced settings.', 'youtube-shorts-embed' ) );
	}

	public function field_default_layout() {
		$val = $this->get( 'layout' );
		$layouts = array(
			'grid'     => __( 'Portrait Grid — Shorts',      'youtube-shorts-embed' ),
			'slider'   => __( 'Slider — Shorts',             'youtube-shorts-embed' ),
			'wall'     => __( 'Video Wall — Long Videos',    'youtube-shorts-embed' ),
			'featured' => __( 'Featured Hero — Videos',     'youtube-shorts-embed' ),
		);
		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[layout]">';
		foreach ( $layouts as $v => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( $val, $v, false ), esc_html( $label ) );
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Default when no layout= is specified in the shortcode. Add tabs="true" to any shortcode for a Shorts/Videos tab switcher.', 'youtube-shorts-embed' ) . '</p>';
	}

	public function field_cache_hours() {
		$val = $this->get( 'cache_hours' );
		$opts = array( 0=>'Disabled', 1=>'1 hour', 2=>'2 hours', 6=>'6 hours', 12=>'12 hours', 24=>'24 hours' );
		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[cache_hours]">';
		foreach ( $opts as $v => $l ) printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( $val, $v, false ), esc_html( $l ) );
		echo '</select><p class="description">' . esc_html__( 'Caches YouTube responses to save API quota.', 'youtube-shorts-embed' ) . '</p>';
	}

	public function field_columns() {
		$val = $this->get( 'columns' );
		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[columns]">';
		foreach ( array( 2, 3, 4, 5, 6 ) as $c ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $c ), selected( $val, $c, false ), esc_html( $c ) );
		}
		echo '</select>';
	}

	public function field_per_page() {
		printf(
			'<input type="number" name="%s[per_page]" value="%s" min="1" max="%d" class="small-text" /><p class="description">%s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $this->get( 'per_page' ) ),
			(int) YSE_MAX_PER_PAGE,
			esc_html__( 'Videos shown at first. Rounded up to fill complete rows, up to 50.', 'youtube-shorts-embed' )
		);
	}

	public function field_btn_text() {
		printf( '<input type="text" name="%s[btn_text]" value="%s" class="regular-text" />', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'btn_text' ) ) );
	}

	public function field_slider_per_view() {
		$val = $this->get( 'slider_per_view' );
		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[slider_per_view]">';
		foreach ( array( 2, 3, 4, 5 ) as $v ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( $val, $v, false ), esc_html( $v ) );
		}
		echo '</select><p class="description">' . esc_html__( 'Number of slides visible at once.', 'youtube-shorts-embed' ) . '</p>';
	}

	public function field_slider_speed() {
		printf( '<input type="number" name="%s[slider_speed]" value="%s" min="100" max="1500" step="50" class="small-text" /> ms', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'slider_speed' ) ) );
	}

	public function field_slider_autoplay() { printf( '<input type="checkbox" name="%s[slider_autoplay]" value="1" %s />', esc_attr( self::OPTION_NAME ), checked( $this->get( 'slider_autoplay' ), 1, false ) ); }
	public function field_slider_arrows()   { printf( '<input type="checkbox" name="%s[slider_arrows]" value="1" %s />',   esc_attr( self::OPTION_NAME ), checked( $this->get( 'slider_arrows' ),   1, false ) ); }
	public function field_slider_dots()     { printf( '<input type="checkbox" name="%s[slider_dots]" value="1" %s />',     esc_attr( self::OPTION_NAME ), checked( $this->get( 'slider_dots' ),     1, false ) ); }

	public function field_gap() {
		printf( '<input type="number" name="%s[gap]" value="%s" min="0" max="40" class="small-text" /> px', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'gap' ) ) );
	}

	public function field_border_radius() {
		printf( '<input type="number" name="%s[border_radius]" value="%s" min="0" max="30" class="small-text" /> px', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'border_radius' ) ) );
	}

	public function field_tile_border_width() {
		printf( '<input type="number" name="%s[tile_border_width]" value="%s" min="0" max="8" class="small-text" /> px<p class="description">%s</p>',
			esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'tile_border_width' ) ),
			esc_html__( 'Set to 0 to hide the border.', 'youtube-shorts-embed' ) );
	}

	public function field_tile_border_color() {
		printf( '<input type="text" name="%s[tile_border_color]" value="%s" class="yse-color-picker" />', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'tile_border_color' ) ) );
	}

	public function field_tile_bg() {
		printf( '<input type="text" name="%s[tile_bg]" value="%s" class="yse-color-picker" />', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'tile_bg' ) ) );
	}

	public function field_overlay_color() {
		printf( '<input type="text" name="%s[overlay_color]" value="%s" class="regular-text" /><p class="description">rgba() values accepted.</p>', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'overlay_color' ) ) );
	}

	public function field_btn_bg() {
		printf( '<input type="text" name="%s[btn_bg]" value="%s" class="yse-color-picker" />', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'btn_bg' ) ) );
	}

	public function field_btn_color() {
		printf( '<input type="text" name="%s[btn_color]" value="%s" class="yse-color-picker" />', esc_attr( self::OPTION_NAME ), esc_attr( $this->get( 'btn_color' ) ) );
	}

	/* ── Sanitize ─────────────────────────────────────────────────────── */

	public function sanitize_settings( $input ) {
		$clean = yse_get_settings();

		$str_keys = array( 'api_key', 'channel_id', 'btn_text' );
		foreach ( $str_keys as $k ) {
			if ( array_key_exists( $k, $input ) ) $clean[ $k ] = sanitize_text_field( $input[ $k ] );
		}
		if ( array_key_exists( 'overlay_color', $input ) ) {
			$raw = sanitize_text_field( $input['overlay_color'] );
			$clean['overlay_color'] = preg_match( '/^(rgba?\([\d\s,\.]+\)|#[0-9a-fA-F]{3,8}|[a-z]+)$/', $raw )
				? $raw : 'rgba(0,0,0,0.85)';
		}
		if ( array_key_exists( 'layout', $input ) ) {
			$clean['layout'] = in_array( $input['layout'], array( 'grid', 'slider', 'wall', 'featured' ), true ) ? $input['layout'] : 'grid';
		}
		if ( array_key_exists( 'columns', $input ) ) {
			$clean['columns'] = max( 2, min( 6, absint( $input['columns'] ) ) );
		}
		if ( array_key_exists( 'per_page', $input ) ) {
			// Round up to full rows, but stay within the API's 50 videos per request.
			$cols              = (int) $clean['columns'];
			$rounded           = (int) ceil( max( 1, absint( $input['per_page'] ) ) / $cols ) * $cols;
			$clean['per_page'] = min( $rounded, (int) floor( YSE_MAX_PER_PAGE / $cols ) * $cols );
		}
		if ( array_key_exists( 'cache_hours', $input ) )   $clean['cache_hours']   = absint( $input['cache_hours'] );
		if ( array_key_exists( 'border_radius', $input ) ) $clean['border_radius'] = absint( $input['border_radius'] );
		if ( array_key_exists( 'gap', $input ) )           $clean['gap']           = absint( $input['gap'] );
		if ( array_key_exists( 'tile_border_width', $input ) ) $clean['tile_border_width'] = max( 0, min( 8, absint( $input['tile_border_width'] ) ) );
		if ( array_key_exists( 'tile_border_color', $input ) ) $clean['tile_border_color'] = sanitize_hex_color( $input['tile_border_color'] ) ?: '#ffffff';
		if ( array_key_exists( 'tile_bg', $input ) )       $clean['tile_bg']       = sanitize_hex_color( $input['tile_bg'] ) ?: '#111111';
		if ( array_key_exists( 'btn_bg', $input ) )        $clean['btn_bg']        = sanitize_hex_color( $input['btn_bg'] )  ?: '#FF0000';
		if ( array_key_exists( 'btn_color', $input ) )     $clean['btn_color']     = sanitize_hex_color( $input['btn_color'] ) ?: '#ffffff';

		// Checkboxes are only present in the form when on the Slider tab.
		// Guard with array_key_exists so saving from other tabs doesn't reset them to 0.
		if ( array_key_exists( 'slider_speed', $input ) ) {
			$clean['slider_autoplay'] = ! empty( $input['slider_autoplay'] ) ? 1 : 0;
			$clean['slider_arrows']   = ! empty( $input['slider_arrows'] )   ? 1 : 0;
			$clean['slider_dots']     = ! empty( $input['slider_dots'] )     ? 1 : 0;
		}

		if ( array_key_exists( 'slider_speed', $input ) )    $clean['slider_speed']    = absint( $input['slider_speed'] );
		if ( array_key_exists( 'slider_per_view', $input ) ) {
			$sv = max( 2, min( 5, absint( $input['slider_per_view'] ) ) );
			$clean['slider_per_view'] = $sv;
		}

		// New settings can change what the feeds show, so drop cached responses.
		YSE_API::flush_cache();

		return $clean;
	}

	/* ── Admin assets ─────────────────────────────────────────────────── */

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_youtube-shorts-embed' !== $hook ) return;

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'yse-admin',
			YSE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			YSE_VERSION
		);
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'yse-admin',
			YSE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			YSE_VERSION,
			true
		);
		wp_localize_script( 'yse-admin', 'yseAdmin', array(
			'nonce'    => wp_create_nonce( 'yse_admin_nonce' ),
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'testing'  => esc_html__( 'Testing…',    'youtube-shorts-embed' ),
			'clearing' => esc_html__( 'Clearing…',   'youtube-shorts-embed' ),
		) );
	}

	/* ── AJAX ─────────────────────────────────────────────────────────── */

	public function ajax_test_connection() {
		check_ajax_referer( 'yse_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );

		$api_key    = sanitize_text_field( wp_unslash( $_POST['api_key']    ?? '' ) );
		$channel_id = sanitize_text_field( wp_unslash( $_POST['channel_id'] ?? '' ) );
		$api    = new YSE_API();
		$result = $api->test_connection( $api_key, $channel_id );

		if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ) );

		wp_send_json_success( array( 'message' => sprintf( /* translators: %d: number of Shorts on the channel. */
			esc_html__( 'Connected. This channel has %d Shorts.', 'youtube-shorts-embed' ), $result['found'] ) ) );
	}

	public function ajax_clear_cache() {
		check_ajax_referer( 'yse_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		YSE_API::flush_cache();
		wp_send_json_success( array( 'message' => esc_html__( '✓ Cache cleared.', 'youtube-shorts-embed' ) ) );
	}

	/* ── Settings page ────────────────────────────────────────────────── */

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
		$tabs = array(
			'dashboard' => array( 'label' => __( 'Dashboard', 'youtube-shorts-embed' ), 'icon' => '⊞' ),
			'general'   => array( 'label' => __( 'General',   'youtube-shorts-embed' ), 'icon' => '⚙' ),
			'grid'      => array( 'label' => __( 'Grid',      'youtube-shorts-embed' ), 'icon' => '▦' ),
			'slider'    => array( 'label' => __( 'Slider',    'youtube-shorts-embed' ), 'icon' => '↔' ),
			'style'     => array( 'label' => __( 'Style',     'youtube-shorts-embed' ), 'icon' => '🎨' ),
		);
		$page_map = array( 'general' => 'yse-general', 'grid' => 'yse-grid', 'slider' => 'yse-slider', 'style' => 'yse-style' );
		?>
		<div class="yse-admin-wrap">

			<!-- Header -->
			<div class="yse-admin-header">
				<div class="yse-admin-header-inner">
					<div class="yse-admin-logo">
						<svg viewBox="0 0 32 32" width="38" height="38"><rect width="32" height="32" rx="8" fill="#fff" fill-opacity=".15"/><path d="M7 10.5A2.5 2.5 0 019.5 8h13A2.5 2.5 0 0125 10.5v11a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 017 21.5v-11z" fill="#fff" fill-opacity=".2"/><path d="M13.5 11.5l7 4.5-7 4.5v-9z" fill="#fff"/></svg>
						<div>
							<h1>YT Embed Pro</h1>
							<span>v<?php echo esc_html( YSE_VERSION ); ?></span>
						</div>
					</div>
					<div class="yse-admin-header-right">
						<span class="yse-header-author">by <a href="https://usmaaan.com" target="_blank" rel="noopener">Usman Shafiq</a></span>
					</div>
				</div>
			</div>

			<!-- Nav tabs -->
			<div class="yse-admin-nav">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'youtube-shorts-embed', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"
					   class="yse-nav-tab <?php echo $active_tab === $key ? 'is-active' : ''; ?>">
						<span class="yse-nav-icon"><?php echo esc_html( $tab['icon'] ); ?></span>
						<?php echo esc_html( $tab['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<!-- Content -->
			<div class="yse-admin-content">

				<?php if ( 'dashboard' === $active_tab ) : ?>
					<?php $this->render_dashboard_tab(); ?>

				<?php else : ?>
					<form method="post" action="options.php">
						<?php
						settings_fields( self::OPTION_GROUP );
						do_settings_sections( $page_map[ $active_tab ] ?? 'yse-general' );
						submit_button( __( 'Save Settings', 'youtube-shorts-embed' ) );
						?>
					</form>

					<?php if ( 'general' === $active_tab ) : ?>
					<div class="yse-admin-card" style="margin-top:16px">
						<h3><?php esc_html_e( 'Quick Actions', 'youtube-shorts-embed' ); ?></h3>
						<p>
							<button type="button" id="yse-clear-cache-btn" class="button yse-inline-btn">
								🗑 <?php esc_html_e( 'Clear Cache', 'youtube-shorts-embed' ); ?>
							</button>
							<span id="yse-clear-result" class="yse-test-result"></span>
						</p>
					</div>
					<?php endif; ?>
				<?php endif; ?>

			</div><!-- .yse-admin-content -->
		</div><!-- .yse-admin-wrap -->
		<?php
	}

	private function render_dashboard_tab() {
		$settings = yse_get_settings();
		$has_api  = ! empty( $settings['api_key'] );
		$has_ch   = ! empty( $settings['channel_id'] );
		?>
		<div class="yse-dashboard-grid">

			<!-- Quick setup card -->
			<div class="yse-admin-card yse-setup-card">
				<h2><?php esc_html_e( 'Quick Setup', 'youtube-shorts-embed' ); ?></h2>
				<ol class="yse-setup-steps">
					<li class="<?php echo $has_api ? 'done' : ''; ?>">
						<span class="yse-step-num"><?php echo $has_api ? '✓' : '1'; ?></span>
						<div>
							<strong><?php esc_html_e( 'YouTube API Key', 'youtube-shorts-embed' ); ?></strong>
							<p><?php esc_html_e( 'Get a free key from Google Cloud Console and enter it in General settings.', 'youtube-shorts-embed' ); ?></p>
							<?php if ( ! $has_api ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=youtube-shorts-embed&tab=general' ) ); ?>" class="button button-primary yse-sm-btn">
									<?php esc_html_e( 'Add API Key →', 'youtube-shorts-embed' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</li>
					<li class="<?php echo $has_ch ? 'done' : ''; ?>">
						<span class="yse-step-num"><?php echo $has_ch ? '✓' : '2'; ?></span>
						<div>
							<strong><?php esc_html_e( 'Channel ID', 'youtube-shorts-embed' ); ?></strong>
							<p><?php esc_html_e( 'Enter your YouTube channel ID (starts with UC…) in General settings.', 'youtube-shorts-embed' ); ?></p>
							<?php if ( ! $has_ch ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=youtube-shorts-embed&tab=general' ) ); ?>" class="button button-primary yse-sm-btn">
									<?php esc_html_e( 'Add Channel ID →', 'youtube-shorts-embed' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</li>
					<li>
						<span class="yse-step-num">3</span>
						<div>
							<strong><?php esc_html_e( 'Embed Anywhere', 'youtube-shorts-embed' ); ?></strong>
							<p><?php esc_html_e( 'Use a shortcode or the Elementor widget to display your feed.', 'youtube-shorts-embed' ); ?></p>
						</div>
					</li>
				</ol>
			</div>

			<!-- Shortcodes reference -->
			<div class="yse-admin-card">
				<h2><?php esc_html_e( 'Shortcodes', 'youtube-shorts-embed' ); ?></h2>
				<p class="yse-code-label"><?php esc_html_e( 'Shorts Grid', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed source="shorts" layout="grid" columns="4"]</code>

				<p class="yse-code-label"><?php esc_html_e( 'Shorts Slider', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed source="shorts" layout="slider"]</code>

				<p class="yse-code-label"><?php esc_html_e( 'Video Wall — long videos', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed source="videos" layout="wall" columns="3"]</code>

				<p class="yse-code-label"><?php esc_html_e( 'Shorts / Videos tab switcher', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed tabs="true"]</code>
				<p class="yse-code-note"><?php esc_html_e( 'Shows the channel\'s Shorts and long-form videos in two tabs.', 'youtube-shorts-embed' ); ?></p>

				<p class="yse-code-label"><?php esc_html_e( 'Sort by Recent or Popular', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed source="shorts" layout="grid" filters="true"]</code>
				<code class="yse-code-block">[yt_embed source="videos" layout="wall" filters="true"]</code>
				<p class="yse-code-note"><?php esc_html_e( 'filters="true" adds a Recent / Popular sort bar above the grid. Clicking Popular fetches highest-view videos from your channel via the YouTube Search API.', 'youtube-shorts-embed' ); ?></p>

				<p class="yse-code-label"><?php esc_html_e( 'Featured Hero', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed source="videos" layout="featured"]</code>

				<p class="yse-code-label"><?php esc_html_e( 'Playlist', 'youtube-shorts-embed' ); ?></p>
				<code class="yse-code-block">[yt_embed source="playlist" playlist_id="PLxxxx" layout="wall"]</code>
			</div>

			<!-- Layouts card -->
			<div class="yse-admin-card yse-layouts-card">
				<h2><?php esc_html_e( 'Available Layouts', 'youtube-shorts-embed' ); ?></h2>
				<div class="yse-layout-grid">
					<?php
					$layouts = array(
						array( 'icon' => '▦', 'name' => 'Portrait Grid',       'desc' => 'Shorts in 9:16 columns' ),
						array( 'icon' => '↔', 'name' => 'Shorts Slider',       'desc' => 'Swipeable carousel' ),
						array( 'icon' => '⊞', 'name' => 'Video Wall',          'desc' => '16:9 grid for long videos' ),
						array( 'icon' => '⊟', 'name' => 'Tab Switcher',        'desc' => 'Shorts and videos in two tabs' ),
						array( 'icon' => '◉', 'name' => 'Featured Hero',       'desc' => 'Hero player + side list' ),
					);
					foreach ( $layouts as $l ) : ?>
					<div class="yse-layout-card">
						<div class="yse-layout-icon"><?php echo esc_html( $l['icon'] ); ?></div>
						<strong><?php echo esc_html( $l['name'] ); ?></strong>
						<span><?php echo esc_html( $l['desc'] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
		<?php
	}
}
