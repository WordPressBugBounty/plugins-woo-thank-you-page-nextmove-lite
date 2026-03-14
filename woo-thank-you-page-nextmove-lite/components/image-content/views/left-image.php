<?php
defined( 'ABSPATH' ) || exit;

$source_left     = ! empty( $this->data->img_l_source ) ? $this->data->img_l_source : '';
$left_image_link = ! empty( $this->data->img_l_link ) ? $this->data->img_l_link : 'javascript:void(0)';
$content         = $this->data->editor;
$ratio           = $this->data->img_cont_ratio;

$left_class  = 'xlwcty_50';
$right_class = 'xlwcty_50';
if ( $ratio == '33_66' ) {
	$left_class  = 'xlwcty_33';
	$right_class = 'xlwcty_66 xlwcty_left_space';
}
if ( $ratio == '66_33' ) {
	$left_class  = 'xlwcty_66';
	$right_class = 'xlwcty_33 xlwcty_left_space';
}
if ( $source_left == '' ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'Data not set', 'woo-thank-you-page-nextmove-lite' ) ) );

	return '';
}
XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
?>
<div class="xlwcty_Box xlwcty_imgBox <?php echo 'xlwcty_imgBox_1'; ?>">
	<?php
	$heading_parsed = XLWCTY_Common::maype_parse_merge_tags( $this->data->heading );
	?>
    <div class="xlwcty_title"><?php echo wp_kses_post( $heading_parsed ); ?></div>
	<?php
	$desc_class = '';
	if ( ! empty( $this->data->desc_alignment ) ) {
		$desc_class = ' class="xlwcty_' . esc_attr( $this->data->desc_alignment ) . '"';
	}
	$desc_parsed = $this->data->desc ? apply_filters( 'xlwcty_the_content', $this->data->desc ) : '';
	echo $desc_parsed ? '<div' . $desc_class . '>' . wp_kses_post( $desc_parsed ) . '</div>' : '';
	?>
    <div class="xlwcty_imgBox_w xlwcty_clearfix">
        <div class="xlwcty_content xlwcty_center <?php echo esc_attr( $left_class ); ?>" data-style="left">
			<?php
			$left_img_link_parsed = XLWCTY_Common::maype_parse_merge_tags( $left_image_link );
			printf( "<p><a href='%s' class='xlwcty_content_block_image_link'><img src='%s' class='xlwcty_content_block_image'/></a></p>", esc_url( $left_img_link_parsed ), esc_url( $source_left ) );
			?>
        </div>
        <div class="xlwcty_content <?php echo esc_attr( $right_class ); ?>" data-style="right">
			<?php
			$content_parsed = apply_filters( 'xlwcty_the_content', $content );
			echo wp_kses_post( $content_parsed );
			?>
        </div>
    </div>
	<?php
	if ( $this->data->show_btn == 'yes' && $this->data->btn_text != '' ) {
		$btn_link        = ! empty( $this->data->btn_link ) != '' ? $this->data->btn_link : 'javascript:void(0)';
		$btn_link_parsed = XLWCTY_Common::maype_parse_merge_tags( $btn_link );
		$btn_text_parsed = XLWCTY_Common::maype_parse_merge_tags( $this->data->btn_text );
		?>
        <div class="xlwcty_clear_20"></div>
        <div class="xlwcty_clearfix xlwcty_center">
            <a href="<?php echo esc_url( $btn_link_parsed ); ?>" class="xlwcty_btn">
				<?php echo wp_kses_post( $btn_text_parsed ); ?>
            </a>
        </div>
		<?php
	}
	?>
</div>
