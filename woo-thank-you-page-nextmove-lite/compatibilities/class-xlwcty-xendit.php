<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class XLWCTY_Xendit {

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
		if ( ! class_exists( 'WC_Xendit_PG' ) ) {
			return;
		}
		add_filter( 'woocommerce_get_return_url', array( $this, 'order_received_url' ), 99, 2 );
	}

	public function order_received_url( $return_url, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			return $return_url;
		}

		$default_settings = XLWCTY_Core()->data->get_option();
		if ( isset( $default_settings['xlwcty_preview_mode'] ) && ( 'sandbox' === $default_settings['xlwcty_preview_mode'] ) ) {
			return $return_url;
		}

		$order_id = XLWCTY_Compatibility::get_order_id( $order );
		if ( 0 != $order_id ) {
			$get_link = XLWCTY_Core()->data->setup_thankyou_post( XLWCTY_Compatibility::get_order_id( $order ), false )->get_page_link();
			if ( false !== $get_link ) {
				$get_link = trim( $get_link );
				$get_link = wp_specialchars_decode( $get_link );

				return ( XLWCTY_Common::prepare_single_post_url( $get_link, $order ) );
			}
		}

		return $return_url;
	}
}

XLWCTY_Xendit::get_instance();
