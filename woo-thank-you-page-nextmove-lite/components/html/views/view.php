<?php
defined( 'ABSPATH' ) || exit;

if ( '' !== $this->data->html_content || '' !== $this->data->heading ) {
    XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
    ?>
    <div class="xlwcty_Box xlwcty_textBox <?php echo 'xlwcty_textBox_1'; ?>">
        <?php
        $heading_parsed = $this->data->heading ? XLWCTY_Common::maype_parse_merge_tags( $this->data->heading ) : '';
        echo $heading_parsed ? '<div class="xlwcty_title">' . wp_kses_post( $heading_parsed ) . '</div>' : '';
        $html_content_parsed = $this->data->html_content ? apply_filters( 'xlwcty_the_content', $this->data->html_content ) : '';
        // Unfiltered HTML output is intentional for this component.
        // This enables embedding third-party forms (Gravity Forms, WPForms), scripts, and iframes.
        // Security: Content is only editable by users with manage_woocommerce capability via the admin page builder.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $html_content_parsed ? '<div class="xlwcty_content">' . $html_content_parsed . '</div>' : '';
        ?>
    </div>
    <?php
} else {
    XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Data not set', 'woo-thank-you-page-nextmove-lite' ) ) );
}
