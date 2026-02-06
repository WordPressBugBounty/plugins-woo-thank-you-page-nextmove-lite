<?php
defined( 'ABSPATH' ) || exit;

if ( '' !== $this->data->url || '' !== $this->data->embed ) {
	XLWCTY_Core()->public->add_header_logs( sprintf( '%s - %s', $this->get_component_property( 'title' ), __( 'On', 'woo-thank-you-page-nextmove-lite' ) ) );
	?>
	<div class="xlwcty_Box xlwcty_videoBox <?php echo 'xlwcty_videoBox_1'; ?>">
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
		<div class="xlwcty_embed_video">
			<div class="xlwcty_16by9">
				<?php
				if ( 'video_url' === $this->data->source ) {
					if ( strpos( $this->data->url, 'youtu' ) !== false ) {
						$youtube_id = XLWCTY_Common::get_video_id( $this->data->url );
						if ( ! empty( $youtube_id ) ) {
							$youtube_url   = "https://www.youtube.com/embed/{$youtube_id}";
							$autoplay_attr = '';
							if ( strpos( $this->data->url, 'autoplay' ) !== false ) {
								$youtube_url   = add_query_arg(
									array(
										'autoplay' => '1',
									),
									$youtube_url
								);
								$autoplay_attr = ' allow="autoplay"';
							}
							if ( strpos( $this->data->url, 'showinfo' ) !== false ) {
								$youtube_url = add_query_arg(
									array(
										'showinfo' => '0',
									),
									$youtube_url
								);
							}
							if ( strpos( $this->data->url, 'rel' ) !== false ) {
								$youtube_url = add_query_arg(
									array(
										'rel' => '0',
									),
									$youtube_url
								);
							}
							if ( strpos( $this->data->url, 'controls' ) !== false ) {
								$youtube_url = add_query_arg(
									array(
										'controls' => '0',
									),
									$youtube_url
								);
							}
							printf( '<iframe src="%s" width="1020" height="574" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen %s></iframe>', $youtube_url, $autoplay_attr );
						}
					} elseif ( strpos( $this->data->url, 'vimeo' ) !== false ) {
						$vimeo_id = XLWCTY_Common::get_video_id( $this->data->url, 'vimeo' );
						if ( ! empty( $vimeo_id ) ) {
							$vimeo_url = "https://player.vimeo.com/video/{$vimeo_id}";
							if ( strpos( $this->data->url, 'autoplay' ) !== false ) {
								$vimeo_url = add_query_arg(
									array(
										'autoplay' => '1',
									),
									$vimeo_url
								);
							}
							if ( strpos( $this->data->url, 'loop' ) !== false ) {
								$vimeo_url = add_query_arg(
									array(
										'loop' => '1',
									),
									$vimeo_url
								);
							}
							printf( '<iframe src="%s" width="1020" height="574" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>', $vimeo_url );
						}
					} else {
						echo apply_filters( 'the_content', sprintf( '[embed]%s[/embed]', $this->data->url ) );
					}
				}
				if ( 'embed' === $this->data->source ) {
					// SECURITY FIX: Sanitize embed code to prevent XSS while allowing iframes
					echo wp_kses(
						$this->data->embed,
						array(
							'iframe' => array(
								'src'                   => array(),
								'width'                 => array(),
								'height'                => array(),
								'frameborder'           => array(),
								'allowfullscreen'       => array(),
								'webkitallowfullscreen' => array(),
								'mozallowfullscreen'    => array(),
								'allow'                 => array(),
							),
						)
					);
				}
				?>
			</div>
			<?php
			if ( 'yes' === $this->data->show_btn && '' !== $this->data->btn_text ) {
				$btn_link = ! empty( $this->data->btn_link ) !== '' ? $this->data->btn_link : 'javascript:void(0)';
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
