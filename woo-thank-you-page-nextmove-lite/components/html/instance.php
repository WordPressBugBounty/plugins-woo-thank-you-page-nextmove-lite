<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class XLWCTY_Text extends XLWCTY_Component {

	private static $instance = null;
	public $is_disable = true;
	public $viewpath = '';
	public $html_content = '';
	public $is_multiple = true;
	public $component_limit = 1;
	public $script = '';

	public function __construct( $order = false ) {
		parent::__construct();
		$this->viewpath = __DIR__ . '/views/view.php';
		add_action( 'xlwcty_after_component_data_setup_xlwcty_text', array( $this, 'setup_style' ), 10, 2 );
		add_action( 'xlwcty_after_components_loaded', array( $this, 'setup_fields' ) );
	}

	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * CMB2 sanitization callback for the HTML content editor.
	 *
	 * Users with the unfiltered_html capability (administrators on single site) keep raw HTML so they can embed
	 * third-party forms and scripts. Everyone else who can reach the builder (e.g. shop managers, or admins on
	 * multisite) is limited to post-safe HTML, the same rule WordPress applies to post content.
	 *
	 * @param mixed $value      Submitted value.
	 * @param array $field_args Field arguments (unused).
	 * @param mixed $field      CMB2_Field instance (unused).
	 *
	 * @return mixed
	 */
	public static function sanitize_html_content( $value, $field_args = array(), $field = null ) {
		if ( current_user_can( 'unfiltered_html' ) ) {
			return $value;
		}

		return is_array( $value ) ? array_map( 'wp_kses_post', $value ) : wp_kses_post( (string) $value );
	}

	public function setup_fields() {
		$this->fields = array(
			'heading'            => $this->get_slug() . '_heading_1',
			'heading_font_size'  => $this->get_slug() . '_heading_font_size_1',
			'heading_alignment'  => $this->get_slug() . '_heading_alignment_1',
			'html_content'       => $this->get_slug() . '_editor_1',
			'border_style'       => $this->get_slug() . '_border_style_1',
			'border_width'       => $this->get_slug() . '_border_width_1',
			'border_color'       => $this->get_slug() . '_border_color_1',
			'component_bg_color' => $this->get_slug() . '_component_bg_1',
		);
	}

	public function setup_style( $slug ) {
		if ( $this->is_enable() ) {
			if ( $this->data->heading_font_size != '' ) {
				$style['.xlwcty_wrap .xlwcty_textBox.xlwcty_textBox_1 .xlwcty_title ']['font-size']   = $this->data->heading_font_size . 'px';
				$style['.xlwcty_wrap .xlwcty_textBox.xlwcty_textBox_1 .xlwcty_title ']['line-height'] = ( $this->data->heading_font_size + 4 ) . 'px';
			}
			if ( $this->data->heading_alignment != '' ) {
				$style['.xlwcty_wrap .xlwcty_textBox.xlwcty_textBox_1 .xlwcty_title']['text-align'] = $this->data->heading_alignment;
			}
			if ( $this->data->border_style != '' ) {
				$style['.xlwcty_wrap .xlwcty_Box.xlwcty_textBox.xlwcty_textBox_1']['border-style'] = $this->data->border_style;
			}
			if ( (int) $this->data->border_width >= 0 ) {
				$style['.xlwcty_wrap .xlwcty_Box.xlwcty_textBox.xlwcty_textBox_1']['border-width'] = (int) $this->data->border_width . 'px';
			}
			if ( $this->data->border_color != '' ) {
				$style['.xlwcty_wrap .xlwcty_Box.xlwcty_textBox.xlwcty_textBox_1']['border-color'] = $this->data->border_color;
			}
			if ( $this->data->component_bg_color != '' ) {
				$style['.xlwcty_wrap .xlwcty_Box.xlwcty_textBox.xlwcty_textBox_1']['background-color'] = $this->data->component_bg_color;
			}

			parent::push_css( $slug . '1', $style );
		}
	}

	public function is_enable( $index = 0 ) {
		if ( XLWCTY_Core()->data->get_meta( $this->get_slug() . '_enable_1', 'raw' ) == '1' ) {
			return true;
		}
	}
}

return XLWCTY_Text::get_instance();
