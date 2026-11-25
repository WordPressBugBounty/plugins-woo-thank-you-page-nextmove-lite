<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCTY_Polylang
 * Polylang / Polylang for WooCommerce compatibility.
 *
 * @package NextMove
 */
#[AllowDynamicProperties]
class XLWCTY_Polylang {

	private static $ins = null;

	/**
	 * Cache for translated page IDs to avoid repeated lookups.
	 *
	 * @var array
	 */
	private static $translation_cache = array();

	/**
	 * Whether setup_thankyou_post() switched the language and it still has to be restored.
	 *
	 * @var bool
	 */
	private $has_language_before_switch = false;

	/**
	 * Language object that was active before setup_thankyou_post() switched it.
	 *
	 * @var mixed
	 */
	private $language_before_switch = null;

	public function __construct() {
		if ( ! $this->is_polylang_active() ) {
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
	 * Check if Polylang is active.
	 */
	public function is_polylang_active() {
		return function_exists( 'pll_current_language' ) && function_exists( 'PLL' );
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_filter( 'xlwcty_woocommerce_get_checkout_order_received_url', array( $this, 'fix_permalink_for_polylang' ), 10, 2 );
		add_filter( 'xlwcty_before_query', array( $this, 'switch_language_context' ), 10, 1 );
		add_filter( 'xlwcty_permalink_check', array( $this, 'handle_permalink_check' ), 5, 3 );
		add_filter( 'xlwcty_permalink_reset_note', array( $this, 'add_permalink_reset_note' ), 10, 1 );
		add_action( 'xlwcty_before_setup_thankyou_post', array( $this, 'maybe_switch_language_from_order' ), 10, 1 );
		add_action( 'xlwcty_after_query', array( $this, 'restore_language_after_query' ), 10, 0 );
	}

	/**
	 * Get current language code.
	 */
	public function get_current_language() {
		if ( ! function_exists( 'pll_current_language' ) ) {
			return '';
		}

		return pll_current_language( 'slug' ) ?: '';
	}

	/**
	 * Get default language code.
	 */
	public function get_default_language() {
		if ( ! function_exists( 'pll_default_language' ) ) {
			return '';
		}

		return pll_default_language( 'slug' ) ?: '';
	}

	/**
	 * Get language from order.
	 *
	 * @param WC_Order|false $order Order object.
	 */
	public function get_order_language( $order ) {
		if ( ! $order instanceof WC_Order ) {
			$url_lang = $this->detect_language_from_url();
			if ( $url_lang ) {
				return $url_lang;
			}

			return $this->get_current_language();
		}

		$forced_language = apply_filters( 'xlwcty_get_order_language', null, $order );
		if ( $forced_language ) {
			return $forced_language;
		}

		if ( function_exists( 'pll_get_post_language' ) ) {
			$order_language = pll_get_post_language( $order->get_id(), 'slug' );
			if ( $order_language ) {
				return $order_language;
			}
		}

		$url_lang = $this->detect_language_from_url();
		if ( $url_lang ) {
			return $url_lang;
		}

		$customer_id = $order->get_customer_id();
		if ( $customer_id && function_exists( 'get_user_meta' ) ) {
			$customer_language = get_user_meta( $customer_id, 'pll_language', true );
			if ( $customer_language ) {
				return $customer_language;
			}
		}

		$product_language = $this->get_order_product_language( $order );
		if ( $product_language ) {
			return $product_language;
		}

		return $this->get_current_language();
	}

	/**
	 * Detect order language from purchased product translations.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function get_order_product_language( $order ) {
		if ( ! $order instanceof WC_Order || ! function_exists( 'pll_get_post_language' ) ) {
			return '';
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_product' ) ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$candidate_ids = array_filter( array( $product->get_id(), $product->get_parent_id() ) );
			foreach ( $candidate_ids as $candidate_id ) {
				$product_language = pll_get_post_language( $candidate_id, 'slug' );
				if ( $product_language ) {
					return $product_language;
				}
			}
		}

		return '';
	}

	/**
	 * Detect language from URL.
	 */
	public function detect_language_from_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$languages   = function_exists( 'pll_languages_list' ) ? pll_languages_list( array( 'fields' => 'slug' ) ) : array();

		if ( empty( $languages ) ) {
			return false;
		}

		foreach ( $languages as $lang_code ) {
			if ( preg_match( '#^/' . preg_quote( $lang_code, '#' ) . '/(?:|$)#', $request_uri ) ) {
				return $lang_code;
			}
		}

		if ( isset( $_GET['lang'] ) ) {
			$lang_param = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
			if ( in_array( $lang_param, $languages, true ) ) {
				return $lang_param;
			}
		}

		return false;
	}

	/**
	 * Switch language context for queries.
	 *
	 * @param int $order_id Order ID.
	 */
	public function switch_language_context( $order_id ) {
		$target_language = $this->resolve_target_language( $order_id );
		if ( $target_language ) {
			$this->switch_language_for_query( $target_language );
		}
	}

	/**
	 * Maybe switch language from order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_switch_language_from_order( $order_id ) {
		$target_language = $this->resolve_target_language( $order_id );
		if ( $target_language ) {
			$this->switch_language_for_query( $target_language );
		}
	}

	/**
	 * Resolve the language that should be active for thank you page lookup.
	 *
	 * @param int $order_id Order ID.
	 */
	private function resolve_target_language( $order_id ) {
		if ( ! $order_id ) {
			return $this->detect_language_from_url();
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->detect_language_from_url();
		}

		$order_language = $this->get_order_language( $order );
		$current_lang   = $this->get_current_language();

		if ( $order_language && $order_language !== $current_lang ) {
			return $order_language;
		}

		return $this->detect_language_from_url();
	}

	/**
	 * Switch Polylang to a specific language.
	 *
	 * @param string $lang Language slug.
	 */
	public function switch_to_language( $lang ) {
		if ( ! $lang || ! $this->is_polylang_active() || ! PLL() ) {
			return null;
		}

		$language = PLL()->model->get_language( $lang );
		if ( ! $language ) {
			return null;
		}

		$previous        = PLL()->curlang;
		PLL()->curlang = $language;

		return $previous;
	}

	/**
	 * Switch language for the thank you page lookup and remember the language to restore afterwards.
	 *
	 * @param string $lang Language slug.
	 */
	private function switch_language_for_query( $lang ) {
		if ( ! $this->is_polylang_active() || ! PLL() ) {
			return;
		}
		$previous = isset( PLL()->curlang ) ? PLL()->curlang : null;
		$this->switch_to_language( $lang );
		$current = isset( PLL()->curlang ) ? PLL()->curlang : null;
		if ( ! $this->has_language_before_switch && $current !== $previous ) {
			$this->has_language_before_switch = true;
			$this->language_before_switch     = $previous;
		}
	}

	/**
	 * Restore the language that was active before setup_thankyou_post() switched it.
	 */
	public function restore_language_after_query() {
		if ( ! $this->has_language_before_switch ) {
			return;
		}
		$previous                         = $this->language_before_switch;
		$this->has_language_before_switch = false;
		$this->language_before_switch     = null;
		if ( $this->is_polylang_active() && PLL() ) {
			PLL()->curlang = $previous;
		}
	}

	/**
	 * Restore a previously active Polylang language.
	 *
	 * @param mixed $previous Previous language object.
	 */
	public function restore_language( $previous ) {
		if ( $this->is_polylang_active() && PLL() && $previous ) {
			PLL()->curlang = $previous;
		}
	}

	/**
	 * Fix permalink for Polylang.
	 *
	 * @param string   $url   Thank you page URL.
	 * @param WC_Order $order Order object.
	 */
	public function fix_permalink_for_polylang( $url, $order ) {
		if ( empty( $url ) || ! $order instanceof WC_Order ) {
			return $url;
		}

		$page_id = XLWCTY_Core()->data->get_page();
		if ( ! $page_id ) {
			return $url;
		}

		$order_language   = $this->get_order_language( $order );
		$translated_id    = $this->get_translated_page_id( $page_id, $order_language );
		$previous_language = $this->switch_to_language( $order_language );
		$new_url          = get_permalink( $translated_id );
		$this->restore_language( $previous_language );

		if ( $new_url ) {
			$new_url = XLWCTY_Common::prepare_single_post_url( $new_url, $order );
		}

		return $new_url ?: $url;
	}

	/**
	 * Handle permalink check for Polylang.
	 */
	public function handle_permalink_check( $result, $url, $page_id ) {
		if ( empty( $url ) || ! $page_id ) {
			return null;
		}

		$page = get_post( $page_id );
		if ( ! $page || $page->post_status !== 'publish' ) {
			return null;
		}

		$test_permalink = get_permalink( $page_id );
		if ( $test_permalink && ! is_wp_error( $test_permalink ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add Polylang-specific note to permalink reset message.
	 */
	public function add_permalink_reset_note( $note ) {
		return ' ' . __( '(With Polylang, ensure your Thank You Page post type is translatable and linked translations exist for each language)', 'woo-thank-you-page-nextmove-lite' );
	}

	/**
	 * Get translated thank you page ID.
	 *
	 * @param int         $page_id         Page ID.
	 * @param string|null $target_language Target language code.
	 */
	public function get_translated_page_id( $page_id, $target_language = null ) {
		if ( ! $page_id || ! function_exists( 'pll_get_post' ) ) {
			return $page_id;
		}

		$target_lang = $target_language ? $target_language : $this->get_current_language();
		$cache_key   = $page_id . '_' . $target_lang;

		if ( isset( self::$translation_cache[ $cache_key ] ) ) {
			return self::$translation_cache[ $cache_key ];
		}

		$translated_id = pll_get_post( $page_id, $target_lang );
		if ( $translated_id ) {
			$translated_post = get_post( $translated_id );
			if ( $translated_post && $translated_post->post_status === 'publish' ) {
				self::$translation_cache[ $cache_key ] = $translated_id;

				return $translated_id;
			}
		}

		$page_language = $this->get_post_language( $page_id );
		if ( $page_language === $target_lang ) {
			$original_post = get_post( $page_id );
			if ( $original_post && $original_post->post_status === 'publish' ) {
				self::$translation_cache[ $cache_key ] = $page_id;

				return $page_id;
			}
		}

		$default_language = $this->get_default_language();
		if ( $default_language && $default_language !== $target_lang ) {
			$default_id = pll_get_post( $page_id, $default_language );
			if ( $default_id ) {
				$default_post = get_post( $default_id );
				if ( $default_post && $default_post->post_status === 'publish' ) {
					self::$translation_cache[ $cache_key ] = $default_id;

					return $default_id;
				}
			}
		}

		self::$translation_cache[ $cache_key ] = $page_id;

		return $page_id;
	}

	/**
	 * Get the language of a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function get_post_language( $post_id ) {
		if ( ! function_exists( 'pll_get_post_language' ) ) {
			return null;
		}

		$post_language = pll_get_post_language( $post_id, 'slug' );

		return $post_language ? $post_language : $this->get_default_language();
	}
}

add_action(
	'plugins_loaded',
	function () {
		if ( function_exists( 'pll_current_language' ) ) {
			XLWCTY_Polylang::get_instance();
		}
	},
	1000
);
