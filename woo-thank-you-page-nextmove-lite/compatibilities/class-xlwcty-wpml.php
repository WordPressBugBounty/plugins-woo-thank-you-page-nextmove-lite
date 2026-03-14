<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCTY_WPML
 * WPML Multilingual CMS Compatibility
 *
 * @package NextMove
 * @author XLPlugins
 */
#[AllowDynamicProperties]
class XLWCTY_WPML {

	private static $ins = null;

	/**
	 * Cache for translated page IDs to avoid repeated lookups
	 *
	 * @var array
	 */
	private static $translation_cache = array();

	public function __construct() {
		if ( ! $this->is_wpml_active() ) {
			return;
		}
		$this->hooks();
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Check if WPML is active
	 */
	public function is_wpml_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' );
	}

	/**
	 * Register hooks
	 */
	public function hooks() {
		add_filter( 'xlwcty_woocommerce_get_checkout_order_received_url', array( $this, 'fix_permalink_for_wpml' ), 10, 2 );
		add_filter( 'xlwcty_before_query', array( $this, 'switch_language_context' ), 10, 1 );
		add_filter( 'xlwcty_permalink_check', array( $this, 'handle_permalink_check' ), 5, 3 );
		add_filter( 'xlwcty_permalink_reset_note', array( $this, 'add_permalink_reset_note' ), 10, 1 );
		add_filter( 'post_link', array( $this, 'fix_post_permalink' ), 10, 3 );
		add_action( 'xlwcty_before_setup_thankyou_post', array( $this, 'maybe_switch_language_from_order' ), 10, 1 );
		add_action( 'wpml_loaded', array( $this, 'register_post_type_with_wpml' ) );
	}

	/**
	 * Get SitePress instance
	 *
	 * @return SitePress|null
	 */
	public function get_sitepress() {
		global $sitepress;

		return ( $sitepress instanceof SitePress ) ? $sitepress : null;
	}

	/**
	 * Get current language code
	 */
	public function get_current_language() {
		$sitepress = $this->get_sitepress();

		return $sitepress ? $sitepress->get_current_language() : ( defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '' );
	}

	/**
	 * Get default language code
	 */
	public function get_default_language() {
		$sitepress = $this->get_sitepress();

		return $sitepress ? $sitepress->get_default_language() : '';
	}

	/**
	 * Get language from order
	 */
	public function get_order_language( $order ) {
		if ( ! $order instanceof WC_Order ) {
			// Try to detect language from URL if order not available
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang ) {
				return $url_lang;
			}

			return $this->get_current_language();
		}

		// Allow filtering to force default language (for emergency fallback)
		$forced_language = apply_filters( 'xlwcty_get_order_language', null, $order );
		if ( $forced_language ) {
			return $forced_language;
		}

		// Check order meta for language.
		$order_language = $order->get_meta( 'wpml_language' ) ?: $order->get_meta( '_wpml_language' );
		if ( $order_language ) {
			return $order_language;
		}

		// Check if language is stored in order meta with different keys
		$order_language = $order->get_meta( 'wpml_order_language' );
		if ( $order_language ) {
			return $order_language;
		}

		// Try to detect from URL
		$url_lang = $this->detect_language_from_url();
		if ( $url_lang ) {
			return $url_lang;
		}

		// Check customer language preference.
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {
			$customer_language = get_user_meta( $customer_id, 'wpml_language', true );
			if ( $customer_language ) {
				return $customer_language;
			}
		}

		$current_lang = $this->get_current_language();

		return $current_lang;
	}

	/**
	 * Detect language from URL
	 * Handles URLs like /en/order-received/ or /es/order-received/
	 * Made public for use in other classes
	 */
	public function detect_language_from_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$sitepress   = $this->get_sitepress();

		if ( ! $sitepress ) {
			return false;
		}

		// Get all active languages
		$active_languages = $sitepress->get_active_languages();
		if ( empty( $active_languages ) ) {
			return false;
		}

		// Extract language code from URL (e.g., /en/ or /es/)
		foreach ( $active_languages as $lang_code => $lang_data ) {
			// Check if URL starts with language code
			if ( preg_match( '#^/' . preg_quote( $lang_code, '#' ) . '/#', $request_uri ) ) {
				return $lang_code;
			}
		}

		// Also check for lang parameter
		if ( isset( $_GET['lang'] ) ) {
			$lang_param = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
			if ( isset( $active_languages[ $lang_param ] ) ) {
				return $lang_param;
			}
		}

		return false;
	}

	/**
	 * Switch language context for queries
	 */
	public function switch_language_context( $order_id ) {
		if ( ! $order_id ) {
			// Try to switch language from URL if no order ID
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang ) {
				$sitepress = $this->get_sitepress();
				if ( $sitepress ) {
					$sitepress->switch_lang( $url_lang, true );
				}
			}

			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			// Try URL detection if order not found
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang ) {
				$sitepress = $this->get_sitepress();
				if ( $sitepress ) {
					$sitepress->switch_lang( $url_lang, true );
				}
			}

			return;
		}

		$sitepress      = $this->get_sitepress();
		$order_language = $this->get_order_language( $order );
		$current_lang   = $this->get_current_language();

		if ( $sitepress && $order_language && $order_language !== $current_lang ) {
			$sitepress->switch_lang( $order_language, true );
		} elseif ( $sitepress && ! $order_language ) {
			// Fallback to URL detection
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang && $url_lang !== $current_lang ) {
				$sitepress->switch_lang( $url_lang, true );
			}
		}
	}

	/**
	 * Fix permalink for WPML
	 */
	public function fix_permalink_for_wpml( $url, $order ) {
		if ( empty( $url ) ) {
			return $url;
		}

		$sitepress = $this->get_sitepress();
		if ( ! $sitepress ) {
			return $url;
		}

		$order_language   = $this->get_order_language( $order );
		$current_language = $this->get_current_language();

		if ( $order_language === $current_language ) {
			return $url;
		}

		$sitepress->switch_lang( $order_language, true );
		$page_id = XLWCTY_Core()->data->get_page();

		if ( $page_id ) {
			// Get translated page ID for the order's language
			$translated_page_id = $this->get_translated_page_id( $page_id, $order_language );

			// Get permalink in the order's language
			$url = get_permalink( $translated_page_id );
			if ( $url ) {
				$url = XLWCTY_Common::prepare_single_post_url( $url, $order );
			}
		}

		$sitepress->switch_lang( $current_language, true );

		return $url;
	}

	/**
	 * Handle permalink check for WPML
	 */
	public function handle_permalink_check( $result, $url, $page_id ) {
		if ( empty( $url ) || ! $page_id ) {
			return null;
		}

		$sitepress = $this->get_sitepress();
		if ( ! $sitepress ) {
			return null;
		}

		$page = get_post( $page_id );
		if ( ! $page || $page->post_status !== 'publish' ) {
			return null;
		}

		// If page exists and permalink can be generated, assume OK.
		$test_permalink = get_permalink( $page_id );
		if ( $test_permalink && ! is_wp_error( $test_permalink ) ) {
			// Quick HTTP check, but don't fail if it returns 404 (WPML URLs may need order params).
			$response_code = $this->check_permalink_accessibility( $url );
			if ( $response_code !== false && $response_code !== 404 ) {
				return true;
			}

			// Even if HTTP check fails, if permalink generation works, assume OK.
			return true;
		}

		return false;
	}

	/**
	 * Add WPML-specific note to permalink reset message
	 */
	public function add_permalink_reset_note( $note ) {
		return ' ' . __( '(With WPML, you may also need to go to WPML → Settings → Post Types and ensure "Thank You Page" is set to translatable, then flush permalinks)', 'woo-thank-you-page-nextmove-lite' );
	}

	/**
	 * Fix post permalink for NextMove pages
	 */
	public function fix_post_permalink( $post_link, $post, $leavename ) {
		if ( ! $post instanceof WP_Post || XLWCTY_Common::get_thank_you_page_post_type_slug() !== $post->post_type ) {
			return $post_link;
		}

		$sitepress = $this->get_sitepress();
		if ( $sitepress ) {
			$post_link = apply_filters( 'wpml_permalink', $post_link, $this->get_current_language() );
		}

		return $post_link;
	}

	/**
	 * Maybe switch language from order
	 */
	public function maybe_switch_language_from_order( $order_id ) {
		if ( ! $order_id ) {
			// Try URL detection if no order ID
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang ) {
				$sitepress = $this->get_sitepress();
				if ( $sitepress ) {
					$sitepress->switch_lang( $url_lang, true );
				}
			}

			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			// Try URL detection if order not found
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang ) {
				$sitepress = $this->get_sitepress();
				if ( $sitepress ) {
					$sitepress->switch_lang( $url_lang, true );
				}
			}

			return;
		}

		$sitepress        = $this->get_sitepress();
		$order_language   = $this->get_order_language( $order );
		$current_language = $this->get_current_language();

		if ( $sitepress && $order_language && $order_language !== $current_language ) {
			$sitepress->switch_lang( $order_language, true );
		} elseif ( $sitepress && ! $order_language ) {
			// Fallback to URL detection
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang && $url_lang !== $current_language ) {
				$sitepress->switch_lang( $url_lang, true );
			}
		}
	}

	/**
	 * Check if permalink is accessible
	 */
	private function check_permalink_accessibility( $url ) {
		$remote = wp_remote_get( add_query_arg( array( 'permalink_check' => 'yes' ), $url ), array( 'sslverify' => false, 'timeout' => 5 ) );

		if ( ! is_wp_error( $remote ) ) {
			$response_code = wp_remote_retrieve_response_code( $remote );
			if ( $response_code !== 404 ) {
				return $response_code;
			}
		}

		return false;
	}

	/**
	 * Get translated page ID
	 * Falls back to default language if translation doesn't exist
	 *
	 * @param int $page_id The page ID to translate.
	 * @param string|null $target_language Target language code.
	 *
	 * @return int Translated page ID or original page ID.
	 */
	public function get_translated_page_id( $page_id, $target_language = null ) {
		if ( ! $page_id ) {
			return $page_id;
		}

		// Check if WPML is active and functions are available
		if ( ! $this->is_wpml_active() ) {
			return $page_id;
		}

		$sitepress = $this->get_sitepress();
		if ( ! $sitepress ) {
			return $page_id;
		}

		// Use target language if provided, otherwise use current language
		$target_lang = $target_language ? $target_language : $this->get_current_language();

		// Check cache first
		$cache_key = $page_id . '_' . $target_lang;
		if ( isset( self::$translation_cache[ $cache_key ] ) ) {
			return self::$translation_cache[ $cache_key ];
		}

		$post_type = XLWCTY_Common::get_thank_you_page_post_type_slug();

		// Try to get translated page ID for target language using WPML filter
		// NEVER use wpml_object_id() directly - always use apply_filters for compatibility
		$translated_id = apply_filters( 'wpml_object_id', $page_id, $post_type, false, $target_lang );

		// If filter didn't work (returned null or same ID), try alternative approach
		if ( ! $translated_id || $translated_id === $page_id ) {
			// Try using SitePress API directly if available
			if ( $sitepress && method_exists( $sitepress, 'get_object_id' ) ) {
				$translated_id = $sitepress->get_object_id( $page_id, $post_type, false, $target_lang );
			}

			// If still no translation, try to find page in target language by checking pages
			if ( ( ! $translated_id || $translated_id === $page_id ) && $sitepress ) {
				// Get thank you pages with reasonable limit
				$all_pages = get_posts( array(
					'post_type'        => $post_type,
					'post_status'      => 'publish',
					'posts_per_page'   => 50,
					'fields'           => 'ids',
					'suppress_filters' => false,
				) );

				foreach ( $all_pages as $check_page_id ) {
					$check_lang = $this->get_post_language( $check_page_id );
					if ( $check_lang === $target_lang ) {
						// Check if this is a translation of the original page
						$original_of_check = apply_filters( 'wpml_object_id', $check_page_id, $post_type, true, $this->get_post_language( $page_id ) );
						if ( $original_of_check == $page_id ) {
							$translated_id = $check_page_id;
							break;
						}
					}
				}
			}
		}

		// Ensure we have a valid ID
		if ( ! $translated_id ) {
			$translated_id = $page_id;
		}

		// If we got a translation, verify it exists and is published
		if ( $translated_id && $translated_id !== $page_id ) {
			$translated_post = get_post( $translated_id );
			if ( $translated_post && $translated_post->post_status === 'publish' ) {
				self::$translation_cache[ $cache_key ] = $translated_id;

				return $translated_id;
			}
		}

		// If no translation found or not published, check if original page is in target language
		$original_language = $this->get_post_language( $page_id );
		if ( $original_language === $target_lang ) {
			// Original page is already in the correct language
			$original_post = get_post( $page_id );
			if ( $original_post && $original_post->post_status === 'publish' ) {
				self::$translation_cache[ $cache_key ] = $page_id;

				return $page_id;
			}
		}

		// Fallback: Try default language
		$default_language = $this->get_default_language();
		if ( $default_language && $default_language !== $target_lang ) {
			$default_translated_id = apply_filters( 'wpml_object_id', $page_id, $post_type, false, $default_language );

			// If filter didn't work, try SitePress API directly
			if ( ! $default_translated_id || $default_translated_id === $page_id ) {
				if ( $sitepress && method_exists( $sitepress, 'get_object_id' ) ) {
					$default_translated_id = $sitepress->get_object_id( $page_id, $post_type, false, $default_language );
				}
			}

			if ( $default_translated_id && $default_translated_id !== $page_id ) {
				$default_post = get_post( $default_translated_id );
				if ( $default_post && $default_post->post_status === 'publish' ) {
					self::$translation_cache[ $cache_key ] = $default_translated_id;

					return $default_translated_id;
				}
			}
		}

		// Final fallback: return original page ID
		self::$translation_cache[ $cache_key ] = $page_id;

		return $page_id;
	}

	/**
	 * Get the language of a post
	 * Made public so it can be called from other classes
	 */
	public function get_post_language( $post_id ) {
		$sitepress = $this->get_sitepress();
		if ( ! $sitepress ) {
			return null;
		}

		// Use WPML filter to get post language
		$post_language = apply_filters( 'wpml_element_language_code', null, array(
			'element_id'   => $post_id,
			'element_type' => XLWCTY_Common::get_thank_you_page_post_type_slug(),
		) );

		// Fallback to SitePress API if filter doesn't work
		if ( ! $post_language && method_exists( $sitepress, 'get_language_for_element' ) ) {
			$post_language = $sitepress->get_language_for_element( $post_id, 'post_' . XLWCTY_Common::get_thank_you_page_post_type_slug() );
		}

		return $post_language ? $post_language : $this->get_default_language();
	}

	/**
	 * Register post type with WPML
	 */
	public function register_post_type_with_wpml() {
		$sitepress = $this->get_sitepress();
		if ( ! $sitepress ) {
			return;
		}

		$post_type     = XLWCTY_Common::get_thank_you_page_post_type_slug();
		$wpml_settings = $sitepress->get_setting( 'custom_posts_sync_option', array() );

		// Only flush rewrite rules when registering post type with WPML for the first time.
		if ( ! isset( $wpml_settings[ $post_type ] ) ) {
			$wpml_settings[ $post_type ] = 1;
			$sitepress->set_setting( 'custom_posts_sync_option', $wpml_settings, true );
			// Flush rewrite rules only when post type is first registered with WPML.
			flush_rewrite_rules( false );
		}
	}
}

// Initialize if WPML is active.
add_action( 'plugins_loaded', function () {
	if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) ) {
		XLWCTY_WPML::get_instance();
	}
}, 1000 );
