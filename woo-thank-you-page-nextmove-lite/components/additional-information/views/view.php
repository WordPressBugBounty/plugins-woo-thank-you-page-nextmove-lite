<?php
defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
$payment_method = XLWCTY_Compatibility::get_order_data( $order_data, 'payment_method' );
remove_action( 'wp_footer', array( XLWCTY_Core()->public, 'execute_wc_thankyou_hooks' ), 1 );
ob_start();
do_action( 'woocommerce_thankyou', XLWCTY_Compatibility::get_order_id( $order_data ) );
do_action( "woocommerce_thankyou_{$payment_method}", XLWCTY_Compatibility::get_order_id( $order_data ) );
$get_content = ob_get_clean();

/**
 * SECURITY: Extract and validate scripts before escaping HTML content
 * This prevents XSS while allowing legitimate tracking scripts to execute
 */
$scripts = array();
$script_pattern = '/(<script\b[^>]*>.*?<\/script>)/is';

if ( preg_match_all( $script_pattern, $get_content, $script_matches ) ) {
	foreach ( $script_matches[0] as $script ) {
		$safe_script_keywords = array(
			'bwf_thankyou_ajax',
			'xlwcty_fab_ecom',
			'fbq(',
			'gtag(',
			'ga(',
			'facebook_tracking_event',
			'XMLHttpRequest',
			'DOMContentLoaded',
		);
		
		$is_safe = false;
		foreach ( $safe_script_keywords as $keyword ) {
			if ( strpos( $script, $keyword ) !== false ) {
				$is_safe = true;
				break;
			}
		}
		$dangerous_patterns = array(
			'eval(',
			'Function(',
			'setTimeout(',
			'setInterval(',
			'document.write',
			'document.cookie',
			'innerHTML',
			'outerHTML',
		);
		
		$is_dangerous = false;
		foreach ( $dangerous_patterns as $pattern ) {
			if ( strpos( $script, $pattern ) !== false ) {
				$is_dangerous = true;
				break;
			}
		}
		
		// Only allow safe scripts without dangerous patterns
		if ( $is_safe && ! $is_dangerous ) {
			$scripts[] = $script;
		}
	}
	
	// Remove scripts from content before escaping
	$get_content = preg_replace( $script_pattern, '', $get_content );
}

// Escape HTML content (scripts already removed)
$filtered_content = wp_kses_post( $get_content );

/**
 * Checking for the content
 */
$parsed_content = strip_tags( $filtered_content );
$parsed_content = trim( $parsed_content );

if ( '' !== $parsed_content ) {
	?>
	<div class="xlwcty_Box xlwcty_textBox xlwcty-wc-thankyou"><?php echo $filtered_content; ?>
	</div>
	<?php
} else {
	?>
	<div style="display: none;"><?php echo $filtered_content; ?>
	</div>
	<?php
}

/**
 * Output validated scripts in wp_footer where they can execute properly
 * This maintains security while allowing legitimate tracking scripts
 */
if ( ! empty( $scripts ) ) {
	add_action( 'wp_footer', function() use ( $scripts ) {
		foreach ( $scripts as $script ) {
			// Scripts have been validated - output directly
			echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}, 6 );
}