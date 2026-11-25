<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class XLWCTY_Klarna_compatibility {
	private static $ins = null;

	public function __construct() {
		add_action( 'parse_request', array( $this, 'thankyou_support' ), 1 );
	}

	public static function get_instance() {
		if ( null == self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function thankyou_support() {
		if ( isset( $_GET['klarna_order'] ) && $_GET['klarna_order'] != '' && isset( $_GET['key'] ) && $_GET['key'] != '' ) {
			$order_id  = ( isset( $_GET['order-received'] ) && absint( $_GET['order-received'] ) > 0 ) ? absint( $_GET['order-received'] ) : ( ( isset( $_GET['sid'] ) && absint( $_GET['sid'] ) > 0 ) ? absint( $_GET['sid'] ) : 0 );
			$order_key = sanitize_text_field( wp_unslash( $_GET['key'] ) );

			$order = wc_get_order( $order_id );

			/**
			 * Security: the WooCommerce order_key is the capability token that unlocks
			 * order data on the Thank You / order-received page. Only proceed when the
			 * caller already possesses the correct key for this order — otherwise this
			 * handler would 302 to a URL that leaks the real order_key of an arbitrary,
			 * enumerable order to an unauthenticated visitor. Compare in constant time.
			 */
			if ( $order instanceof WC_Order && hash_equals( (string) XLWCTY_Compatibility::get_order_data( $order, 'order_key' ), (string) $order_key ) ) {
				$get_link = XLWCTY_Core()->data->setup_thankyou_post( XLWCTY_Compatibility::get_order_id( $order ), false )->get_page_link();
				if ( false !== $get_link ) {
					$link_url = XLWCTY_Common::prepare_single_post_url( $get_link, $order );
					wp_redirect( $link_url );
					exit;
				}
			}
		}
	}


}

XLWCTY_Klarna_compatibility::get_instance();
