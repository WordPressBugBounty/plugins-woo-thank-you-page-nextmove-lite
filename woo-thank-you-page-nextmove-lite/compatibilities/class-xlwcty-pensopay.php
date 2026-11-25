<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pensopay Payments gateway compatibility.
 *
 * Ensures subscription and external payment returns use the correct
 * NextMove thank you page URL in the customer's order language.
 */
#[AllowDynamicProperties]
class XLWCTY_PensoPay {

	private static $ins = null;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'actions' ), 1000 );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function actions() {
		if ( ! class_exists( 'WC_PensoPay' ) && ! function_exists( 'woocommerce_pensopay_create_payment_link' ) ) {
			return;
		}

		add_filter( 'woocommerce_pensopay_transaction_link_params', array( $this, 'filter_transaction_link_params' ), 99, 2 );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_from_order_received' ), 1 );
	}

	/**
	 * Override Pensopay continue URL with the localized NextMove thank you page.
	 *
	 * @param array    $params Transaction link params.
	 * @param WC_Order $order  Order object.
	 *
	 * @return array
	 */
	public function filter_transaction_link_params( $params, $order ) {
		if ( ! $order instanceof WC_Order || ! is_array( $params ) ) {
			return $params;
		}

		$url = XLWCTY_Common::get_thankyou_redirect_url_for_order( $order );
		if ( $url ) {
			$params['continueurl'] = $url;
		}

		return $params;
	}

	/**
	 * Redirect default WooCommerce order-received returns to NextMove pages.
	 */
	public function maybe_redirect_from_order_received() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
			return;
		}

		$order_id = absint( get_query_var( 'order-received' ) );
		if ( ! $order_id ) {
			$order_id = isset( $_GET['order-received'] ) ? absint( wp_unslash( $_GET['order-received'] ) ) : 0;
		}

		$order_key = filter_input( INPUT_GET, 'key' );
		if ( ! $order_id || empty( $order_key ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			return;
		}

		$url = XLWCTY_Common::get_thankyou_redirect_url_for_order( $order );
		if ( ! $url ) {
			return;
		}

		$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$target_uri  = wp_parse_url( $url, PHP_URL_PATH );

		if ( $target_uri && false !== strpos( $current_uri, $target_uri ) ) {
			return;
		}

		wp_safe_redirect( $url );
		exit;
	}
}

XLWCTY_PensoPay::get_instance();
