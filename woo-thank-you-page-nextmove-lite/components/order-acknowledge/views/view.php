<?php
defined( 'ABSPATH' ) || exit;

XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
?>
	<div class="xlwcty_order_info">
		<?php
		echo $this->icon_html ? wp_kses_post( $this->icon_html ) : '';
		$heading1_parsed = $this->data->heading ? XLWCTY_Common::maype_parse_merge_tags( $this->data->heading ) : '';
		echo $heading1_parsed ? '<div class="xlwcty_order_no">' . wp_kses_post( $heading1_parsed ) . '</div>' : '';
		$heading2_parsed = $this->data->heading2 ? XLWCTY_Common::maype_parse_merge_tags( $this->data->heading2 ) : '';
		echo $heading2_parsed ? '<div class="xlwcty_userN">' . wp_kses_post( $heading2_parsed ) . '</div>' : '';
		?>
	</div>
<?php
