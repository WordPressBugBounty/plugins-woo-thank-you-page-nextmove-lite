<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCTY_Data
 * @package NextMove
 * @author XlPlugins
 */
#[AllowDynamicProperties]
class XLWCTY_Data {

	private static $ins = null;
	public $page_id = false;
	public $page_link = false;
	private $order_id = false;
	private $order = false;
	private $page_component_raw_meta = false;
	private $page_component_meta = array();
	private $page_layout = false;
	private $page_layout_info = false;
	private $options = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	/**
	 * @param $order_id
	 * @param bool $return_key
	 * @param bool $skip_rules
	 *
	 * @return $this|void
	 */
	public function setup_thankyou_post( $order_id, $skip_rules = false ) {

		if ( ! is_numeric( $order_id ) ) {
			return;
		}
		$this->load_order( $order_id );

		// Trigger WPML language switching if needed.
		do_action( 'xlwcty_before_setup_thankyou_post', $order_id );

		$args = array(
			'post_type'        => XLWCTY_Common::get_thank_you_page_post_type_slug(),
			'post_status'      => 'publish',
			'nopaging'         => true,
			'meta_key'         => '_xlwcty_menu_order',
			'orderby'          => 'meta_value_num',
			'order'            => 'ASC',
			'fields'           => 'ids',
			'suppress_filters' => false,
		);

		$xl_transient_obj = XL_Transient::get_instance();
		$xl_cache_obj     = XL_Cache::get_instance();

		$key = 'xlwcty_instances';

		// handling for WPML.
		$current_lang = '';
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE !== '' ) {
			$current_lang = ICL_LANGUAGE_CODE;
			$key          .= '_' . $current_lang;
		} elseif ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'XLWCTY_WPML' ) ) {
			$wpml_compat  = XLWCTY_WPML::get_instance();
			$current_lang = $wpml_compat->get_current_language();
			if ( $current_lang ) {
				$key .= '_' . $current_lang;
			}
		}

		$contents = array();
		do_action( 'xlwcty_before_query', $order_id );

		/**
		 * Setting xl cache and transient for NextMove pages query.
		 */
		$cache_data = $xl_cache_obj->get_cache( $key, 'nextmove' );
		if ( false !== $cache_data ) {
			$contents = $cache_data;
		} else {
			$transient_data = $xl_transient_obj->get_transient( $key, 'nextmove' );

			if ( false !== $transient_data ) {
				$contents = $transient_data;
			} else {
				$query_result = new WP_Query( $args );
				if ( $query_result instanceof WP_Query && $query_result->have_posts() ) {
					$contents = $query_result->posts;
					$xl_transient_obj->set_transient( $key, $contents, 21600, 'nextmove' );
				}
			}
			$xl_cache_obj->set_cache( $key, $contents, 'nextmove' );
		}

		do_action( 'xlwcty_after_query', $order_id );

		$contents = apply_filters( 'xlwcty_before_rules_validation', $contents, $order_id, $this, $skip_rules );

		if ( is_array( $contents ) && count( $contents ) > 0 ) {
			// If WPML is active and we have an order, prioritize pages in order's language
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'XLWCTY_WPML' ) && $this->order instanceof WC_Order ) {
				$wpml_compat = XLWCTY_WPML::get_instance();
				$order_lang  = $wpml_compat->get_order_language( $this->order );

				// Reorder pages: put pages in order's language first
				$pages_in_order_lang = array();
				$other_pages         = array();

				foreach ( $contents as $content_single ) {
					$content_id = ( $content_single instanceof WP_Post && is_object( $content_single ) ) ? $content_single->ID : $content_single;
					$page_lang  = $wpml_compat->get_post_language( $content_id );

					if ( $page_lang === $order_lang ) {
						$pages_in_order_lang[] = $content_single;
					} else {
						$other_pages[] = $content_single;
					}
				}

				// Reorder: pages in order's language first
				if ( ! empty( $pages_in_order_lang ) ) {
					$contents = array_merge( $pages_in_order_lang, $other_pages );
				}
			}

			foreach ( $contents as $content_single ) {

				/**
				 * post instance extra checking added as some plugins may modify wp_query args on pre_get_posts filter hook.
				 */
				$content_id = ( $content_single instanceof WP_Post && is_object( $content_single ) ) ? $content_single->ID : $content_single;

				if ( $skip_rules || XLWCTY_Common::match_groups( $content_id, $order_id ) ) {
					$custom_pages = get_option( 'xlwcty_custom_thank_you_pages', array() );
					if ( isset( $custom_pages[ $content_id ] ) && ! empty( $custom_pages[ $content_id ] ) ) {
						$content_id = $custom_pages[ $content_id ];
					}

					// Get translated page ID if WPML is active.
					if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'XLWCTY_WPML' ) && $this->order instanceof WC_Order ) {
						$wpml_compat         = XLWCTY_WPML::get_instance();
						$order_lang          = $wpml_compat->get_order_language( $this->order );
						$original_content_id = $content_id;

						// Check if current page is already in order's language
						$page_lang = $wpml_compat->get_post_language( $content_id );
						if ( $page_lang !== $order_lang ) {
							// Get translation for order's language
							$content_id = $wpml_compat->get_translated_page_id( $content_id, $order_lang );
						}

						// Verify the page exists and is accessible
						$page_post = get_post( $content_id );
						if ( ! $page_post || $page_post->post_status !== 'publish' ) {
							// Try to get default language version
							$default_lang = $wpml_compat->get_default_language();
							if ( $default_lang ) {
								$default_id   = apply_filters( 'wpml_object_id', $original_content_id, XLWCTY_Common::get_thank_you_page_post_type_slug(), true, $default_lang );
								$default_post = get_post( $default_id );
								if ( $default_post && $default_post->post_status === 'publish' ) {
									$content_id = $default_id;
								}
							}
						}
					}

					$this->page_id = $content_id;

					// Get translated page ID first, then generate permalink
					$final_page_id = $content_id;
					if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'XLWCTY_WPML' ) && $this->order instanceof WC_Order ) {
						$wpml_compat  = XLWCTY_WPML::get_instance();
						$order_lang   = $wpml_compat->get_order_language( $this->order );
						$current_lang = $wpml_compat->get_current_language();

						// Get translated page ID for order's language (already done above, but ensure we use it)
						// $content_id is already translated above, but let's verify
						$final_page_id = $wpml_compat->get_translated_page_id( $content_id, $order_lang );

						// Switch to order's language to get correct permalink
						global $sitepress;
						if ( $sitepress instanceof SitePress ) {
							$sitepress->switch_lang( $order_lang, true );
							$permalink = get_permalink( $final_page_id );
							$sitepress->switch_lang( $current_lang, true );
						} else {
							$permalink = get_permalink( $final_page_id );
						}
					} else {
						$permalink = get_permalink( $content_id );
					}

					$this->page_id   = $final_page_id; // Update to translated version
					$this->page_link = $permalink;

					break;
				}
			}
		}

		return $this;
	}

	public function load_order( $order_id = 0 ) {
		if ( $order_id instanceof WP ) {
			$order_id = 0;
		}

		if ( 0 === $order_id ) {
			$order_id = ( isset( $_GET['order_id'] ) && ( $_GET['order_id'] !== '' ) ) ? wc_clean( $_GET['order_id'] ) : 0;
		}
		if ( 0 !== $order_id ) {
			$this->order_id = $order_id;
			$this->order    = wc_get_order( $order_id );
		}
	}

	public function load_order_wp( $order_id = 0 ) {
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( $order_id instanceof WP ) {
			$order_id = 0;
		}

		if ( 0 === $order_id ) {
			$order_id = ( isset( $_GET['order_id'] ) && ( $_GET['order_id'] !== '' ) ) ? wc_clean( $_GET['order_id'] ) : 0;
		}

		$this->order_id = $order_id;
		$this->order    = wc_get_order( $order_id );
	}

	public function get_page_link() {

		return $this->page_link;
	}

	public function load_thankyou_metadata() {

		global $wpdb;
		$xl_cache_obj     = XL_Cache::get_instance();
		$xl_transient_obj = XL_Transient::get_instance();

		if ( false === $this->page_id ) {
			return;
		}
		$meta_query = apply_filters( 'xlwcty_product_meta_query', $wpdb->prepare( "SELECT meta_key,meta_value  FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s", $this->page_id, '%_xlwcty_%' ) );
		$cache_key  = 'xlwcty_thankyou_meta_' . $this->page_id;

		/**
		 * Setting xl cache and transient for NextMove page meta.
		 */
		$cache_data = $xl_cache_obj->get_cache( $cache_key, 'nextmove' );
		if ( false !== $cache_data ) {
			$parseObj = $cache_data;
		} else {
			$transient_data = $xl_transient_obj->get_transient( $cache_key, 'nextmove' );

			if ( false !== $transient_data ) {
				$parseObj = $transient_data;
			} else {
				$get_product_xlwcty_meta = $wpdb->get_results( $meta_query, ARRAY_A );
				$product_meta            = XLWCTY_Common::get_parsed_query_results_meta( $get_product_xlwcty_meta );
				$parseObj                = $product_meta;
				$xl_transient_obj->set_transient( $cache_key, $parseObj, 21600, 'nextmove' );
			}
			$xl_cache_obj->set_cache( $cache_key, $parseObj, 'nextmove' );
		}

		$this->page_component_raw_meta = $parseObj;

		if ( isset( $this->page_component_raw_meta['_xlwcty_builder_template'] ) ) {
			$this->page_layout = $this->page_component_raw_meta['_xlwcty_builder_template'];
		}

		if ( isset( $this->page_component_raw_meta['_xlwcty_builder_layout'] ) ) {
			$layout_info            = $this->page_component_raw_meta['_xlwcty_builder_layout'];
			$this->page_layout_info = json_decode( $layout_info, true );
		}

		$components = xlwcty_components::retrieve_components();

		foreach ( $components as $slug => $component ) {
			$component_properties = $component->get_component();
			if ( $component->has_multiple_fields() ) {
				for ( $i = 1; $i <= $component_properties['fields']['count']; $i ++ ) {
					$this->parse_key_value( $parseObj, $component, $i );
					$this->page_component_meta[ $component->get_slug() ][ $i ] = wp_parse_args( $this->page_component_meta[ $component->get_slug() ][ $i ], $component->get_defaults() );
				}
			} else {
				$this->parse_key_value( $parseObj, $component );
				$this->page_component_meta[ $component->get_slug() ] = wp_parse_args( $this->page_component_meta[ $component->get_slug() ], $component->get_defaults() );
			}
		}

		do_action( 'xlwcty_page_meta_setup_completed', $this );
	}

	/**
	 * Parse and prepare data for single trigger
	 *
	 * @param $data Array Options data
	 * @param $trigger String Trigger slug
	 *
	 */
	public function parse_key_value( $data, $trigger, $index = false ) {
		if ( false === $index ) {
			$this->page_component_meta[ $trigger->get_slug() ] = array();

			foreach ( $trigger->fields as $key => $meta_key ) {

				if ( isset( $data[ $meta_key ] ) ) {

					$this->page_component_meta[ $trigger->get_slug() ][ $key ] = $data[ $meta_key ];
				}
			}
		} else {
			$this->page_component_meta[ $trigger->get_slug() ][ $index ] = array();
			foreach ( $trigger->fields as $key => $meta_key ) {
				if ( isset( $data[ $meta_key . '_' . $index ] ) ) {
					if ( false === $index ) {
						$this->page_component_meta[ $trigger->get_slug() ][ $key ] = $data[ $meta_key . '_' . $index ];
					} else {
						$this->page_component_meta[ $trigger->get_slug() ][ $index ][ $key ] = $data[ $meta_key . '_' . $index ];
					}
				}
			}
		}
	}

	public function get_meta( $key = '', $mode = 'parsed' ) {
		$prop = ( 'raw' === $mode ) ? $this->page_component_raw_meta : $this->page_component_meta;
		if ( $prop && '' === $key ) {
			return $prop;
		}
		if ( $prop && ! empty( $key ) ) {
			return ( isset( $prop[ $key ] ) ? $prop[ $key ] : '' );
		}

		return '';
	}

	public function get_page() {
		return $this->page_id;
	}

	public function get_order( $id = 0 ) {
		if ( 0 !== $id ) {
			$this->load_order( $id );
		}

		return $this->order;
	}

	public function reset_order( $order = 0 ) {
		if ( 0 === $order ) {
			$this->order = false;
		}

		$this->order = $order;
	}

	public function set_page( $id = null ) {
		global $post;

		if ( $post instanceof WP_Post && XLWCTY_Common::get_thank_you_page_post_type_slug() === $post->post_type ) {
			$this->page_id = $post->ID;
		}
		// If post is not set, page_id from setup_thankyou_post is preserved
	}

	public function get_layout() {
		return $this->page_layout;
	}

	public function set_layout( $layout ) {
		$this->page_layout = $layout;
	}

	public function get_layout_info() {
		return $this->page_layout_info;
	}

	public function set_layout_info( $data ) {
		$this->page_layout_info = $data;
	}

	public function setup_options() {
		if ( ! $this->options ) {
			$options = get_option( 'xlwcty_global_settings' );

			$this->options = wp_parse_args( $options, XLWCTY_Common::get_options_defaults() );

			/**
			 * Compatibility with WPML
			 */
			if ( function_exists( 'icl_t' ) ) {
				$translated_google_map_error_text      = icl_t( 'admin_texts_xlwcty_global_settings', '[xlwcty_global_settings]google_map_error_txt', $this->options['google_map_error_txt'] );
				$this->options['google_map_error_txt'] = $translated_google_map_error_text;
			}
		}
	}

	public function get_option( $key = '' ) {
		if ( '' !== $key ) {
			return ( isset( $this->options[ $key ] ) ? $this->options[ $key ] : '' );
		}

		return $this->options;
	}

}

if ( class_exists( 'XLWCTY_Data' ) ) {
	XLWCTY_Core::register( 'data', 'XLWCTY_Data' );
}

