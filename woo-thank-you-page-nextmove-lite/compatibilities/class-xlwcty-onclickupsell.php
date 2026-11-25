<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class XLWCTY_One_Click_Upsell {
	private static $ins = null;

	public function __construct() {
		/**
		 * This file is loaded by XLWCTY_Compatibilities on plugins_loaded priority 999, so hooking
		 * plugins_loaded at a lower priority would never fire. All plugins are loaded by now; run directly.
		 */
		$this->actions();
	}

	public static function get_instance() {
		if ( null == self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function actions() {
		if ( defined( 'GB_OCU_ID' ) ) {
			add_filter( 'xlwcty_redirect_to_thankyou', array( $this, 'order_received_url' ), 99, 3 );
		}
	}


	public function order_received_url( $status, $link, $order ) {
		$parse_link = wp_parse_url( $link );
		// checking if upsell plugin has modified the thank you url of order
		if ( isset( $parse_link['query'] ) && strpos( $parse_link['query'], '1cu' ) !== false ) {
			return $link;
		}

		return $status;

	}

}

XLWCTY_One_Click_Upsell::get_instance();
