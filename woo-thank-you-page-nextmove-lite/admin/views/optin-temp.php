<?php
defined( 'ABSPATH' ) || exit;

global $current_user;

$user_instance = __( 'Hey', 'woo-thank-you-page-nextmove-lite' );
if ( is_object( $current_user ) ) {
	/* translators: %s: current user display name. */
	$user_instance = sprintf( __( 'Hey %s,', 'woo-thank-you-page-nextmove-lite' ), $current_user->display_name );
}
$non_sensitive_page_link = esc_url( 'https://xlplugins.com/non-sensitive-usage-tracking/?utm_source=nextmove-lite&utm_campaign=optin&utm_medium=text-click&utm_term=non-sensitive' );
$accept_link             = esc_url( wp_nonce_url( add_query_arg( array(
	'xl-optin-choice' => 'yes',
	'ref'             => filter_input( INPUT_GET, 'page' ),
) ), 'xl_optin_nonce', '_xl_optin_nonce' ) );
$skip_link               = esc_url( wp_nonce_url( add_query_arg( 'xl-optin-choice', 'no' ), 'xl_optin_nonce', '_xl_optin_nonce' ) );
?>
<div id="xlo-wrap" class="wrap">
    <div class="xlo-logos">
        <img class="xlo-plugin-icon" width="80" height="80" src="<?php echo plugin_dir_url( XLWCTY_PLUGIN_FILE ) . 'admin/assets/img/wc.png'; ?>"/>
        <i class="dashicons dashicons-plus xlo-first"></i>
        <img class="xlo-wrap-logo" width="80" height="80" src="<?php echo plugin_dir_url( XLWCTY_PLUGIN_FILE ) . 'admin/assets/img/xlplugins.png'; ?>"/>
    </div>
    <div class="xlo-content">
        <p><?php echo esc_html( $user_instance ); ?><br></p>
        <h2><?php esc_html_e( 'Thank you for choosing NextMove!', 'woo-thank-you-page-nextmove-lite' ); ?></h2>
        <p><?php esc_html_e( 'We are constantly improving the plugin and building in new features.', 'woo-thank-you-page-nextmove-lite' ); ?></p>
        <p><?php esc_html_e( 'Never miss an update! Opt in for security, feature updates and non-sensitive diagnostic tracking. Click Allow & Continue!', 'woo-thank-you-page-nextmove-lite' ); ?></p>
    </div>
    <div class="xlo-actions" data-source="NextMove">
        <a href="<?php echo $skip_link; ?>" class="button button-secondary" data-status="no"><?php esc_html_e( 'Skip', 'woo-thank-you-page-nextmove-lite' ); ?></a>
        <a href="<?php echo $accept_link; ?>" class="button button-primary" data-status="yes"><?php esc_html_e( 'Allow & Continue', 'woo-thank-you-page-nextmove-lite' ); ?></a>
        <div style="display: none" class="xlo_loader">&nbsp;</div>
    </div>
    <div class="xlo-error-boundary"></div>
    <div class="xlo-permissions">
        <a class="xlo-trigger" href="#" tabindex="1"><?php esc_html_e( 'What permissions are being granted?', 'woo-thank-you-page-nextmove-lite' ); ?></a>
        <ul>
            <li id="xlo-permission-profile" class="xlo-permission xlo-profile">
                <i class="dashicons dashicons-admin-users"></i>
                <div>
                    <span><?php esc_html_e( 'Your Profile Overview', 'woo-thank-you-page-nextmove-lite' ); ?></span>
                    <p><?php esc_html_e( 'Name and email address', 'woo-thank-you-page-nextmove-lite' ); ?></p>
                </div>
            </li>
            <li id="xlo-permission-site" class="xlo-permission xlo-site">
                <i class="dashicons dashicons-admin-settings"></i>
                <div>
                    <span><?php esc_html_e( 'Your Site Overview', 'woo-thank-you-page-nextmove-lite' ); ?></span>
                    <p><?php esc_html_e( 'Site URL, WP version, PHP info, plugins & themes', 'woo-thank-you-page-nextmove-lite' ); ?></p>
                </div>
            </li>
        </ul>
    </div>
    <div class="xlo-terms">
        <a href="<?php echo $non_sensitive_page_link; ?>" target="_blank"><?php esc_html_e( 'Non-Sensitive Usage Tracking', 'woo-thank-you-page-nextmove-lite' ); ?></a>
    </div>
</div>
