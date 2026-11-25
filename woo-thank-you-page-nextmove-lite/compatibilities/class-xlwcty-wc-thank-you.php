<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class XLWCTY_WC_Thank_You {

	private static $ins = null;

	public function __construct() {
		/**
		 * This file is loaded by XLWCTY_Compatibilities on plugins_loaded priority 999, so hooking
		 * plugins_loaded at a lower priority would never fire. All plugins are loaded by now; run directly.
		 */
		$this->actions();
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function actions() {
		if ( class_exists( 'WC_Custom_Thankyou' ) ) {
			$wc_thankyou_obj = WC_Custom_Thankyou::instance();
			remove_action( 'template_redirect', array( $wc_thankyou_obj, 'custom_redirect_after_purchase' ) );
		}
	}
}

XLWCTY_WC_Thank_You::get_instance();
