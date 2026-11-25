<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class xlwcty_Input_Date extends xlwcty_Input_Text {

	public function __construct() {
		$this->type = 'Date';

		parent::__construct();
	}

	public function render( $field, $value = null ) {
		$field = array_merge( $this->defaults, $field );
		if ( ! isset( $field['id'] ) ) {
			$field['id'] = sanitize_title( $field['id'] );
		}

		echo '<input name="' . xlwcty_Input_Builder::esc_name( $field['name'] ) . '" type="text" id="' . esc_attr( $field['id'] ) . '" class="xlwcty-date-picker-field' . esc_attr( $field['class'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $value ) . '" />';
	}

}
