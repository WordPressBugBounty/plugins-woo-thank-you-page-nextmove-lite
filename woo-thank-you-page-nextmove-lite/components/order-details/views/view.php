<?php
defined( 'ABSPATH' ) || exit;

// Security: Check if order_data exists before proceeding
if ( empty( $order_data ) || ! is_object( $order_data ) || ! method_exists( $order_data, 'has_status' ) ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Order data not available', 'woo-thank-you-page-nextmove-lite' ) ) );

	return false;
}

XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );

$heading_desc = '';
ob_start();
$desc_class = '';
if ( ! empty( $this->data->desc_alignment ) ) {
	$desc_class = ' class="xlwcty_' . esc_attr( $this->data->desc_alignment ) . ' xlwcty_margin_bottom"';
}
$desc_parsed = $this->data->desc ? apply_filters( 'xlwcty_the_content', $this->data->desc ) : '';
echo $desc_parsed ? '<div' . $desc_class . '>' . wp_kses_post( $desc_parsed ) . '</div>' : '';
$heading_desc = ob_get_clean();
$after_desc   = '';

ob_start();
$desc_class = '';
if ( ! empty( $this->data->after_desc_alignment ) ) {
	$desc_class = ' class="xlwcty_' . esc_attr( $this->data->after_desc_alignment ) . ' xlwcty_margin_top"';
}
$after_desc_parsed = $this->data->after_desc ? apply_filters( 'xlwcty_the_content', $this->data->after_desc ) : '';
echo $after_desc_parsed ? '<div' . $desc_class . '>' . wp_kses_post( $after_desc_parsed ) . '</div>' : '';
unset( $desc_class );
$after_desc = ob_get_clean();

if ( 'default' === $this->data->layout ) {
	include __DIR__ . '/order-summary-default.php';
} else {
	include __DIR__ . '/order-summary-custom.php';
}
