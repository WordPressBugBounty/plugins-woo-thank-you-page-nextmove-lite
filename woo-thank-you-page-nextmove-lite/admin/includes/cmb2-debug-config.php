<?php
defined( 'ABSPATH' ) || exit;

/**
 * The order pre-data runs a wc_get_orders() query. This file is included on every
 * `cmb2_init` (i.e. every admin request), so only build it on the NextMove settings
 * screen where the debug box is actually rendered.
 */
$xlwcty_debug_pre_data = array();
if ( is_admin() && 'wc-settings' === filter_input( INPUT_GET, 'page' ) && XLWCTY_Common::get_wc_settings_tab_slug() === filter_input( INPUT_GET, 'tab' ) && 'settings' === filter_input( INPUT_GET, 'section' ) ) {
	$xlwcty_debug_pre_data = XLWCTY_Admin_CMB2_Support::get_orders_cmb2( null, true );
}

return array(
	array(
		'id'               => "order_select",
		'name'             => __( 'Select Order', 'woo-thank-you-page-nextmove-lite' ),
		'type'             => 'select',
		'desc'             => '',
		'row_classes'      => array( 'xlwcty_cmb2_chosen', "cmb2_debug_order_select", "no-border" ),
		'show_option_none' => __( 'Choose an Order', 'woo-thank-you-page-nextmove-lite' ),
		'options_cb'       => array( 'XLWCTY_Admin_CMB2_Support', 'get_orders_cmb2' ),
		'attributes'       => array(
			'data-pre-data' => wp_json_encode( $xlwcty_debug_pre_data ),
		)
	),
	array(
		'id'      => "page_select_area",
		'type'    => 'xlwcty_html_content_field',
		'content' => '<div class="page_select_area_inner"></div>',
	),
	array(
		'id'      => "page_select_temp",
		'type'    => 'xlwcty_html_content_field',
		'content' => '<script type="text/html" id="tmpl-xlwcty-debug-page-template">
        <ul>
        <li><strong>Page</strong>: {{{data.post_title}}} ({{{data.ID}}})</li>
        <li><strong>Page Template</strong>: {{{data.xlwcty_template}}}</li>
        <li>{{{data.xlwcty_componets_html}}}</li>
        <li><strong>Public link</strong>: <a href="{{{data.public_link}}}" target="_blank">{{{data.public_link}}}</a></li>
    
        </ul>
 
</script>',
	),
	array(
		'id'      => "page_select_temp_error",
		'type'    => 'xlwcty_html_content_field',
		'content' => '<script type="text/html" id="tmpl-xlwcty-debug-page-error-template">
   <p> {{{data.error_text}}}</p>
</script>',
	),
	array(
		'id'      => "page_select_temp_loader",
		'type'    => 'xlwcty_html_content_field',
		'content' => '<script type="text/html" id="tmpl-xlwcty-debug-page-loader-template">
   <p> <img src="' . plugin_dir_url( XLWCTY_PLUGIN_FILE ) . 'admin/assets/img/spinner.gif"/></p>
</script>',
	),
	array(
		'name'       => "_wpnonce",
		'id'         => '_wpnonce',
		'type'       => 'hidden',
		'attributes' => array(
			'value' => wp_create_nonce( 'woocommerce-settings' )
		)
	)
);
