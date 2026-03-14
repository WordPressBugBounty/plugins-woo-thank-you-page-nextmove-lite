<?php
defined( 'ABSPATH' ) || exit;

$default_zoom = 14;
if ( isset( $this->data->zoom_level ) ) {
	$default_zoom = (int) $this->data->zoom_level;
	if ( $default_zoom < 8 || $default_zoom > 20 ) {
		$default_zoom = 14;
	}
}
if ( empty( $this->data->map_add ) ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Data not set', 'woo-thank-you-page-nextmove-lite' ) ) );

	return;
}
XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
$default_settings    = XLWCTY_Core()->data->get_option();
$is_google_key_exist = $default_settings['google_map_api'];

?>
<div class="xlwcty_Box xlwcty_Map">
    <div class="xlwcty_mapDiv xlwcty-map-component" data-address='<?php echo esc_attr( $this->data->map_add ); ?>' data-zoom-level='<?php echo esc_attr( $default_zoom ); ?>'
         data-nm-icon="<?php echo esc_attr( $this->data->icon ); ?>" data-style="<?php echo esc_attr( $this->data->style ? $this->data->style : 'standard' ); ?>"
         data-marker-text="
		<?php
	     $marker_text_parsed = apply_filters( 'xlwcty_the_content', $this->data->marker_text );
	     echo esc_attr( wp_strip_all_tags( $marker_text_parsed ) );
	     ?>
		">
		<?php
		if ( empty( $is_google_key_exist ) ) {
			echo '<div class="xlwcty_map_error_txt">' . esc_html__( 'Google Map API Key is missing.', 'woo-thank-you-page-nextmove-lite' ) . '</div>';
		}
		?>
    </div>
    <div class="xlwcty_content">
		<?php
		$heading_parsed = $this->data->heading ? XLWCTY_Common::maype_parse_merge_tags( $this->data->heading ) : '';
		echo $heading_parsed ? '<div class="xlwcty_title">' . wp_kses_post( $heading_parsed ) . '</div>' : '';
		$desc_class = '';
		if ( ! empty( $this->data->desc_alignment ) ) {
			$desc_class = ' class="xlwcty_' . esc_attr( $this->data->desc_alignment ) . '"';
		}
		$desc_parsed = $this->data->desc ? apply_filters( 'xlwcty_the_content', $this->data->desc ) : '';
		echo $desc_parsed ? '<div' . $desc_class . '>' . wp_kses_post( $desc_parsed ) . '</div>' : '';
		?>
    </div>
</div>
