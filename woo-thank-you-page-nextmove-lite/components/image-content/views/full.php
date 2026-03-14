<?php
defined( 'ABSPATH' ) || exit;

$source          = ( ! empty( $this->data->img_source ) ) ? $this->data->img_source : '';
$full_image_link = ( $this->data->img_link ) ? $this->data->img_link : 'javascript:void(0)';
if ( $source != '' ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
	?>
    <div class="xlwcty_Box xlwcty_imgBox <?php echo 'xlwcty_imgBox_1'; ?>">
		<?php
		$heading_parsed = $this->data->heading ? XLWCTY_Common::maype_parse_merge_tags( $this->data->heading ) : '';
		echo $heading_parsed ? '<div class="xlwcty_title">' . wp_kses_post( $heading_parsed ) . '</div>' : '';
		?>
        <div class="xlwcty_content">
			<?php
			$desc_class = '';
			if ( ! empty( $this->data->desc_alignment ) ) {
				$desc_class = ' class="xlwcty_' . esc_attr( $this->data->desc_alignment ) . '"';
			}
			$desc_parsed = $this->data->desc ? apply_filters( 'xlwcty_the_content', $this->data->desc ) : '';
			echo $desc_parsed ? '<div' . $desc_class . '>' . wp_kses_post( $desc_parsed ) . '</div>' : '';
			?>
            <div class="xlwcty_imgBox_w xlwcty_clearfix">
				<?php
				$img_link_parsed = XLWCTY_Common::maype_parse_merge_tags( $full_image_link );
				printf( "<p class='xlwcty_center'><a href='%s' class='xlwcty_content_block_image_link'><img src='%s' class='xlwcty_content_block_image'/></a></p>", esc_url( $img_link_parsed ), esc_url( $source ) );
				?>

            </div>
			<?php
			if ( $this->data->show_btn == 'yes' && $this->data->btn_text != '' ) {
				$btn_link = ! empty( $this->data->btn_link ) != '' ? $this->data->btn_link : 'javascript:void(0)';
				?>
                <div class="xlwcty_clear_20"></div>
                <div class="xlwcty_clearfix xlwcty_center">
					<?php
					$btn_link_parsed = XLWCTY_Common::maype_parse_merge_tags( $btn_link );
					$btn_text_parsed = XLWCTY_Common::maype_parse_merge_tags( $this->data->btn_text );
					?>
                    <a href="<?php echo esc_url( $btn_link_parsed ); ?>" class="xlwcty_btn">
						<?php echo wp_kses_post( $btn_text_parsed ); ?>
                    </a>
                </div>
				<?php
			}
			?>
        </div>
    </div>
	<?php
} else {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Data not set', 'woo-thank-you-page-nextmove-lite' ) ) );
}
