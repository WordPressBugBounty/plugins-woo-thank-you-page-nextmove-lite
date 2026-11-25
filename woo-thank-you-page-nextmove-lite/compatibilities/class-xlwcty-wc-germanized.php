<?php
defined( 'ABSPATH' ) || exit;

#[AllowDynamicProperties]
class XLWCTY_Wc_Germanized {
	private static $ins = null;

	public function __construct() {
		if ( class_exists( 'WC_GZD_Checkout' ) ) {
			/**
			 * The title is stripped from the formatted address (priority 99) on NextMove pages only, so Germanized's own
			 * title formatting does not need to be removed globally (that would also drop it from emails/admin).
			 */
			add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'xlwcty_format_billing_address_campatibility' ), 99, 2 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'xlwcty_format_shipping_address_campatibility' ), 99, 2 );
			add_filter( 'xlwcty_customer_info_contact_name', array( $this, 'xlwcty_add_honorific_to_contact_name' ), 10, 2 );
			add_filter( 'woocommerce_get_order_address', array( $this, 'xlwcty_add_title_field_in_address' ), 10, 3 );
		}
	}

	public function remove_wc_germanized_checkout_hook() {
		$wc_gzd_checkout = $this->get_wc_gzd_checkout();

		remove_filter( 'woocommerce_order_formatted_billing_address', array( $wc_gzd_checkout, 'set_formatted_billing_address' ), 0, 2 );
		remove_filter( 'woocommerce_order_formatted_shipping_address', array( $wc_gzd_checkout, 'set_formatted_shipping_address' ), 0, 2 );
	}

	/**
	 * Use Germanized's singleton; constructing WC_GZD_Checkout again would register all of its hooks a second time.
	 *
	 * @return WC_GZD_Checkout
	 */
	protected function get_wc_gzd_checkout() {
		return method_exists( 'WC_GZD_Checkout', 'instance' ) ? WC_GZD_Checkout::instance() : new WC_GZD_Checkout();
	}

	/**
	 * Whether a NextMove thank you page is being rendered.
	 *
	 * @return bool
	 */
	protected function is_nextmove_page() {
		return ! is_admin() && did_action( 'wp' ) && is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() );
	}

	public static function get_instance() {
		if ( self::$ins == null ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function xlwcty_format_billing_address_campatibility( $billing_address, $order ) {
		if ( $this->is_nextmove_page() && isset( $billing_address['title'] ) ) {
			unset( $billing_address['title'] );
			$billing_address = array_filter( $billing_address );
		}

		return $billing_address;
	}

	public function xlwcty_format_shipping_address_campatibility( $shipping_address, $order ) {
		if ( $this->is_nextmove_page() && isset( $shipping_address['title'] ) ) {
			unset( $shipping_address['title'] );
			$shipping_address = array_filter( $shipping_address );
		}

		return $shipping_address;
	}

	public function xlwcty_add_honorific_to_contact_name( $contact_name, $address_raw ) {
		if ( isset( $address_raw['title'] ) && ! empty( $address_raw['title'] ) ) {
			$contact_name = $address_raw['title'] . ' ' . $contact_name;
		}

		return $contact_name;
	}

	public function xlwcty_add_title_field_in_address( $fields, $type, $obj ) {
		if ( ! $this->is_nextmove_page() || ! defined( 'WC_GERMANIZED_VERSION' ) || ! function_exists( 'wc_gzd_get_crud_data' ) ) {
			return $fields;
		}

		if ( version_compare( WC_GERMANIZED_VERSION, '2.2.8', '>=' ) ) {
			if ( $type == 'billing' && wc_gzd_get_crud_data( $obj, 'billing_title' ) ) {
				$fields['title'] = wc_gzd_get_customer_title( wc_gzd_get_crud_data( $obj, 'billing_title' ) );
			} elseif ( $type == 'shipping' && wc_gzd_get_crud_data( $obj, 'shipping_title' ) ) {
				$fields['title'] = wc_gzd_get_customer_title( wc_gzd_get_crud_data( $obj, 'shipping_title' ) );
			}
		} else {
			$wc_gzd_checkout = $this->get_wc_gzd_checkout();

			if ( $type == 'billing' && wc_gzd_get_crud_data( $obj, 'billing_title' ) ) {
				$fields['title'] = $wc_gzd_checkout->get_customer_title( wc_gzd_get_crud_data( $obj, 'billing_title' ) );
			} elseif ( $type == 'shipping' && wc_gzd_get_crud_data( $obj, 'shipping_title' ) ) {
				$fields['title'] = $wc_gzd_checkout->get_customer_title( wc_gzd_get_crud_data( $obj, 'shipping_title' ) );
			}
		}

		return $fields;
	}

}

/**
 * This file is loaded by XLWCTY_Compatibilities on plugins_loaded priority 999, so a plugins_loaded (priority 10)
 * callback added here would never fire. All plugins are loaded by now; initialise directly.
 */
XLWCTY_Wc_Germanized::get_instance();
