<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class xlwcty_Input_Html_Rule_Is_Guest {

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Default field values.
	 *
	 * @var array
	 */
	public $defaults;

	public function __construct() {
		// vars
		$this->type = 'Html_Rule_Is_Guest';

		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => '',
		);
	}

	public function render( $field, $value = null ) {

		_e( 'This Page will show on guest orders.', 'woo-thank-you-page-nextmove-lite' );
	}

}
