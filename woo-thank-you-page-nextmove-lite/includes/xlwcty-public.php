<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class xlwcty
 * @package NextMove
 * @author XlPlugins
 */
#[AllowDynamicProperties]
class xlwcty {

	public static $extend = array();
	private static $ins = null;
	private static $_registered_entity = array(
		'active'   => array(),
		'inactive' => array(),
	);
	public $xlwcty_data = array();
	public $wp_loaded = false;
	public $xl_gtag_rendered = false;
	public $loop_thank_you_pages = array();
	public $all_thank_you_pages = array();
	public $is_mini_cart = false;
	public $deals = array();
	public $goals = array();
	public $single_thank_you_page = array();
	public $current_cart_item = null;
	public $single_product_css = array();
	public $product_obj = array();
	public $thank_you_page_goal = array();
	public $is_preview = false;
	public $header_info = array();
	public $xlwcty_is_thankyou = false;
	public $social_setting = array(
		'fb' => array(
			'appId'   => '',
			'version' => 'v2.9',
			'status'  => true,
			'cookie'  => true,
			'xfbml'   => true,
			'oauth'   => true,
		),
	);

	/**
	 * Get sanitized request URI
	 *
	 * @since 1.0.0
	 * @return string Sanitized REQUEST_URI or empty string
	 */
	private function get_sanitized_request_uri() {
		return isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
	}

	/**
	 * Get active WPML language codes
	 *
	 * @since 1.0.0
	 * @return array Array of active language codes, empty if WPML not active
	 */
	private function get_wpml_language_codes() {
		static $language_codes = null;

		if ( $language_codes !== null ) {
			return $language_codes;
		}

		$language_codes = array();

		if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) ) {
			global $sitepress;
			if ( $sitepress instanceof SitePress ) {
				$active_languages = $sitepress->get_active_languages();
				if ( ! empty( $active_languages ) ) {
					$language_codes = array_keys( $active_languages );
				}
			}
		}

		return $language_codes;
	}

	/**
	 * Get thank you page URL slugs (localized)
	 *
	 * @since 1.0.0
	 * @return array Array of possible thank you page slugs
	 */
	private function get_thankyou_page_slugs() {
		return apply_filters( 'xlwcty_thankyou_page_slugs', array(
			'order-received',
			'pedido-recibido',
			'bestellung-erhalten',
			'commande-recue',
			'ordine-ricevuto',
			'pedido-recebido',
			'bestelling-ontvangen',
		) );
	}

	public function __construct() {
		/**
		 * Initiating hooks
		 */
		add_action( 'xlwcty_loaded', array( $this, 'init' ) );
	}

	/**
	 * Getting class instance
	 * @return null|xlwcty
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	/**
	 * Initialize hooks and setup core class to run front end functionality
	 */
	public function init() {
		/**
		 * Hook to modify order received url that matches criteria
		 */
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'redirect_to_thankyou' ), 99, 2 );
		/**
		 * Hooks for data setup while loading thank you page
		 */
		add_action( 'wp', array( XLWCTY_Core()->data, 'setup_options' ), 1 );
		// Run validate_preview early, before Elementor processes the page (priority 0)
		// Elementor hooks into template_redirect at priority -1 and 0, so we need to run before that
		add_action( 'wp', array( $this, 'validate_preview' ), 0 );
		add_action( 'wp', array( $this, 'maybe_preview_load' ), 1 );
		add_action( 'wp', array( $this, 'validate_request' ), 9 );
		add_action( 'wp', array( XLWCTY_Core()->data, 'load_order_wp' ), 10 );
		add_action( 'wp', array( $this, 'validate_order' ), 11 );
		add_action( 'wp', array( XLWCTY_Core()->data, 'set_page' ), 12 );
		add_action( 'wp', array( XLWCTY_Core()->data, 'load_thankyou_metadata' ), 13 );
		add_action( 'wp', array( $this, 'is_xlwcty_page' ), 14 );
		// Ensure order is loaded for NextMove pages even if not order_received_page
		add_action( 'wp', array( $this, 'maybe_load_order_for_nextmove_page' ), 15 );
		add_action( 'parse_request', array( $this, 'maybe_set_query_var' ), 15 );
		/**
		 * Adding wc native thankyou hooks
		 */
		add_action( 'wp_footer', array( $this, 'execute_wc_thankyou_hooks' ), 1 );
		/**
		 * Enqueue necessary scripts
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'component_script' ), 9999 );

		add_action( 'wp_head', array( $this, 'enqueue_all_css' ) );
		add_action( 'wp_head', array( $this, 'xl_render_ga' ) );
		add_action( 'wp_footer', array( $this, 'print_html_header_info' ), 50 );
		add_action( 'wp_footer', array( $this, 'maybe_add_info_footer' ) );
		add_action( 'xlwcty_before_page_render', array( $this, 'register_hooks' ) );
		add_action( 'xlwcty_after_page_render', array( $this, 'de_register_hooks' ) );
		add_filter( 'xlwcty_the_content', array( 'XLWCTY_Common', 'maype_parse_merge_tags' ) );
		add_filter( 'xlwcty_the_content', 'wptexturize' );
		add_filter( 'xlwcty_the_content', 'convert_smilies', 20 );
		add_filter( 'xlwcty_the_content', 'wpautop' );
		add_filter( 'xlwcty_the_content', 'shortcode_unautop' );
		add_filter( 'xlwcty_the_content', 'prepend_attachment' );

		add_filter( 'xlwcty_parse_shortcode', 'do_shortcode', 11 );
		
		// Hook into the_content at priority 11 (after Elementor's priority 9) to inject NextMove content
		// This ensures NextMove content shows even when Elementor replaces the_content
		// Also hook into Elementor's specific filter if available
		add_filter( 'the_content', array( $this, 'inject_nextmove_content_into_elementor' ), 11 );
		add_filter( 'elementor/frontend/the_content', array( $this, 'inject_nextmove_content_into_elementor' ), 11 );

		add_action( 'woocommerce_thankyou', array( $this, 'facebook_pixel_tracking_script' ) );

		add_filter( 'woocommerce_is_checkout', array( $this, 'declare_wc_checkout_page' ) );
		add_action( 'wp', array( $this, 'maybe_pass_no_cache_header' ), 15 );
		add_action( 'wp_footer', array( $this, 'maybe_push_script_for_map_check' ) );
		add_filter( 'woocommerce_is_order_received_page', array( $this, 'declare_wc_order_received_page' ) );

		//Detection of klarna gateways and redirect to out thankyou pages
		add_action( 'parse_request', array( $this, 'parse_request_for_thankyou' ), 1 );
		add_action( 'parse_query', array( $this, 'parse_query_for_thankyou' ), 11 );
		add_filter( 'body_class', array( $this, 'add_body_class' ), 100, 2 );

		// setting nextmove page meta in case of any theme to make page full width
		add_action( 'template_redirect', array( $this, 'maybe_set_meta_to_hide_sidebar' ), 20 );
		
		// Fix 404 errors when Elementor modifies URLs - run early to prevent 404
		add_action( 'pre_get_posts', array( $this, 'fix_query_for_elementor_urls' ), 1 );
		add_action( 'template_redirect', array( $this, 'fix_404_for_elementor_urls' ), 1 );

		add_action( 'wp_head', array( $this, 'xlwcty_page_noindex' ) );

		// remove other languages options
		add_filter( 'icl_post_alternative_languages', array( $this, 'post_alternative_languages' ) );
	}

	public function post_alternative_languages( $output ) {
		if ( $this->xlwcty_is_thankyou ) {
			$output = null;
		}

		return $output;
	}

	public function enqueue_all_css() {
		$css              = XLWCTY_Component::get_css();
		$default_settings = XLWCTY_Core()->data->get_option();
		$output           = '';
		if ( is_array( $css ) && count( $css ) > 0 ) {
			ob_start();
			echo "<style>\n";
			if ( isset( $default_settings['wrap_left_right_padding'] ) && (int) $default_settings['wrap_left_right_padding'] >= 0 ) {
				echo '.xlwcty_wrap{padding:0 ' . (int) $default_settings['wrap_left_right_padding'] . 'px;}';
			}

			foreach ( $css as $comp => $comp_css ) {
				echo "/*" . esc_html( wp_strip_all_tags( (string) $comp ) ) . "*/\n";
				if ( is_array( $comp_css ) && count( $comp_css ) > 0 ) {
					foreach ( $comp_css as $elem => $single_css ) {
						// COMPREHENSIVE CSS SELECTOR SANITIZATION
						$sanitized_elem = preg_replace( '/[^a-zA-Z0-9\-\_\s\.\#\,\:\>\+\[\]\(\)\*\~\|\=\"\'\@]/', '', (string) $elem );
						echo wp_strip_all_tags( $sanitized_elem ) . '{';

						if ( is_array( $single_css ) && count( $single_css ) > 0 ) {
							foreach ( $single_css as $css_prop => $css_val ) {
								// CSS PROPERTY SANITIZATION (allows letters, numbers, hyphens, underscores)
								$sanitized_prop = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $css_prop );

								// USE WORDPRESS'S BUILT-IN CSS SANITIZATION
								$sanitized_val = safecss_filter_attr( (string) $css_val );

								// SAFE OUTPUT WITH PROPER ESCAPING
								echo wp_strip_all_tags( $sanitized_prop ) . ':' . $sanitized_val . ';';
							}
						}
						echo "}\n";
					}
				}
			}

			echo '</style>';
			$output = ob_get_clean();
		}
		echo $output;
	}

	public function xl_render_ga() {
		$ga_ids = XLWCTY_Core()->data->get_option( 'ga_analytics_id' );
		if ( empty( $ga_ids ) ) {
			return;
		}
		$this->xli_render_gad( $ga_ids );
	}

	public function xli_render_gad( $ga_ids ) {
		$get_tracking_codes = explode( ",", $ga_ids );
		?>
        <script>
            (function (window, document, src) {
                var a = document.createElement('script'),
                    m = document.getElementsByTagName('script')[0];
                a.async = 1;
                a.src = src;
                m.parentNode.insertBefore(a, m);
            })(window, document, '//www.googletagmanager.com/gtag/js?id=<?php echo esc_js( trim( $get_tracking_codes[0] ) ); ?>');

            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function gtag() {
                dataLayer.push(arguments);
            };

            gtag('js', new Date());
        </script>
		<?php
	}

	/**
	 * Setup thank-you page post and get new order-received link for the new order
	 *
	 * @param string $url
	 * @param WC_Order $order
	 *
	 * @return mixed|void Modified URL on success , default otherwise
	 */
	public function redirect_to_thankyou( $url, $order ) {
		$default_settings = XLWCTY_Core()->data->get_option();
		if ( isset( $default_settings['xlwcty_preview_mode'] ) && ( 'sandbox' == $default_settings['xlwcty_preview_mode'] ) ) {
			return $url;
		}
		$external_thankyou_url = apply_filters( 'xlwcty_redirect_to_thankyou', false, $url, $order );
		if ( false !== $external_thankyou_url ) {
			$external_thankyou_url = trim( $external_thankyou_url );
			$external_thankyou_url = wp_specialchars_decode( $external_thankyou_url );

			return $external_thankyou_url;
		} else {
			$order_id = XLWCTY_Compatibility::get_order_id( $order );
			if ( 0 != $order_id ) {
				$get_link = XLWCTY_Core()->data->setup_thankyou_post( XLWCTY_Compatibility::get_order_id( $order ), $this->is_preview )->get_page_link();
				if ( false !== $get_link ) {
					$get_link = trim( $get_link );
					$get_link = wp_specialchars_decode( $get_link );
					$final_url = XLWCTY_Common::prepare_single_post_url( $get_link, $order );

					return $final_url;
				}
			}
		}

		return $url;
	}

	public function component_script() {
		wp_enqueue_script( 'jquery' );
		if ( ! $this->is_xlwcty_page() ) {
			$localize = array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'version'    => XLWCTY_VERSION,
				'wc_version' => WC()->version,
			);
			wp_localize_script( 'jquery', 'xlwcty', apply_filters( 'xlwcty_localize_js_data', $localize ) );

			return;
		}

		$plugin_url = untrailingslashit( plugin_dir_url( XLWCTY_PLUGIN_FILE ) );
		$script_min = '.min';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ) {
			$script_min = '';
		}
		$fb_app_id                           = XLWCTY_Core()->data->get_option( 'fb_app_id' );
		$this->social_setting['fb']['appId'] = $fb_app_id;
		$google_map_api                      = XLWCTY_Core()->data->get_option( 'google_map_api' );

		wp_enqueue_script( 'xlwcty-component-script', $plugin_url . '/assets/js/xlwcty-public' . $script_min . '.js', array(), false, true );
		wp_enqueue_style( 'xlwcty-components-css', $plugin_url . '/assets/css/xlwcty-public' . $script_min . '.css', false );
		if ( is_rtl() ) {
			wp_enqueue_style( 'xlwcty-components-css-rtl', $plugin_url . '/assets/css/xlwcty-public-rtl.css', false );
		}
		wp_enqueue_style( 'xlwcty-faicon', $plugin_url . '/assets/fonts/fa.css', false );
		$localize = array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'plugin_url'     => $plugin_url,
			'social'         => $this->social_setting,
			'google_map_key' => $google_map_api,
			'version'        => XLWCTY_VERSION,
			'wc_version'     => WC()->version,
			'infobubble_url' => $plugin_url . '/assets/js/xlwcty-infobubble' . $script_min . '.js',
			'cp'             => 0,
			'or'             => 0,
		);
		$order    = XLWCTY_Core()->data->get_order();
		if ( $order instanceof WC_Order ) {
			$localize['cp'] = get_the_ID();
			$localize['or'] = XLWCTY_Compatibility::get_order_id( $order );
		}
		$localize['settings']               = XLWCTY_Core()->data->get_option();
		$localize['map_errors']             = array(
			'error'          => __( 'Unable to process the request.', 'woo-thank-you-page-nextmove-lite' ),
			'over_limit'     => __( 'Google Map API quota limit reached.', 'woo-thank-you-page-nextmove-lite' ),
			'request_denied' => __( 'This API project is not authorized to use this API. Please ensure that this API is activated in the APIs Console.', 'woo-thank-you-page-nextmove-lite' ),
		);
		$localize['settings']['is_preview'] = ( true === $this->is_preview ) ? 'yes' : 'no';
		wp_localize_script( 'xlwcty-component-script', 'xlwcty', apply_filters( 'xlwcty_localize_js_data', $localize ) );
	}

	/**
	 * Checks whether its our page or not
	 * @return bool
	 */
	public function is_xlwcty_page() {
		$is_page = $this->xlwcty_is_thankyou;
		
		// Also check if we're in preview mode on a NextMove page
		if ( ! $is_page ) {
			// Check if query var indicates NextMove page
			global $wp_query;
			if ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
				$is_page = true;
				$this->xlwcty_is_thankyou = true;
			}
			
			// Check if we're in preview mode
			if ( $this->is_preview ) {
				global $post;
				if ( $post instanceof WP_Post && $post->post_type === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
					$is_page = true;
					$this->xlwcty_is_thankyou = true;
				}
			}
		}
		
		// Only log once per request to reduce log spam
		if ( $is_page && ! isset( $this->logged_xlwcty_page ) ) {
			$this->logged_xlwcty_page = true;
			}
		return $is_page;
	}

	/**
	 * Hooked over shortcode 'xlwcty_load'
	 * Includes layout files
	 *
	 * @param array $attrs
	 *
	 * @return string|void
	 */
	public function maybe_render_elements( $attrs = array() ) {
		if ( ! $this->is_xlwcty_page() ) {return;
		}
		
			// Try to load order from URL if not already loaded (for preview and direct access)
			$order_id_from_url = filter_input( INPUT_GET, 'order_id' );
			if ( $order_id_from_url && ! XLWCTY_Core()->data->get_order() instanceof WC_Order ) {
				XLWCTY_Core()->data->load_order( (int) $order_id_from_url );
				
				// Setup page for this order
				if ( XLWCTY_Core()->data->get_order() instanceof WC_Order ) {
					XLWCTY_Core()->data->setup_thankyou_post( (int) $order_id_from_url, $this->is_preview );
					XLWCTY_Core()->data->load_thankyou_metadata();
				}
			}
			
			$order = XLWCTY_Core()->data->get_order();
			if ( ! $order instanceof WC_Order ) {
				// In preview mode, allow rendering without order
				if ( $this->is_preview ) {
					// Set page from current post if in preview
					global $post;
					if ( $post instanceof WP_Post && XLWCTY_Common::get_thank_you_page_post_type_slug() === $post->post_type ) {
						XLWCTY_Core()->data->set_page( $post->ID );
						XLWCTY_Core()->data->load_thankyou_metadata();
					}
				} else {
					return;
				}
			}
			
			$order_id = $order instanceof WC_Order ? XLWCTY_Compatibility::get_order_id( $order ) : 0;
			$page_id = XLWCTY_Core()->data->get_page();

		do_action( 'xlwcty_before_page_render' );
		if ( $order_id > 0 ) {
			$this->add_header_logs( sprintf( 'Order: #%s', $order_id ) );
		}
		$page_id_for_log = XLWCTY_Core()->data->get_page();
		if ( $page_id_for_log ) {
			$this->add_header_logs( sprintf( 'Page: %s', '<a target="_blank" href="' . XLWCTY_Common::get_builder_link( $page_id_for_log ) . '">' . get_the_title( $page_id_for_log ) . '</a>' ) );
		}
		ob_start();
		$this->include_template();
		do_action( 'xlwcty_aftr_page_render' );
		if ( $order_id > 0 ) {
			do_action( 'xlwcty_nextmove_thankyou_page', $order_id );
		}

		$output = ob_get_clean();
		return $output;
	}

	public function add_header_logs( $string ) {
		if ( ! in_array( $string, $this->header_info ) ) {
			array_push( $this->header_info, $string );
		}
	}

	/**
	 * Includes template file bases on chosen layout
	 */
	public function include_template() {
		$get_layout = XLWCTY_Core()->data->get_layout();
		if ( empty( $get_layout ) ) {
			return;
		}

		$template_file = plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'templates/' . $get_layout . '.php';
		if ( ! file_exists( $template_file ) ) {
			return;
		}

		$file_data = get_file_data( $template_file, array( 'XLWCTY Template Name' ) );
		if ( ! empty( $file_data ) ) {
			$this->add_header_logs( sprintf( 'Template: %s', $file_data[0] ) );
		}
		include $template_file;

		if ( isset( $_REQUEST['order_id'] ) && ! empty( $_REQUEST['order_id'] ) ) {
			$order_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['order_id'] ) );
			$order    = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$order->update_meta_data( '_xlwcty_thankyou_page', get_the_ID() );
				$order->save();
			}
		}
	}

	/**
	 * Renders a section of a layout
	 * Usually called by the templates so that specific section renders
	 *
	 * @param string $layout layout to call
	 * @param string $section section to render
	 *
	 * @return string
	 * @see xlwcty::include_template()
	 */
	public function render( $layout = 'basic', $section = 'first' ) {
		try {
			$get_layout_data = XLWCTY_Core()->data->get_layout_info();
			
			if ( isset( $get_layout_data[ $layout ] ) && isset( $get_layout_data[ $layout ][ $section ] ) && is_array( $get_layout_data[ $layout ][ $section ] ) ) {
				$components_count = count( $get_layout_data[ $layout ][ $section ] );
				foreach ( $get_layout_data[ $layout ][ $section ] as $components ) {
					if ( isset( $components['component'] ) ) {
						XLWCTY_Components::get_components( $components['component'] )->render_view( $components['slug'] );
					} else {
						XLWCTY_Components::get_components( $components['slug'] )->render_view( $components['slug'] );
					}
				}
			}
		} catch ( Exception $ex ) {
			echo '';
		}
	}

	public function validate_request() {
		// CRITICAL: Handle permalink check FIRST - this must work for everyone (including admins)
		// The permalink check is used by the plugin to verify the thank you page is accessible
		if ( is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() ) && filter_input( INPUT_GET, 'permalink_check' ) === 'yes' ) {
			wp_send_json( array(
				'status' => 'success',
			) );
			exit;
		}
		
		// Skip validation if this is Elementor's own preview mode
		if ( isset( $_REQUEST['elementor-preview'] ) ) {
			return;
		}
		
		// Skip validation if user can manage WooCommerce (admin) - let validate_preview handle preview mode
		// This allows admins to access preview URLs even without order_id/key initially
		if ( current_user_can( 'manage_woocommerce' ) ) {
			// Check if this looks like a preview attempt (NextMove page without order params)
			$is_nextmove_page = is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() );
			if ( $is_nextmove_page ) {
				// Let validate_preview handle adding preview parameters
				return;
			}
		}
		
		if ( is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() ) && $this->is_preview === false && ( is_null( filter_input( INPUT_GET, 'order_id' ) ) || is_null( filter_input( INPUT_GET, 'key' ) ) ) ) {
			wp_redirect( home_url() );
			exit;
		}
	}

	public function validate_preview() {
		// Only skip if this is Elementor's own preview mode (not our preview mode)
		// Elementor uses 'elementor-preview' parameter, we use 'mode=preview'
		if ( isset( $_REQUEST['elementor-preview'] ) && ! isset( $_REQUEST['mode'] ) ) {
			return;
		}

		// Prevent redirect loops: check if we've already redirected in this request
		static $redirect_attempted = false;
		if ( $redirect_attempted ) {
			return;
		}

		global $post, $wp_query;
		
		// Check if this is a NextMove page (singular or via query var)
		// When Elementor is active, we need to check multiple ways to detect the page
		$is_nextmove_page = false;
		
		// Method 1: Check if current post is NextMove type
		if ( $post instanceof WP_Post && XLWCTY_Common::get_thank_you_page_post_type_slug() === $post->post_type ) {
			$is_nextmove_page = true;
		}
		
		// Method 2: Check via is_singular
		if ( ! $is_nextmove_page ) {
			$is_nextmove_page = is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() );
		}
		
		// Method 3: Check query vars (works even when Elementor modifies queries)
		if ( ! $is_nextmove_page && isset( $wp_query->query_vars['post_type'] ) ) {
			if ( $wp_query->query_vars['post_type'] === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
				$is_nextmove_page = true;
			}
		}
		
		// Method 4: Check by URL path (for Elementor-modified URLs like /en/order-received/)
		if ( ! $is_nextmove_page ) {
			$request_uri = $this->get_sanitized_request_uri();
			if ( $request_uri ) {
				// Check if URL contains thank you page slugs (dynamically detected)
				$thankyou_slugs = $this->get_thankyou_page_slugs();
				$slug_pattern = implode( '|', array_map( 'preg_quote', $thankyou_slugs ) );
				if ( preg_match( '#/(' . $slug_pattern . ')/#', $request_uri ) ) {
					// Try to find the post by name from URL
					$url_parts = explode( '/', trim( parse_url( $request_uri, PHP_URL_PATH ), '/' ) );
					// Remove language prefix and thank you slugs
					$language_codes = $this->get_wpml_language_codes();
					$exclude_parts = array_merge( $language_codes, $thankyou_slugs );
					$url_parts = array_filter( $url_parts, function( $part ) use ( $exclude_parts ) {
						return ! empty( $part ) && ! in_array( $part, $exclude_parts );
					} );
					$url_parts = array_values( $url_parts );

					// Get the last part as page slug
					$page_slug = end( $url_parts );
					if ( $page_slug && ! in_array( $page_slug, $thankyou_slugs ) ) {
						$possible_post = get_page_by_path( $page_slug, OBJECT, XLWCTY_Common::get_thank_you_page_post_type_slug() );
						if ( $possible_post && $possible_post->post_type === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
							$post = $possible_post;
							// Set global post and query vars so WordPress recognizes it
							// This prevents 404 errors when Elementor modifies URLs
							global $wp_query;
							$wp_query->queried_object = $possible_post;
							$wp_query->queried_object_id = $possible_post->ID;
							$wp_query->post = $possible_post;
							$wp_query->posts = array( $possible_post );
							$wp_query->post_count = 1;
							$wp_query->found_posts = 1;
							$wp_query->is_singular = true;
							$wp_query->is_single = true;
							$wp_query->is_page = false;
							$wp_query->is_404 = false;
							$wp_query->query_vars['post_type'] = XLWCTY_Common::get_thank_you_page_post_type_slug();
							$wp_query->query_vars['name'] = $possible_post->post_name;
							$wp_query->query_vars['p'] = $possible_post->ID;
							$is_nextmove_page = true;
							
							// Only log in debug mode
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								}
						}
					}
				}
			}
		}
		
		// If we detected the page via URL but $post isn't set, try to get it
		if ( $is_nextmove_page && ( ! $post instanceof WP_Post || $post->ID === 0 ) ) {
			$request_uri = $this->get_sanitized_request_uri();
			$thankyou_slugs = $this->get_thankyou_page_slugs();
			$slug_pattern = implode( '|', array_map( 'preg_quote', $thankyou_slugs ) );
			if ( $request_uri && preg_match( '#/(' . $slug_pattern . ')/#', $request_uri ) ) {
				$url_parts = explode( '/', trim( parse_url( $request_uri, PHP_URL_PATH ), '/' ) );
				$language_codes = $this->get_wpml_language_codes();
				$exclude_parts = array_merge( $language_codes, $thankyou_slugs );
				$url_parts = array_filter( $url_parts, function( $part ) use ( $exclude_parts ) {
					return ! empty( $part ) && ! in_array( $part, $exclude_parts );
				} );
				$url_parts = array_values( $url_parts );
				$page_slug = end( $url_parts );
				if ( $page_slug ) {
					$possible_post = get_page_by_path( $page_slug, OBJECT, XLWCTY_Common::get_thank_you_page_post_type_slug() );
					if ( $possible_post && $possible_post->post_type === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
						$post = $possible_post;
					}
				}
			}
		}
		
		if ( ! $is_nextmove_page || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Handle WPML language switching for preview (only if WPML is active)
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) && class_exists( 'XLWCTY_WPML' ) ) {
			$wpml_compat = XLWCTY_WPML::get_instance();
			if ( method_exists( $wpml_compat, 'detect_language_from_url' ) ) {
				$url_lang = $wpml_compat->detect_language_from_url();
				if ( $url_lang ) {
					global $sitepress;
					if ( $sitepress instanceof SitePress ) {
						$sitepress->switch_lang( $url_lang, true );
					}
				}
			}
		}

		// FIRST: Check REQUEST_URI directly for parameters (most reliable, not affected by plugins)
		// This prevents redirect loops when Elementor/WPML strip $_GET parameters
		$request_uri = $this->get_sanitized_request_uri();
		$uri_has_all_params = false;
		if ( $request_uri ) {
			$url_parts = parse_url( $request_uri );
			if ( isset( $url_parts['query'] ) ) {
				parse_str( $url_parts['query'], $uri_params );
				$uri_has_all_params = (
					isset( $uri_params['order_id'] ) && ! empty( $uri_params['order_id'] ) &&
					isset( $uri_params['key'] ) && ! empty( $uri_params['key'] ) &&
					isset( $uri_params['mode'] ) && $uri_params['mode'] === 'preview'
				);
			}
		}

		// Get parameters from both filter_input and $_GET as fallback
		// filter_input can return null in some cases, so we check $_GET as backup
		$order_id_param = filter_input( INPUT_GET, 'order_id' );
		if ( $order_id_param === null && isset( $_GET['order_id'] ) ) {
			$order_id_param = sanitize_text_field( $_GET['order_id'] );
		}
		// Also check URI params if $_GET is empty (Elementor/WPML might strip $_GET)
		if ( empty( $order_id_param ) && isset( $uri_params['order_id'] ) ) {
			$order_id_param = sanitize_text_field( $uri_params['order_id'] );
		}
		
		$mode_param = filter_input( INPUT_GET, 'mode' );
		if ( $mode_param === null && isset( $_GET['mode'] ) ) {
			$mode_param = sanitize_text_field( $_GET['mode'] );
		}
		if ( empty( $mode_param ) && isset( $uri_params['mode'] ) ) {
			$mode_param = sanitize_text_field( $uri_params['mode'] );
		}
		
		$key_param = filter_input( INPUT_GET, 'key' );
		if ( $key_param === null && isset( $_GET['key'] ) ) {
			$key_param = sanitize_text_field( $_GET['key'] );
		}
		if ( empty( $key_param ) && isset( $uri_params['key'] ) ) {
			$key_param = sanitize_text_field( $uri_params['key'] );
		}
		
		// CRITICAL: If URI already has all params, return early - DO NOT redirect
		// This prevents infinite loops when Elementor/WPML strip $_GET but URI still has params
		if ( $uri_has_all_params ) {
			return; // Exit early - params are in URL, don't redirect
		}
		
		// Check if all required parameters are present (either in $_GET or URI)
		$has_all_params = ( $mode_param === 'preview' && ! empty( $order_id_param ) && ! empty( $key_param ) ) || $uri_has_all_params;
		
		// If mode=preview is missing or order_id/key are missing, we need to redirect
		// This handles cases where Elementor or other plugins strip query parameters
		// BUT: Only redirect if we don't already have all parameters (prevent loops)
		if ( ! $has_all_params ) {
			/**
			 * case where we do not get order_id or preview parameters are missing
			 */
			
			// If we have an order_id but missing mode/key, try to get the order and its key
			if ( $order_id_param && ( $mode_param !== 'preview' || $key_param === null || $key_param === '' ) ) {
				$existing_order = wc_get_order( $order_id_param );
				if ( $existing_order instanceof WC_Order ) {
					$order_id = XLWCTY_Compatibility::get_order_id( $existing_order );
					$order_key = XLWCTY_Compatibility::get_order_data( $existing_order, 'order_key' );
					
					// Use current request URL as base to preserve Elementor-modified URLs
					// This ensures we work correctly even when Elementor filters permalinks
					$current_url = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . $this->get_sanitized_request_uri();
					$url_parts = parse_url( $current_url );
					
					// Build base URL from current request
					if ( ! $url_parts ) {
						// Fallback: get permalink with proper language
						$permalink = get_permalink( $post );
						
						// Handle WPML language in URL (only if WPML is active)
						if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) && class_exists( 'XLWCTY_WPML' ) ) {
							$wpml_compat = XLWCTY_WPML::get_instance();
							if ( method_exists( $wpml_compat, 'get_order_language' ) && method_exists( $wpml_compat, 'get_current_language' ) ) {
								$order_lang = $wpml_compat->get_order_language( $existing_order );
								$current_lang = $wpml_compat->get_current_language();
								
								if ( $order_lang && $order_lang !== $current_lang ) {
									global $sitepress;
									if ( $sitepress instanceof SitePress ) {
										$sitepress->switch_lang( $order_lang, true );
										$permalink = get_permalink( $post );
										$sitepress->switch_lang( $current_lang, true );
									}
								}
							}
						}
						$url_parts = parse_url( $permalink );
					}
					
					// Build base URL without query parameters
					$base_url = '';
					if ( isset( $url_parts['scheme'] ) ) {
						$base_url .= $url_parts['scheme'] . '://';
					}
					if ( isset( $url_parts['host'] ) ) {
						$base_url .= $url_parts['host'];
					}
					if ( isset( $url_parts['port'] ) ) {
						$base_url .= ':' . $url_parts['port'];
					}
					if ( isset( $url_parts['path'] ) ) {
						$base_url .= $url_parts['path'];
					}
					
					// Build query args - preserve lang if it exists
					$query_args = array(
						'order_id' => $order_id,
						'key'      => $order_key,
						'mode'     => 'preview',
					);
					
					// Preserve lang parameter from current request
					$current_lang_param = filter_input( INPUT_GET, 'lang' );
					if ( $current_lang_param ) {
						$query_args['lang'] = $current_lang_param;
					} elseif ( isset( $url_parts['query'] ) ) {
						parse_str( $url_parts['query'], $existing_params );
						if ( isset( $existing_params['lang'] ) ) {
							$query_args['lang'] = $existing_params['lang'];
						}
					}
					
				$link = add_query_arg( $query_args, $base_url );
				$link = apply_filters( 'xlwcty_redirect_preview_link', $link );
				
				// Prevent redirect loops: check if redirect URL is same as current URL
				$current_url_full = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . $this->get_sanitized_request_uri();
				$current_url_normalized = remove_query_arg( array( 'order_id', 'key', 'mode', 'lang' ), $current_url_full );
				$redirect_url_normalized = remove_query_arg( array( 'order_id', 'key', 'mode', 'lang' ), $link );
				
				// If base URLs match and we're just adding/updating query params, check if params already exist
				if ( $current_url_normalized === $redirect_url_normalized ) {
					// Parse current URL to check existing params (check REQUEST_URI directly, not $_GET)
					$current_url_parts = parse_url( $current_url_full );
					if ( isset( $current_url_parts['query'] ) ) {
						parse_str( $current_url_parts['query'], $current_params );
						// If all required params already exist, don't redirect (prevent loop)
						if ( isset( $current_params['order_id'] ) && isset( $current_params['key'] ) && isset( $current_params['mode'] ) && $current_params['mode'] === 'preview' ) {
							return; // Exit early, don't redirect
						}
					}
				}
				
				// Mark that we're attempting a redirect
				$redirect_attempted = true;
					
					wp_safe_redirect( $link );
					exit;
				}
			}
			
			$get_chosen_order_meta = get_post_meta( $post->ID, '_xlwcty_chosen_order_preview', true );
			if ( $get_chosen_order_meta === '' ) {
				// Get allowed statuses including refunded
				$allowed_status = apply_filters( 'xlwcty_get_order_statuses', XLWCTY_Core()->data->get_option( 'allowed_order_statuses' ) );
				$args           = array(
					'status'    => $allowed_status,
					'post_type' => 'shop_order',
					'limit'     => 1,
					'orderby'   => 'date',
					'order'     => 'DESC',
				);
				$get_orders     = wc_get_orders( $args );
				if ( is_array( $get_orders ) && count( $get_orders ) === 0 ) {
					wp_die( __( 'We are unable to show preview for this thank you page.', 'woo-thank-you-page-nextmove-lite' ) );
				} else {
					$current_order = current( $get_orders );
					$order_id = XLWCTY_Compatibility::get_order_id( $current_order );
					$order_key = XLWCTY_Compatibility::get_order_data( $current_order, 'order_key' );
					
					// Use current request URL as base to preserve Elementor-modified URLs
					// This ensures we work correctly even when Elementor filters permalinks
					$current_url = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . $this->get_sanitized_request_uri();
					$url_parts = parse_url( $current_url );
					
					// If current URL parsing fails, fallback to get_permalink with WPML handling
					if ( ! $url_parts ) {
						$permalink = get_permalink( $post );
						
						// Handle WPML language in URL (only if WPML is active)
						if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) && class_exists( 'XLWCTY_WPML' ) ) {
							$wpml_compat = XLWCTY_WPML::get_instance();
							if ( method_exists( $wpml_compat, 'get_order_language' ) && method_exists( $wpml_compat, 'get_current_language' ) ) {
								$order_lang = $wpml_compat->get_order_language( $current_order );
								$current_lang = $wpml_compat->get_current_language();
								
								if ( $order_lang && $order_lang !== $current_lang ) {
									global $sitepress;
									if ( $sitepress instanceof SitePress ) {
										$sitepress->switch_lang( $order_lang, true );
										$permalink = get_permalink( $post );
										$sitepress->switch_lang( $current_lang, true );
									}
								}
							}
						}
						$url_parts = parse_url( $permalink );
					}
					
					// Build base URL without query parameters
					$base_url = '';
					if ( isset( $url_parts['scheme'] ) ) {
						$base_url .= $url_parts['scheme'] . '://';
					}
					if ( isset( $url_parts['host'] ) ) {
						$base_url .= $url_parts['host'];
					}
					if ( isset( $url_parts['port'] ) ) {
						$base_url .= ':' . $url_parts['port'];
					}
					if ( isset( $url_parts['path'] ) ) {
						$base_url .= $url_parts['path'];
					}
					
					// Build query args - preserve lang if it exists
					$query_args = array(
						'order_id' => $order_id,
						'key'      => $order_key,
						'mode'     => 'preview',
					);
					
					// Preserve lang parameter from current request
					$current_lang_param = filter_input( INPUT_GET, 'lang' );
					if ( $current_lang_param ) {
						$query_args['lang'] = $current_lang_param;
					} elseif ( isset( $url_parts['query'] ) ) {
						parse_str( $url_parts['query'], $existing_params );
						if ( isset( $existing_params['lang'] ) ) {
							$query_args['lang'] = $existing_params['lang'];
						}
					}
					
					$link = add_query_arg( $query_args, $base_url );

					$link = apply_filters( 'xlwcty_redirect_preview_link', $link );

					wp_safe_redirect( $link );
					exit;
				}
			} else {
				$get_chosen_order = wc_get_order( $get_chosen_order_meta );
				if ( ! $get_chosen_order instanceof WC_Order ) {
					return;
				}
				
				$order_id = $get_chosen_order_meta;
				$order_key = XLWCTY_Compatibility::get_order_data( $get_chosen_order, 'order_key' );
				
				// Get permalink with proper language
				$permalink = get_permalink( $post );
				
				// Handle WPML language in URL (only if WPML is active)
				if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) && class_exists( 'XLWCTY_WPML' ) ) {
					$wpml_compat = XLWCTY_WPML::get_instance();
					if ( method_exists( $wpml_compat, 'get_order_language' ) && method_exists( $wpml_compat, 'get_current_language' ) ) {
						$order_lang = $wpml_compat->get_order_language( $get_chosen_order );
						$current_lang = $wpml_compat->get_current_language();
						
						if ( $order_lang && $order_lang !== $current_lang ) {
							global $sitepress;
							if ( $sitepress instanceof SitePress ) {
								$sitepress->switch_lang( $order_lang, true );
								$permalink = get_permalink( $post );
								$sitepress->switch_lang( $current_lang, true );
							}
						}
					}
				}
				
				// Parse URL to preserve any existing query parameters (like lang)
				$url_parts = parse_url( $permalink );
				if ( ! $url_parts ) {
					// Fallback if parse_url fails
					$base_url = $permalink;
				} else {
					$base_url = '';
					if ( isset( $url_parts['scheme'] ) ) {
						$base_url .= $url_parts['scheme'] . '://';
					}
					if ( isset( $url_parts['host'] ) ) {
						$base_url .= $url_parts['host'];
					}
					if ( isset( $url_parts['port'] ) ) {
						$base_url .= ':' . $url_parts['port'];
					}
					if ( isset( $url_parts['path'] ) ) {
						$base_url .= $url_parts['path'];
					}
				}
				
				// Build query args - preserve lang if it exists
				$query_args = array(
					'order_id' => $order_id,
					'key'      => $order_key,
					'mode'     => 'preview',
				);
				
				// Preserve lang parameter if it exists in the permalink or current request
				if ( isset( $url_parts['query'] ) ) {
					parse_str( $url_parts['query'], $existing_params );
					if ( isset( $existing_params['lang'] ) ) {
						$query_args['lang'] = $existing_params['lang'];
					}
				}
				// Also check current request for lang parameter
				$current_lang = filter_input( INPUT_GET, 'lang' );
				if ( $current_lang ) {
					$query_args['lang'] = $current_lang;
				}
				
				$link = add_query_arg( $query_args, $base_url );

				$link = apply_filters( 'xlwcty_redirect_preview_link', $link );
				
				// Prevent redirect loops: check if redirect URL is same as current URL
				$current_url_full = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . $this->get_sanitized_request_uri();
				$current_url_normalized = remove_query_arg( array( 'order_id', 'key', 'mode', 'lang' ), $current_url_full );
				$redirect_url_normalized = remove_query_arg( array( 'order_id', 'key', 'mode', 'lang' ), $link );
				
				// If base URLs match and we're just adding/updating query params, check if params already exist
				if ( $current_url_normalized === $redirect_url_normalized ) {
					// Parse current URL to check existing params (check REQUEST_URI directly, not $_GET)
					$current_url_parts = parse_url( $current_url_full );
					if ( isset( $current_url_parts['query'] ) ) {
						parse_str( $current_url_parts['query'], $current_params );
						// If all required params already exist, don't redirect (prevent loop)
						if ( isset( $current_params['order_id'] ) && isset( $current_params['key'] ) && isset( $current_params['mode'] ) && $current_params['mode'] === 'preview' ) {
							return; // Exit early, don't redirect
						}
					}
				}
				
				// Mark that we're attempting a redirect
				$redirect_attempted = true;

				wp_safe_redirect( $link );
				exit;
			}
		}
	}

	/**
	 * Checking query arguments and validating preview mode
	 */
	public function maybe_preview_load() {
		global $post;
		
		// Check if this is a NextMove page (singular or via query var)
		$is_nextmove_page = is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() );
		if ( ! $is_nextmove_page ) {
			// Check if query var indicates NextMove page
			global $wp_query;
			if ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
				$is_nextmove_page = true;
			}
		}
		
		$is_preview_mode = filter_input( INPUT_GET, 'mode' ) === 'preview';
		
		if ( $is_nextmove_page && $is_preview_mode ) {
			// Handle WPML language switching for preview (only if WPML is active)
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) && class_exists( 'XLWCTY_WPML' ) ) {
				$wpml_compat = XLWCTY_WPML::get_instance();
				if ( method_exists( $wpml_compat, 'detect_language_from_url' ) ) {
					$url_lang = $wpml_compat->detect_language_from_url();
					if ( $url_lang ) {
						global $sitepress;
						if ( $sitepress instanceof SitePress ) {
							$sitepress->switch_lang( $url_lang, true );
						}
					}
				}
			}

			/**
			 * Allowing theme and plugins to allow preview before it checks to user capability
			 */
			$this->is_preview = apply_filters( 'xlwcty_allow_preview', $this->is_preview );
			/**
			 * Checking user capability
			 */
			if ( $this->is_preview === false && ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( 'You are not allowed to access this page. ' );
			}
			$this->is_preview = true;
			
			// Load order if order_id is provided
			$order_id = filter_input( INPUT_GET, 'order_id' );
			if ( $order_id ) {
				XLWCTY_Core()->data->load_order( (int) $order_id );
				
				// Setup page for this order
				if ( XLWCTY_Core()->data->get_order() instanceof WC_Order ) {
					XLWCTY_Core()->data->setup_thankyou_post( (int) $order_id, true );
					XLWCTY_Core()->data->load_thankyou_metadata();
				}
			}
		}
	}

	/**
	 * Validates current order and checks if order qualifies for the current loading
	 * loads native thank you page if order don't qualify
	 * @uses WC_Order::get_checkout_order_received_url()
	 * @uses WC_Order::post_status
	 */
	public function validate_order() {
		global $post;
		
		// Only validate orders on NextMove pages or order received pages
		$is_nextmove_page = is_singular( XLWCTY_Common::get_thank_you_page_post_type_slug() );
		$is_order_received = is_order_received_page();
		
		if ( ! $is_nextmove_page && ! $is_order_received ) {
			// Not a thank you page, skip validation
			return;
		}
		
		// Try to load order if not already loaded
		$order_id_from_url = filter_input( INPUT_GET, 'order_id' );
		if ( $order_id_from_url && ! XLWCTY_Core()->data->get_order() instanceof WC_Order ) {
			XLWCTY_Core()->data->load_order( (int) $order_id_from_url );
			
			// Setup page for this order if it's a NextMove page
			if ( $is_nextmove_page && XLWCTY_Core()->data->get_order() instanceof WC_Order ) {
				XLWCTY_Core()->data->setup_thankyou_post( (int) $order_id_from_url, $this->is_preview );
				XLWCTY_Core()->data->load_thankyou_metadata();
			}
		}
		
		$order = XLWCTY_Core()->data->get_order();

		if ( ! $order instanceof WC_Order ) {
			// In preview mode, allow rendering without order on NextMove pages
			if ( $this->is_preview && $is_nextmove_page ) {
				return;
			}
			
			// If not a NextMove page, don't log warning (it's normal)
			if ( $is_nextmove_page ) {
				}
			return;
		}

		$order_id = XLWCTY_Compatibility::get_order_id( $order );
		/**
		 * Check order key from URL so that users cannot open other's thank you page
		 */
		$order_key = XLWCTY_Compatibility::get_order_data( $order, 'order_key' );

		$check_for_empty_key = apply_filters( 'xlwcty_check_for_empty_order_key', true );
		if ( $check_for_empty_key ) {
			/** empty key than redirect to home page **/
			if ( empty( filter_input( INPUT_GET, 'key' ) ) ) {
				wp_redirect( home_url() );
				exit;
			}
		}

		$url_key = filter_input( INPUT_GET, 'key' );
		if ( $url_key !== $order_key ) {
			if ( XLWCTY_Common::get_thank_you_page_post_type_slug() === $post->post_type ) {
				wp_die( __( 'Unable to process your request.', 'woo-thank-you-page-nextmove-lite' ) );
			}

			XLWCTY_Core()->data->reset_order();

			return;
		}

		$current_order_status = XLWCTY_Compatibility::get_order_status( $order );

		/**
		 * Check for $this->xlwcty_is_thankyou added to redirect to thank you page only if it's NextMove thank you page or leave as it is.
		 * This check is added as it causes conflict with upstroke plugin because it changes the order status which can be the case with any third party plugin as well.
		 */
		$allowed_statuses = XLWCTY_Common::get_order_statuses();

		// In preview mode, always allow regardless of status
		if ( $this->is_preview ) {
			return;
		}
		
		if ( ! in_array( $current_order_status, $allowed_statuses, true ) && true === $this->xlwcty_is_thankyou ) {
			/**
			 * Removing our filter so that it would not modify order_received_url when we fetch it
			 */
			if ( strpos( $current_order_status, 'cancelled' ) === false ) {
				remove_filter( 'woocommerce_get_checkout_order_received_url', array(
					$this,
					'redirect_to_thankyou',
				), 99, 2 );
				$url = $order->get_checkout_order_received_url();

				wp_safe_redirect( $url );
				exit;
			}
		}
	}

	/**
	 * Hooked over `wp_footer`
	 * Trying and executing wc native thankyou hooks
	 * Payment Gateways and other plugin usually use these hooks to read order data and process
	 * Also removes native woocommerce_order_details_table() to prevent order table load
	 */
	public function execute_wc_thankyou_hooks() {
		if ( ! $this->is_xlwcty_page() ) {
			return;
		}
		if ( ! XLWCTY_Core()->data->get_order() instanceof WC_Order ) {
			return;
		}
		$order = XLWCTY_Core()->data->get_order();
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
		$payment_method = XLWCTY_Compatibility::get_order_data( $order, 'payment_method' )
		?>
        <div class="xlwcty_wc_thankyou" style="display: none; opacity: 0">
			<?php
			do_action( 'woocommerce_thankyou', XLWCTY_Compatibility::get_order_id( $order ) );
			do_action( "woocommerce_thankyou_{$payment_method}", XLWCTY_Compatibility::get_order_id( $order ) );
			?>
        </div>
		<?php
	}

	public function print_html_header_info() {
		ob_start();
		if ( $this->header_info && count( $this->header_info ) > 0 ) {
			foreach ( $this->header_info as $key => $info_row ) {
				?>
                <li id="wp-admin-bar-xlwcty_admin_page_node_<?php echo esc_attr( (string) $key ); ?>">
					<span class="ab-item">
						<?php echo esc_html( (string) $info_row ); ?>
					</span>
                </li>
				<?php
			}
		}
		echo "<div class='xlwcty_header_passed' style='display: none;'>" . ob_get_clean() . '</div>';
	}

	/**
	 * Adding Script data to help in debug what campaign is ON for that product.
	 * Using WordPress way to localize a script
	 * @see WP_Scripts::localize()
	 */
	public function maybe_add_info_footer() {
		$l10n = array();
		if ( $this->header_info && count( $this->header_info ) > 0 ) {
			foreach ( (array) $this->header_info as $key => $value ) {
				if ( ! is_scalar( $value ) ) {
					continue;
				}
				$l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
			}
		}
		$script = 'var xlwcty_info = ' . wp_json_encode( $l10n ) . ';';
		?>
        <script type="text/javascript">
			<?php echo $script; ?>
        </script>
		<?php
	}

	public function register_hooks() {
		add_filter( 'woocommerce_short_description', array( $this, 'woocommerce_short_desc_limit_words' ), 99 );
		add_filter( 'woocommerce_product_get_short_description', array(
			$this,
			'woocommerce_short_desc_limit_words',
		), 99 );
	}

	public function de_register_hooks() {
		remove_filter( 'woocommerce_short_description', array( $this, 'woocommerce_short_desc_limit_words' ), 99 );
		remove_filter( 'woocommerce_product_get_short_description', array(
			$this,
			'woocommerce_short_desc_limit_words',
		), 99 );
	}

	public function woocommerce_short_desc_limit_words( $excerpt ) {
		return '<p>' . wp_trim_words( $excerpt, 30 ) . '</p>';
	}

	/**
	 * Inject NextMove content into Elementor's the_content output
	 * This ensures NextMove thank you page content displays even when Elementor replaces the_content
	 * 
	 * @param string $content The content from Elementor or WordPress
	 * @return string Modified content with NextMove components
	 */
	public function inject_nextmove_content_into_elementor( $content ) {
		// Prevent recursion - if we're already processing, return
		static $processing = false;
		if ( $processing ) {
			return $content;
		}
		
		// Only process if this is a NextMove page
		if ( ! $this->is_xlwcty_page() ) {
			return $content;
		}
		
		// Don't process if we're in Elementor's own preview mode (without our mode=preview)
		if ( isset( $_REQUEST['elementor-preview'] ) && ! isset( $_REQUEST['mode'] ) ) {
			return $content;
		}
		
		// Check if NextMove content is already in the content (via shortcode)
		if ( has_shortcode( $content, 'xlwcty_load' ) ) {
			return $content;
		}
		
		// Check if content already contains NextMove wrapper classes
		if ( strpos( $content, 'xlwcty_wrap' ) !== false ) {
			return $content;
		}
		
		$processing = true;
		
		// Render NextMove content
		$nextmove_output = $this->maybe_render_elements();
		
		$processing = false;
		
		// If we have NextMove output, inject it
		if ( ! empty( $nextmove_output ) ) {
			// Replace content with NextMove output
			// When Elementor is active, it replaces the_content with builder content
			// We need to replace that with NextMove's thank you page content
			$content = $nextmove_output;
		}
		
		return $content;
	}

	public function add_body_class( $classes, $class ) {
		global $post, $xlwcty_is_thankyou;
		$nm_slug = XLWCTY_Common::get_thank_you_page_post_type_slug();
		if ( ! is_singular( $nm_slug ) ) {
			return $classes;
		}
		if ( false === $xlwcty_is_thankyou ) {
			return $classes;
		}
		if ( is_array( $classes ) && count( $classes ) > 0 ) {
			$post_type = 'page';
			$classes[] = $post_type;
			$classes[] = "{$post_type}-template";

			$template_slug = get_page_template_slug( $post->ID );
			if ( empty( $template_slug ) ) {
				$template_parts[0] = 'default';
			} else {
				$template_parts = explode( '/', $template_slug );
			}
			foreach ( $template_parts as $part ) {
				$classes[] = "{$post_type}-template-" . sanitize_html_class( str_replace( array(
						'.',
						'/',
					), '-', basename( $part, '.php' ) ) );
			}
			$classes[] = "{$post_type}-template-" . sanitize_html_class( str_replace( '.', '-', $template_slug ) );
		}

		return $classes;
	}

	public function facebook_pixel_tracking_script( $order_id ) {
		include __DIR__ . '/google-facebook-ecommerce.php';
	}

	public function facebook_pixel_enabled() {
		$facebook_enable = XLWCTY_Core()->data->get_option( 'enable_fb_ecom_tracking' );
		$facebook_id     = XLWCTY_Core()->data->get_option( 'ga_fb_pixel_id' );

		if ( $facebook_enable === 'on' && $facebook_id > 0 ) {
			return $facebook_id;
		}

		return false;
	}

	public function google_analytics_enabled() {
		$analytic_enable = XLWCTY_Core()->data->get_option( 'enable_ga_ecom_tracking' );
		$analytic_id     = XLWCTY_Core()->data->get_option( 'ga_analytics_id' );
		if ( $analytic_enable === 'on' && ! empty( $analytic_id ) ) {
			return $analytic_id;
		}

		return false;
	}

	public function declare_wc_checkout_page( $bool ) {
		if ( $this->is_xlwcty_page() === true ) {
			return true;
		}

		return $bool;
	}

	public function maybe_pass_no_cache_header() {
		if ( $this->is_xlwcty_page() ) {
			$this->set_nocache_constants();
			nocache_headers();
		}
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set_nocache_constants() {
		$this->maybe_define_constant( 'DONOTCACHEPAGE', true );
		$this->maybe_define_constant( 'DONOTCACHEOBJECT', true );
		$this->maybe_define_constant( 'DONOTCACHEDB', true );

		return null;
	}

	function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


	public function maybe_push_script_for_map_check() {
		if ( $this->is_xlwcty_page() === false ) {
			return;
		}
		?>
        <script>
            var xlwcty_is_google_map_failed = false;
            if (typeof gm_authFailure !== 'function ') {
                function gm_authFailure() {
                    console.log('Google map error found');
                    xlwcty_is_google_map_failed = true;
                    xlwctyCore.loadmap();
                }
            }
        </script>
		<?php
	}

	public function maybe_set_query_var( $wp_query_obj ) {
		if ( false === $this->is_xlwcty_page() ) {
			return;
		}

		$get_order_id = filter_input( INPUT_GET, 'order_id' );
		if ( $get_order_id === null ) {
			return;
		}
		$wp_query_obj->query_vars['order-received'] = $get_order_id;
		set_query_var( 'order-received', $get_order_id );
	}

	public function declare_wc_order_received_page( $bool ) {
		if ( $this->is_xlwcty_page() === true ) {
			return true;
		}

		return $bool;
	}

	public function parse_request_for_thankyou( $wp_query_obj ) {
		if ( isset( $wp_query_obj->query_vars['post_type'] ) && ( XLWCTY_Common::get_thank_you_page_post_type_slug() === $wp_query_obj->query_vars['post_type'] ) ) {
			$this->xlwcty_is_thankyou = true;
			
			// Check if this is a preview request
			$is_preview_mode = filter_input( INPUT_GET, 'mode' ) === 'preview';
			if ( $is_preview_mode ) {
				$this->is_preview = true;
			}
		}
	}

	public function parse_query_for_thankyou( $wp_query_obj ) {
		if ( $this->is_xlwcty_page() && $wp_query_obj->is_main_query() ) {
			$wp_query_obj->is_page   = true;
			$wp_query_obj->is_single = false;
		}
	}

	/**
	 * Perform any changes on NextMove Thank You page only
	 * xlwcty-themes-helper functions working on it.
	 */
	/**
	 * Fix query for Elementor-modified URLs before WordPress determines 404
	 * This prevents 404 errors by setting up the query correctly
	 */
	public function fix_query_for_elementor_urls( $query ) {
		// Only process main query
		if ( ! $query->is_main_query() ) {
			return;
		}

		// Check if URL matches NextMove page pattern
		$request_uri = $this->get_sanitized_request_uri();
		$thankyou_slugs = $this->get_thankyou_page_slugs();
		$slug_pattern = implode( '|', array_map( 'preg_quote', $thankyou_slugs ) );
		if ( ! $request_uri || ! preg_match( '#/(' . $slug_pattern . ')/#', $request_uri ) ) {
			return;
		}

		// Extract page slug from URL
		$url_parts = explode( '/', trim( parse_url( $request_uri, PHP_URL_PATH ), '/' ) );
		$language_codes = $this->get_wpml_language_codes();
		$exclude_parts = array_merge( $language_codes, $thankyou_slugs );
		$url_parts = array_filter( $url_parts, function( $part ) use ( $exclude_parts ) {
			return ! empty( $part ) && ! in_array( $part, $exclude_parts );
		} );
		$url_parts = array_values( $url_parts );
		$page_slug = end( $url_parts );

		if ( ! $page_slug ) {
			return;
		}

		// Try to find the post
		$possible_post = get_page_by_path( $page_slug, OBJECT, XLWCTY_Common::get_thank_you_page_post_type_slug() );
		if ( $possible_post && $possible_post->post_type === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
			// Set query vars to find this post
			$query->set( 'post_type', XLWCTY_Common::get_thank_you_page_post_type_slug() );
			$query->set( 'name', $possible_post->post_name );
			$query->set( 'p', $possible_post->ID );
			$query->is_singular = true;
			$query->is_single = true;
			$query->is_page = false;
			$query->is_404 = false;
		}
	}

	/**
	 * Fix 404 errors when Elementor modifies URLs
	 * Ensures WordPress recognizes NextMove pages even when URL structure is modified
	 */
	public function fix_404_for_elementor_urls() {
		global $wp_query, $post;

		// Only fix if we're getting a 404
		if ( ! $wp_query->is_404() ) {
			return;
		}

		// Check if URL matches NextMove page pattern
		$request_uri = $this->get_sanitized_request_uri();
		$thankyou_slugs = $this->get_thankyou_page_slugs();
		$slug_pattern = implode( '|', array_map( 'preg_quote', $thankyou_slugs ) );
		if ( ! $request_uri || ! preg_match( '#/(' . $slug_pattern . ')/#', $request_uri ) ) {
			return;
		}

		// Extract page slug from URL
		$url_parts = explode( '/', trim( parse_url( $request_uri, PHP_URL_PATH ), '/' ) );
		$language_codes = $this->get_wpml_language_codes();
		$exclude_parts = array_merge( $language_codes, $thankyou_slugs );
		$url_parts = array_filter( $url_parts, function( $part ) use ( $exclude_parts ) {
			return ! empty( $part ) && ! in_array( $part, $exclude_parts );
		} );
		$url_parts = array_values( $url_parts );
		$page_slug = end( $url_parts );

		if ( ! $page_slug ) {
			return;
		}
		
		// Try to find the post
		$possible_post = get_page_by_path( $page_slug, OBJECT, XLWCTY_Common::get_thank_you_page_post_type_slug() );
		if ( $possible_post && $possible_post->post_type === XLWCTY_Common::get_thank_you_page_post_type_slug() ) {
			// Found the post - fix the query to prevent 404
			$wp_query->queried_object = $possible_post;
			$wp_query->queried_object_id = $possible_post->ID;
			$wp_query->post = $possible_post;
			$wp_query->posts = array( $possible_post );
			$wp_query->post_count = 1;
			$wp_query->found_posts = 1;
			$wp_query->is_singular = true;
			$wp_query->is_single = true;
			$wp_query->is_page = false;
			$wp_query->is_404 = false;
			$wp_query->query_vars['post_type'] = XLWCTY_Common::get_thank_you_page_post_type_slug();
			$wp_query->query_vars['name'] = $possible_post->post_name;
			$wp_query->query_vars['p'] = $possible_post->ID;
			
			// Set global post
			$post = $possible_post;
			setup_postdata( $post );
		}
	}

	public function maybe_set_meta_to_hide_sidebar() {
		global $post;
		if ( $this->is_xlwcty_page() && $post instanceof WP_Post ) {
			do_action( 'nextmove_template_redirect_single_thankyou_page' );
		}
	}

	public function xlwcty_page_noindex() {
		$post_type = XLWCTY_Common::get_thank_you_page_post_type_slug();
		if ( is_singular( $post_type ) ) {
			echo "<meta name='robots' content='noindex,follow' />\n";
		}
	}

	/**
	 * Ensure order is loaded for NextMove pages
	 * This handles cases where order_id is in URL but order wasn't loaded yet
	 */
	public function maybe_load_order_for_nextmove_page() {
		if ( ! $this->is_xlwcty_page() ) {
			return;
		}

		$order = XLWCTY_Core()->data->get_order();
		if ( ! $order instanceof WC_Order ) {
			$order_id_from_url = filter_input( INPUT_GET, 'order_id' );
			if ( $order_id_from_url ) {
				XLWCTY_Core()->data->load_order( (int) $order_id_from_url );
				
				// If order loaded, setup thank you page for this order
				$order = XLWCTY_Core()->data->get_order();
				if ( $order instanceof WC_Order ) {
					XLWCTY_Core()->data->setup_thankyou_post( (int) $order_id_from_url, $this->is_preview );
					XLWCTY_Core()->data->load_thankyou_metadata();
				}
			}
		}
	}
}

if ( class_exists( 'XLWCTY_Core' ) ) {
	XLWCTY_Core::register( 'public', 'xlwcty' );
}
