<?php
defined( 'ABSPATH' ) || exit;

if ( '' !== $this->data->text || '' !== $this->data->heading ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
	?>
    <div class="xlwcty_Box xlwcty_textBox xlwcty_textBoxSimpleText xlwcty_textBoxSimpleText_1">
		<?php
		$heading_parsed = $this->data->heading ? XLWCTY_Common::maype_parse_merge_tags( $this->data->heading ) : '';
		echo $heading_parsed ? '<div class="xlwcty_title">' . wp_kses_post( $heading_parsed ) . '</div>' : '';
		$text_parsed = $this->data->text ? apply_filters( 'xlwcty_the_content', $this->data->text ) : '';
		echo $text_parsed ? '<div class="xlwcty_content">' . wp_kses_post( $text_parsed ) . '</div>' : '';
		?>
    </div>
	<?php
} else {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Data not set', 'woo-thank-you-page-nextmove-lite' ) ) );
}
