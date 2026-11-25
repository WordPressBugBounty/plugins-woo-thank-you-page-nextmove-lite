<?php
defined( 'ABSPATH' ) || exit;

// Security: Check if order_data exists before proceeding
if ( empty( $order_data ) || ! is_object( $order_data ) || ! method_exists( $order_data, 'get_id' ) ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Order data not available', 'woo-thank-you-page-nextmove-lite' ) ) );

	return false;
}

remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
$payment_method = XLWCTY_Compatibility::get_order_data( $order_data, 'payment_method' );
remove_action( 'wp_footer', array( XLWCTY_Core()->public, 'execute_wc_thankyou_hooks' ), 1 );
ob_start();
do_action( 'woocommerce_thankyou', XLWCTY_Compatibility::get_order_id( $order_data ) );
do_action( "woocommerce_thankyou_{$payment_method}", XLWCTY_Compatibility::get_order_id( $order_data ) );
$get_content = ob_get_clean();

/**
 * Output the woocommerce_thankyou / woocommerce_thankyou_{gateway} markup exactly as WooCommerce core does
 * (woocommerce/templates/checkout/thankyou.php fires the same actions and prints their output unfiltered).
 *
 * This markup is generated server-side by installed plugins (payment gateways, tracking/GTM, affiliate
 * integrations), not by request input, so it is trusted the same way WooCommerce trusts it. Running it through
 * wp_kses_post() or a script keyword allow-list removed gateway forms, iframes, <noscript> pixels and most
 * tracking scripts without adding a security boundary: each callback is responsible for escaping the data it prints.
 * NextMove's own fallback (XLWCTY_Public::execute_wc_thankyou_hooks()) already prints this output unfiltered.
 *
 * Sites that still want to post-process it can use this filter.
 *
 * @param string   $get_content Captured hook output.
 * @param WC_Order $order_data  Current order.
 */
$get_content = (string) apply_filters( 'xlwcty_additional_information_content', $get_content, $order_data );

/**
 * Checking for visible content (ignore script/style bodies so tracking-only output stays hidden)
 */
$parsed_content = preg_replace( '/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', '', $get_content );
$parsed_content = trim( wp_strip_all_tags( (string) $parsed_content ) );

if ( '' !== $parsed_content ) {
	?>
    <div class="xlwcty_Box xlwcty_textBox xlwcty-wc-thankyou"><?php echo $get_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted plugin hook output, printed as WooCommerce core does. ?>
    </div>
	<?php
} else {
	?>
    <div style="display: none;"><?php echo $get_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted plugin hook output, printed as WooCommerce core does. ?>
    </div>
	<?php
}
