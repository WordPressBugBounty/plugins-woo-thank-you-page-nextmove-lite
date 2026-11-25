<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Stripe Gateway compatibility.
 *
 * Stripe Gateway v10.6+ (Adaptive Pricing) and v11+ (Optimized Checkout) confirm payments
 * through Stripe Checkout Sessions. The order is only moved to processing by the
 * `checkout.session.completed` webhook, so when the customer lands on the thank you page
 * the order is usually still `pending`. Without this, NextMove treats the order as unpaid
 * and redirects the customer to the default WooCommerce order-received page.
 */
#[AllowDynamicProperties]
class XLWCTY_Stripe {

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
		if ( ! class_exists( 'WC_Stripe' ) && ! defined( 'WC_STRIPE_VERSION' ) ) {
			return;
		}

		add_filter( 'xlwcty_allow_pending_order', array( $this, 'maybe_allow_pending_checkout_session_order' ), 10, 2 );
	}

	/**
	 * Allow pending Stripe orders whose Checkout Session Stripe has already verified as complete.
	 *
	 * Stripe sets `_stripe_upe_redirect_processed` only after it retrieves the Checkout Session
	 * from the Stripe API on the customer's return and confirms its status is `complete`.
	 *
	 * @param bool     $allow Current decision.
	 * @param WC_Order $order Order object.
	 *
	 * @return bool
	 */
	public function maybe_allow_pending_checkout_session_order( $allow, $order ) {
		if ( true === $allow || ! $order instanceof WC_Order ) {
			return $allow;
		}

		if ( 0 !== strpos( (string) $order->get_payment_method(), 'stripe' ) ) {
			return $allow;
		}

		if ( '' === (string) $order->get_meta( '_stripe_checkout_session_id' ) ) {
			return $allow;
		}

		return wc_string_to_bool( (string) $order->get_meta( '_stripe_upe_redirect_processed' ) );
	}
}

XLWCTY_Stripe::get_instance();
