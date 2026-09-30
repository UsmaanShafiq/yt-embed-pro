<?php
/**
 * Elementor widget for YouTube Shorts Embed.
 *
 * This file is loaded only inside the elementor/widgets/register hook,
 * so \Elementor\Widget_Base is guaranteed to exist when this class is defined.
 *
 * @package YT_Shorts_Embed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class YSE_Elementor_Widget
 */
class YSE_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'yt_shorts_embed';
	}

	public function get_title() {
		return esc_html__( 'YouTube Shorts', 'youtube-shorts-embed' );
	}

	public function get_icon() {
		return 'eicon-youtube';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'youtube', 'shorts', 'video', 'embed' );
	}

	protected function register_controls() {
		$settings = yse_get_settings();

		/* ── Content ── */
		$this->start_controls_section(
			'section_source',
			array(
				'label' => esc_html__( 'Source', 'youtube-shorts-embed' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'channel_id',
			array(
				'label'       => esc_html__( 'Channel ID', 'youtube-shorts-embed' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'UCxxxxxxxxxxxxxxxx',
				'default'     => $settings['channel_id'] ?? '',
				'description' => esc_html__( 'YouTube Channel ID starting with UC. Find it in YouTube Studio → Settings → Channel → Advanced.', 'youtube-shorts-embed' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $settings['layout'] ?? 'grid',
				'options' => array(
					'grid'   => esc_html__( 'Portrait Grid', 'youtube-shorts-embed' ),
					'slider' => esc_html__( 'Slider',        'youtube-shorts-embed' ),
				),
			)
		);

		$this->end_controls_section();

		/* ── Grid settings ── */
		$this->start_controls_section(
			'section_grid',
			array(
				'label'     => esc_html__( 'Grid Settings', 'youtube-shorts-embed' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array( 'layout' => 'grid' ),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => (string) ( $settings['columns'] ?? 4 ),
				'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			)
		);

		$this->add_control(
			'per_page',
			array(
				'label'   => esc_html__( 'Videos per page', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => $settings['per_page'] ?? 9,
				'min'     => 3,
				'max'     => 50,
				'step'    => 3,
			)
		);

		$this->end_controls_section();

		/* ── Slider settings ── */
		$this->start_controls_section(
			'section_slider',
			array(
				'label'     => esc_html__( 'Slider Settings', 'youtube-shorts-embed' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array( 'layout' => 'slider' ),
			)
		);

		$this->add_control(
			'slider_per_view',
			array(
				'label'   => esc_html__( 'Visible slides', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => (string) ( $settings['slider_per_view'] ?? 4 ),
				'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5' ),
			)
		);

		$this->add_control(
			'slider_autoplay',
			array(
				'label'     => esc_html__( 'Autoplay', 'youtube-shorts-embed' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => ! empty( $settings['slider_autoplay'] ) ? 'yes' : '',
				'label_on'  => esc_html__( 'Yes', 'youtube-shorts-embed' ),
				'label_off' => esc_html__( 'No',  'youtube-shorts-embed' ),
			)
		);

		$this->add_control(
			'slider_arrows',
			array(
				'label'   => esc_html__( 'Show Arrows', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'slider_dots',
			array(
				'label'   => esc_html__( 'Show Dots', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		/* ── Style tab ── */
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Style', 'youtube-shorts-embed' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'   => esc_html__( 'Gap (px)', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => $settings['gap'] ?? 12,
				'min'     => 0,
				'max'     => 40,
			)
		);

		$this->add_control(
			'border_radius',
			array(
				'label'   => esc_html__( 'Border Radius (px)', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => $settings['border_radius'] ?? 12,
				'min'     => 0,
				'max'     => 30,
			)
		);

		$this->add_control(
			'tile_bg',
			array(
				'label'   => esc_html__( 'Tile Background', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $settings['tile_bg'] ?? '#1a1a1a',
			)
		);

		$this->add_control(
			'btn_bg',
			array(
				'label'   => esc_html__( 'Load More Button Color', 'youtube-shorts-embed' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $settings['btn_bg'] ?? '#FF0000',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$channel_id      = sanitize_text_field( $s['channel_id'] );
		$layout          = in_array( $s['layout'], array( 'grid', 'slider' ), true ) ? $s['layout'] : 'grid';
		$columns         = absint( $s['columns'] );
		$per_page        = max( 1, min( YSE_MAX_PER_PAGE, absint( $s['per_page'] ) ) );
		$gap             = absint( $s['gap'] );
		$border_radius   = absint( $s['border_radius'] );
		$tile_bg         = sanitize_hex_color( $s['tile_bg'] );
		$btn_bg          = sanitize_hex_color( $s['btn_bg'] );
		$slider_per_view = absint( $s['slider_per_view'] );
		$slider_autoplay = ( 'yes' === $s['slider_autoplay'] ) ? 1 : 0;
		$slider_arrows   = ( 'yes' === $s['slider_arrows'] )   ? 1 : 0;
		$slider_dots     = ( 'yes' === $s['slider_dots'] )     ? 1 : 0;

		if ( empty( $channel_id ) ) {
			echo '<p class="yse-notice yse-notice--error">'
				. esc_html__( 'Please enter a Channel ID in the widget settings.', 'youtube-shorts-embed' )
				. '</p>';
			return;
		}

		// Per-widget CSS variable overrides scoped to this widget instance.
		$uid = 'yse-widget-' . $this->get_id();
		echo "<style>
			#{$uid} {
				--yse-columns: {$columns};
				--yse-gap: {$gap}px;
				--yse-border-radius: {$border_radius}px;
				--yse-tile-bg: {$tile_bg};
				--yse-btn-bg: {$btn_bg};
				--yse-slider-per-view: {$slider_per_view};
			}
		</style>";
		echo "<div id='" . esc_attr( $uid ) . "'>";

		$api    = new YSE_API();
		$result = $api->fetch_shorts( $channel_id, $per_page );

		if ( is_wp_error( $result ) ) {
			echo '<p class="yse-notice yse-notice--error">' . esc_html( yse_public_error_message( $result ) ) . '</p>';
			echo '</div>';
			return;
		}

		$videos     = $result['videos'];
		$next_token = $result['nextPageToken'];

		if ( empty( $videos ) ) {
			echo '<p class="yse-notice yse-notice--empty">'
				. esc_html__( 'No Shorts found for this channel.', 'youtube-shorts-embed' )
				. '</p>';
			echo '</div>';
			return;
		}

		include YSE_PLUGIN_DIR . 'templates/' . $layout . '.php';
		echo '</div>';
	}
}
