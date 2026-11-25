<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCTY_Common
 * Handles Common Functions For admins as well as front end interface
 * @package NextMove
 * @author XlPlugins
 */
#[AllowDynamicProperties]
class XLWCTY_Common {

	public static $xlwcty_post;
	public static $xlwcty_query;
	public static $is_front_page = false;
	public static $is_executing_rule = false;
	public static $is_force_debug = false;
	public static $info_generated = false;
	protected static $default;
	public static $custom_ty_pages = null;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_status' ), 5 );

		/** Necessary Hooks For Rules functionality */
		add_action( 'init', array( __CLASS__, 'register_wcthankyou_post_type' ) );
		add_action( 'init', array( __CLASS__, 'load_rules_classes' ) );
		add_filter( 'xlwcty_xlwcty_rule_get_rule_types', array( __CLASS__, 'default_rule_types' ), 1 );
		add_action( 'wp_ajax_xlwcty_change_rule_type', array( __CLASS__, 'ajax_render_rule_choice' ) );
		add_action( 'save_post', array( __CLASS__, 'save_data' ), 10, 2 );
		/**
		 * Checking xlwcty query params
		 */
		/**
		 * Loading XL core
		 */
		add_action( 'plugins_loaded', array( __CLASS__, 'xlwcty_xl_init' ), 99 );
		/**
		 * Containing current Page State using wp hook
		 * using priority 0 to make sure it is not changed by that moment
		 */
		add_action( 'wp', array( __CLASS__, 'xlwcty_contain_current_query' ), 1 );

		// ajax
		add_action( 'wp_ajax_get_product_cmb2', array( __CLASS__, 'get_product_cmb2' ) );
		add_action( 'wp_ajax_nopriv_get_product_cmb2', array( __CLASS__, 'get_product_cmb2' ) );
		add_action( 'wp_ajax_xlwcty_get_orders_cmb2', array( __CLASS__, 'xlwcty_get_orders_cmb2' ) );
		add_action( 'wp_ajax_nopriv_xlwcty_get_orders_cmb2', array( __CLASS__, 'xlwcty_get_orders_cmb2' ) );
		add_action( 'wp_ajax_xlwcty_get_pages_for_order', array( __CLASS__, 'xlwcty_get_pages_for_order' ) );
		add_action( 'wp_ajax_nopriv_xlwcty_get_pages_for_order', array( __CLASS__, 'xlwcty_get_pages_for_order' ) );

		/**
		 * Adding shortcode cb, responsible for front end loading
		 */
		add_shortcode( 'xlwcty_load', array( __CLASS__, 'maybe_render_elements' ) );
		/**
		 *
		 */
		add_action( 'xlwcty_maybe_schedule_check_license', array( __CLASS__, 'check_license_state' ) );
		add_action( 'xlwcty_installed', 'flush_rewrite_rules' );
		add_action( 'xlwcty_loaded', array( __CLASS__, 'setup_global_options' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar_link_to_xlplugins' ), 999 );

		add_action( 'admin_init', array( __CLASS__, 'maybe_duplicate_post' ) );

		add_action( 'wp_ajax_xlwcty_quick_view', array( __CLASS__, 'handle_quick_view' ) );
		add_action( 'xlwcty_options_page_right_content', array( __CLASS__, 'render_quick_view_metabox' ), 9 );
	}

	public static function xlwcty_get_date_format() {
		$date_format = get_option( 'date_format', true );
		$date_format = $date_format ? $date_format : 'M d, Y';

		return $date_format;
	}

	public static function xlwcty_get_time_format() {
		$time_format = get_option( 'time_format', true );
		$time_format = $time_format ? $time_format : 'g:i a';

		return $time_format;
	}

	public static function array_flatten( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		$result = iterator_to_array( new RecursiveIteratorIterator( new RecursiveArrayIterator( $array ) ), false );

		return $result;
	}

	public static function array_flat_mysql_results( $results, $expected_key, $expected_value_key ) {
		$array = array();
		foreach ( $results as $result ) {
			$array[ $result[ $expected_key ] ] = (int) $result[ $expected_value_key ];
		}

		return $array;
	}

	public static function get_date_modified( $mod, $format ) {
		$date_object = new DateTime();
		$date_object->setTimestamp( current_time( 'timestamp' ) );

		return $date_object->modify( $mod )->format( ( $format ) );
	}

	public static function get_current_date( $format ) {
		$date_object = new DateTime();
		$date_object->setTimestamp( current_time( 'timestamp' ) );

		return $date_object->format( $format );
	}

	public static function register_wcthankyou_post_type() {
		$menu_name = XLWCTY_FULL_NAME; // Product name constant, not a translatable literal.
		if ( filter_input( INPUT_POST, 'woocommerce_checkout_order_received_endpoint' ) !== null ) {
			$rewrite_slug = sanitize_title( wp_unslash( filter_input( INPUT_POST, 'woocommerce_checkout_order_received_endpoint' ) ) );
		} else {
			$rewrite_slug = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );
		}

		register_post_type( self::get_thank_you_page_post_type_slug(), apply_filters( 'xlwcty_post_type_args', array(
			'labels'               => array(
				'name'               => __( 'Thank You Page', 'woo-thank-you-page-nextmove-lite' ),
				'singular_name'      => __( 'Thank You Page', 'woo-thank-you-page-nextmove-lite' ),
				'add_new'            => __( 'Add New', 'woo-thank-you-page-nextmove-lite' ),
				'add_new_item'       => __( 'Add New', 'woo-thank-you-page-nextmove-lite' ),
				'edit'               => __( 'Edit', 'woo-thank-you-page-nextmove-lite' ),
				'edit_item'          => __( 'Edit', 'woo-thank-you-page-nextmove-lite' ),
				'new_item'           => __( 'New', 'woo-thank-you-page-nextmove-lite' ),
				'view'               => __( 'View', 'woo-thank-you-page-nextmove-lite' ),
				'view_item'          => __( 'View', 'woo-thank-you-page-nextmove-lite' ),
				'search_items'       => __( 'Search', 'woo-thank-you-page-nextmove-lite' ),
				'not_found'          => __( 'No Thank You Page', 'woo-thank-you-page-nextmove-lite' ),
				'not_found_in_trash' => __( 'No Thank You Page found in trash', 'woo-thank-you-page-nextmove-lite' ),
				'parent'             => __( 'Parent', 'woo-thank-you-page-nextmove-lite' ),
				'menu_name'          => $menu_name,
			),
			'public'               => true,
			'show_ui'              => true,
			'capability_type'      => 'product',
			'map_meta_cap'         => true,
			'publicly_queryable'   => true,
			'exclude_from_search'  => true,
			'show_in_menu'         => false,
			'hierarchical'         => false,
			'show_in_nav_menus'    => false,
			'rewrite'              => array(
				'slug'       => $rewrite_slug,
				'with_front' => false,
			),
			'query_var'            => true,
			'supports'             => array( 'title', 'editor' ),
			'has_archive'          => false,
			'register_meta_box_cb' => array( 'xlwcty_Admin', 'add_metaboxes' ),
		) ) );
	}

	public static function get_thank_you_page_post_type_slug() {
		return 'xlwcty_thankyou';
	}

	public static function load_rules_classes() {
		//Include the compatibility class
		include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/class-xlwcty-compatibility.php';
		//Include our default rule classes
		include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/rules/base.php';
		include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/rules/general.php';
		include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/rules/order.php';
		include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/rules/woofunnels.php';

		if ( is_admin() || defined( 'DOING_AJAX' ) ) {
			//Include the admin interface builder
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/class-xlwcty-input-builder.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/html-always.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/text.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/select.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/product-select.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/chosen-select.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/cart-category-select.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/cart-product-select.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/html-rule-is-renewal.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/html-rule-is-first-order.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/html-rule-is-guest.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/date.php';
			include plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'rules/inputs/time.php';
		}
	}

	/**
	 * Creates an instance of an input object
	 *
	 * @param type $input_type The slug of the input type to load
	 *
	 * @return type An instance of an xlwcty_Input object type
	 * @global type $woocommerce_xlwcty_rule_inputs
	 *
	 */
	public static function woocommerce_xlwcty_rule_get_input_object( $input_type ) {
		global $woocommerce_xlwcty_rule_inputs;
		if ( isset( $woocommerce_xlwcty_rule_inputs[ $input_type ] ) ) {
			return $woocommerce_xlwcty_rule_inputs[ $input_type ];
		}
		$class = 'xlwcty_Input_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $input_type ) ) );
		if ( class_exists( $class ) ) {
			$woocommerce_xlwcty_rule_inputs[ $input_type ] = new $class;
		} else {
			$woocommerce_xlwcty_rule_inputs[ $input_type ] = apply_filters( 'woocommerce_xlwcty_rule_get_input_object', $input_type );
		}

		return $woocommerce_xlwcty_rule_inputs[ $input_type ];
	}

	/**
	 * Ajax and PHP Rendering Functions for Options.
	 *
	 * Renders the correct Operator and Values controls.
	 */
	public static function ajax_render_rule_choice( $options ) {
		// defaults
		$defaults = array(
			'group_id'  => 0,
			'rule_id'   => 0,
			'rule_type' => null,
			'condition' => null,
			'operator'  => null,
		);
		$is_ajax  = false;
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'xlwcty_change_rule_type' ) {
			$is_ajax = true;
		}
		if ( $is_ajax ) {
			if ( ! check_ajax_referer( 'xlwctyaction-admin', 'security' ) || ! current_user_can( 'manage_woocommerce' ) ) {
				die();
			}
			$posted  = wp_unslash( $_POST );
			$options = array_merge( $defaults, array(
				'group_id'  => isset( $posted['group_id'] ) ? sanitize_key( $posted['group_id'] ) : 0,
				'rule_id'   => isset( $posted['rule_id'] ) ? sanitize_key( $posted['rule_id'] ) : 0,
				'rule_type' => isset( $posted['rule_type'] ) ? sanitize_text_field( $posted['rule_type'] ) : null,
				'condition' => isset( $posted['condition'] ) ? $posted['condition'] : null,
				'operator'  => isset( $posted['operator'] ) ? $posted['operator'] : null,
			) );
		} else {
			$options = array_merge( $defaults, $options );
		}
		$options['operator'] = self::normalize_rule_operator( $options['operator'] );
		$rule_object         = self::woocommerce_xlwcty_rule_get_rule_object( $options['rule_type'] );
		if ( ! empty( $rule_object ) ) {
			$values               = $rule_object->get_possibile_rule_values();
			$operators            = $rule_object->get_possibile_rule_operators();
			$condition_input_type = $rule_object->get_condition_input_type();
			// create operators field
			$operator_args = array(
				'input'   => 'select',
				'name'    => 'xlwcty_rule[' . $options['group_id'] . '][' . $options['rule_id'] . '][operator]',
				'choices' => $operators,
			);
			echo '<td class="operator">';
			if ( ! empty( $operators ) ) {
				xlwcty_Input_Builder::create_input_field( $operator_args, $options['operator'] );
			} else {
				echo '<input type="hidden" name="' . xlwcty_Input_Builder::esc_name( $operator_args['name'] ) . '" value="==" />';
			}
			echo '</td>';
			// create values field
			$value_args = array(
				'input'   => $condition_input_type,
				'name'    => 'xlwcty_rule[' . $options['group_id'] . '][' . $options['rule_id'] . '][condition]',
				'choices' => $values,
			);
			echo '<td class="condition">';
			xlwcty_Input_Builder::create_input_field( $value_args, $options['condition'] );
			echo '</td>';
		}
		// ajax?
		if ( $is_ajax ) {
			die();
		}
	}

	/**
	 * Creates an instance of a rule object
	 *
	 * @param type $rule_type The slug of the rule type to load.
	 *
	 * @return xlwcty_Rule_Base or superclass of xlwcty_Rule_Base
	 * @global array $woocommerce_xlwcty_rule_rules
	 *
	 */
	public static function woocommerce_xlwcty_rule_get_rule_object( $rule_type ) {
		global $woocommerce_xlwcty_rule_rules;
		if ( isset( $woocommerce_xlwcty_rule_rules[ $rule_type ] ) ) {
			return $woocommerce_xlwcty_rule_rules[ $rule_type ];
		}
		$class = 'xlwcty_Rule_' . $rule_type;
		if ( class_exists( $class ) ) {
			$woocommerce_xlwcty_rule_rules[ $rule_type ] = new $class;

			return $woocommerce_xlwcty_rule_rules[ $rule_type ];
		} else {
			return null;
		}
	}

	public static function get_coupons( $is_ajax = false ) {
		if ( ! is_admin() || ! isset( $_GET['page'] ) || $_GET['page'] != 'xlwcty_builder' ) {
			return;
		}
		$args               = array(
			'post_type' => 'shop_coupon',
			'showposts' => 3,
			'paged'     => 1,
		);
		$args['meta_query'] = array(
			array(
				'key'     => 'is_xlwcty_coupon',
				'compare' => 'NOT EXISTS',
			),
		);
		$posts              = get_posts( $args );
		$array              = array();
		if ( $is_ajax ) {
			$array[] = array(
				'value' => '',
				'text'  => __( 'Choose a Coupon', 'woo-thank-you-page-nextmove-lite' ),
			);
		}
		if ( $posts && is_array( $posts ) && count( $posts ) > 0 ) {
			foreach ( $posts as $post ) :
				setup_postdata( $post );
				if ( $is_ajax ) {
					$array[] = array(
						'value' => $post->ID,
						'text'  => $post->post_title,
					);
				} else {
					$array[ $post->ID ] = $post->post_title;
				}
			endforeach;
		}

		return $array;
	}

	public static function get_product_cmb2() {
		$nonce = isset( $_POST['cmb2_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cmb2_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'cmb2_nonce' ) ) {
			wp_send_json( array(
				'status'  => 'error',
				'message' => 'Invalid nonce',
			) );
			exit;
		}

		$array = array();
		if ( isset( $_POST['term'] ) && $_POST['term'] !== '' ) {
			$args  = array(
				'post_type'      => 'product',
				'posts_per_page' => 20,
				'paged'          => 1,
				's'              => sanitize_text_field( wp_unslash( $_POST['term'] ) ),
			);
			$posts = get_posts( $args );
			if ( $posts && is_array( $posts ) && count( $posts ) > 0 ) {
				foreach ( $posts as $post ) :
					setup_postdata( $post );
					$array[] = array(
						'value' => (string) $post->ID,
						'text'  => $post->post_title,
					);
				endforeach;
			}
		}
		wp_send_json( $array );
	}

	public static function xlwcty_get_orders_cmb2() {
		$nonce = isset( $_POST['cmb2_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cmb2_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'cmb2_nonce' ) ) {
			wp_send_json( array(
				'status'  => 'error',
				'message' => 'Invalid nonce',
			) );
			exit;
		}

		// Security: Check user capabilities - this endpoint should only be accessible to authenticated admins
		// as it exposes sensitive order data (order IDs, statuses, billing emails)
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json( array(
				'status'  => 'error',
				'message' => __( 'Unauthorized access. This action requires administrator privileges.', 'woo-thank-you-page-nextmove-lite' ),
			) );
			exit;
		}

		$array = array();
		if ( isset( $_POST['term'] ) && $_POST['term'] !== '' ) {
			$order_statuses = XLWCTY_Core()->data->get_option( 'allowed_order_statuses' );
			$order_statuses = is_array( $order_statuses ) ? array_values( array_filter( array_map( 'sanitize_key', $order_statuses ) ) ) : array();
			$term           = sanitize_text_field( wp_unslash( $_POST['term'] ) );

			// Search through the WooCommerce order data store so HPOS and legacy storage both work.
			$order_ids = ( ! empty( $order_statuses ) && function_exists( 'wc_order_search' ) ) ? array_filter( array_map( 'absint', (array) wc_order_search( $term ) ) ) : array();
			$orders    = array();
			if ( ! empty( $order_ids ) ) {
				$orders = wc_get_orders( array(
					'post__in' => array_values( $order_ids ),
					'status'   => $order_statuses,
					'type'     => 'shop_order',
					'limit'    => 10,
					'orderby'  => 'date',
					'order'    => 'DESC',
				) );
			}

			if ( $orders && is_array( $orders ) ) {
				foreach ( $orders as $order ) :
					if ( ! $order instanceof WC_Order ) {
						continue;
					}
					$order_key    = XLWCTY_Compatibility::get_order_data( $order, 'order_key' );
					$order_status = wc_get_order_status_name( $order->get_status() );
					$label        = '#' . XLWCTY_Compatibility::get_order_id( $order ) . ' (' . $order_status . ') ' . XLWCTY_Compatibility::get_order_data( $order, 'billing_email' ) . '';
					$array[]      = array(
						'value' => $order_key . '||' . XLWCTY_Compatibility::get_order_id( $order ),
						'text'  => $label,
					);
				endforeach;
			}
		}
		wp_send_json( $array );
	}

	public static function get_product( $is_ajax = false ) {
		$args  = array(
			'post_type' => 'product',
			'showposts' => 10,
			'paged'     => 1,
		);
		$posts = get_posts( $args );
		$array = array();
		if ( $is_ajax ) {
			$array[] = array(
				'value' => '',
				'text'  => __( 'Choose a Product', 'woo-thank-you-page-nextmove-lite' ),
			);
		}
		if ( $posts && is_array( $posts ) && count( $posts ) > 0 ) {
			foreach ( $posts as $post ) :
				setup_postdata( $post );
				if ( $is_ajax ) {
					$array[] = array(
						'value' => $post->ID,
						'text'  => $post->post_title,
					);
				} else {
					$array[ $post->ID ] = $post->post_title;
				}
			endforeach;
		}

		return $array;
	}

	/**
	 * Called from the metabox_settings.php screen.  Renders the template for a rule group that has already been saved.
	 *
	 * @param array $options The group config options to render the template with.
	 */
	public static function render_rule_choice_template( $options ) {
		// defaults
		$defaults             = array(
			'group_id'  => 0,
			'rule_id'   => 0,
			'rule_type' => null,
			'condition' => null,
			'operator'  => null,
		);
		$options              = array_merge( $defaults, $options );
		$rule_object          = self::woocommerce_xlwcty_rule_get_rule_object( $options['rule_type'] );
		$values               = $rule_object->get_possibile_rule_values();
		$operators            = $rule_object->get_possibile_rule_operators();
		$condition_input_type = $rule_object->get_condition_input_type();
		// create operators field
		$operator_args = array(
			'input'   => 'select',
			'name'    => 'xlwcty_rule[<%= groupId %>][<%= ruleId %>][operator]',
			'choices' => $operators,
		);
		echo '<td class="operator">';
		if ( ! empty( $operators ) ) {
			xlwcty_Input_Builder::create_input_field( $operator_args, $options['operator'] );
		} else {
			echo '<input type="hidden" name="' . xlwcty_Input_Builder::esc_name( $operator_args['name'] ) . '" value="==" />';
		}
		echo '</td>';
		// create values field
		$value_args = array(
			'input'   => $condition_input_type,
			'name'    => 'xlwcty_rule[<%= groupId %>][<%= ruleId %>][condition]',
			'choices' => $values,
		);
		echo '<td class="condition">';
		xlwcty_Input_Builder::create_input_field( $value_args, $options['condition'] );
		echo '</td>';
	}

	public static function get_thank_you_page_status_select() {
		$triggers            = self::get_thank_you_page_statuses();
		$create_select_array = array();
		if ( $triggers && is_array( $triggers ) && count( $triggers ) > 0 ) {
			foreach ( $triggers as $triggerlist ) {
				$create_select_array[ $triggerlist['name'] ] = array();
				foreach ( $triggerlist['triggers'] as $triggers_main ) {
					$create_select_array[ $triggerlist['name'] ][ $triggers_main['slug'] ] = $triggers_main['title'];
				}
			}
		}

		return $create_select_array;
	}

	/**
	 * Getting list of declared triggers in hierarchical order
	 * @return array array of triggers
	 */
	public static function get_thank_you_page_statuses() {
		return array(
			'activated'   => array(
				'name'     => __( 'Activated', 'woo-thank-you-page-nextmove-lite' ),
				'slug'     => 'activated',
				'position' => 5,
			),
			'deactivated' => array(
				'name'     => __( 'Deactivated', 'woo-thank-you-page-nextmove-lite' ),
				'slug'     => 'deactivated',
				'position' => 9,
			),
		);
	}

	public static function match_groups( $content_id, $order_id = 0 ) {
		$display = false;
		if ( $order_id ) {
			$cache_key = 'xlwcty_thankyou_match_groups_' . $content_id . '_' . $order_id;
		} else {
			$cache_key = 'xlwcty_thankyou_match_groups_' . $content_id;
		}
		$results = wp_cache_get( $cache_key, 'xlwcty_thankyou_match' );
		if ( false === $results ) {
			self::$is_executing_rule = true;
			$groups                  = get_post_meta( $content_id, 'xlwcty_rule', true );
			if ( $groups && is_array( $groups ) && count( $groups ) > 0 ) {
				foreach ( $groups as $group_id => $group ) {
					$result = null;
					foreach ( $group as $rule_id => $rule ) {
						$rule_object = self::woocommerce_xlwcty_rule_get_rule_object( $rule['rule_type'] );
						if ( is_object( $rule_object ) ) {
							if ( isset( $rule['operator'] ) ) {
								// Recover rules saved with an HTML-encoded operator ('&lt;' / '&lt;=').
								$rule['operator'] = self::normalize_rule_operator( $rule['operator'] );
							}
							$match = $rule_object->is_match( $rule, $order_id );
							if ( false === $match ) {
								$result = false;
								break;
							}
							$result = ( ( null !== $result ) ? ( $result & $match ) : $match );
						}
					}
					if ( $result ) {
						$display = true;
						break;
					}
				}
			} else {
				$display = true; //Always display the content if no rules have been configured.
			}
			wp_cache_set( $cache_key, ( $display ) ? 'yes' : 'no', 'xlwcty_thankyou_match', 0 );
		} else {
			$display = ( $results == 'yes' ) ? true : false;
		}
		self::$is_executing_rule = false;

		return $display;
	}

	/**
	 * Hooked into xlwcty_get_rule_types to get the default list of rule types.
	 *
	 * @param array $types Current list, if any, of rule types.
	 *
	 * @return array the list of rule types.
	 */
	public static function default_rule_types( $types ) {
		$types = array(
			__( 'General', 'woo-thank-you-page-nextmove-lite' )   => array(
				'general_always' => __( 'Always', 'woo-thank-you-page-nextmove-lite' ),
			),
			__( 'Order', 'woo-thank-you-page-nextmove-lite' )     => array(
				'order_total'           => __( 'Order Total', 'woo-thank-you-page-nextmove-lite' ),
				'order_item'            => __( 'Order Item(s)', 'woo-thank-you-page-nextmove-lite' ),
				'order_category'        => __( 'Order Category(s)', 'woo-thank-you-page-nextmove-lite' ),
				'order_item_count'      => __( 'Order Item Count', 'woo-thank-you-page-nextmove-lite' ),
				'order_item_type'       => __( 'Order Item Type', 'woo-thank-you-page-nextmove-lite' ),
				'order_coupons'         => __( 'Order Coupons', 'woo-thank-you-page-nextmove-lite' ),
				'order_payment_gateway' => __( 'Order Payment Gateway', 'woo-thank-you-page-nextmove-lite' ),
				'order_shipping_method' => __( 'Order Shipping Method', 'woo-thank-you-page-nextmove-lite' ),
				'is_first_order'        => __( 'Is First Order', 'woo-thank-you-page-nextmove-lite' ),
				'order_is_renewal'      => __( 'Is Order Renewal', 'woo-thank-you-page-nextmove-lite' ),
				'order_is_upgrade'      => __( 'Is Order Upgrade', 'woo-thank-you-page-nextmove-lite' ),
				'order_is_downgrade'    => __( 'Is Order Downgrade', 'woo-thank-you-page-nextmove-lite' ),
			),
			__( 'Customer', 'woo-thank-you-page-nextmove-lite' )  => array(
				'customer_user'        => __( 'Customer', 'woo-thank-you-page-nextmove-lite' ),
				'customer_role'        => __( 'Customer User Role', 'woo-thank-you-page-nextmove-lite' ),
				'customer_order_count' => __( 'Customer Order Count', 'woo-thank-you-page-nextmove-lite' ),
				'customer_total_spent' => __( 'Customer Total Spent', 'woo-thank-you-page-nextmove-lite' ),
			),
			__( 'Guest', 'woo-thank-you-page-nextmove-lite' )     => array(
				'is_guest'          => __( 'Is Customer Guest', 'woo-thank-you-page-nextmove-lite' ),
				'guest_order_count' => __( 'Guest Order Count', 'woo-thank-you-page-nextmove-lite' ),
				'guest_total_spent' => __( 'Guest Total Spent', 'woo-thank-you-page-nextmove-lite' ),
			),
			__( 'Geography', 'woo-thank-you-page-nextmove-lite' ) => array(
				'order_shipping_country' => __( 'Order Shipping Country', 'woo-thank-you-page-nextmove-lite' ),
				'order_billing_country'  => __( 'Order Billing Country', 'woo-thank-you-page-nextmove-lite' ),
			),
			__( 'Date/Time', 'woo-thank-you-page-nextmove-lite' ) => array(
				'day'  => __( 'Day', 'woo-thank-you-page-nextmove-lite' ),
				'date' => __( 'Date', 'woo-thank-you-page-nextmove-lite' ),
				'time' => __( 'Time', 'woo-thank-you-page-nextmove-lite' ),
			),
		);

		$woofunnels = array();
		if ( class_exists( 'WFOCU_Common' ) ) {
			$woofunnels['upstroke'] = __( 'Upstroke Funnel', 'woo-thank-you-page-nextmove-lite' );
		}
		if ( class_exists( 'WFACP_Core' ) ) {
			$woofunnels['aerocheckout'] = __( 'AeroCheckout Page', 'woo-thank-you-page-nextmove-lite' );
		}
		if ( ! empty( $woofunnels ) ) {
			$types[ __( 'WooFunnels', 'woo-thank-you-page-nextmove-lite' ) ] = $woofunnels;
		}

		return $types;
	}

	/**
	 * Recursively sanitize rules array structure.
	 *
	 * @param array $rules Rules array to sanitize.
	 *
	 * @return array Sanitized rules array.
	 */
	private static function sanitize_rules_array( $rules ) {
		if ( ! is_array( $rules ) ) {
			return sanitize_text_field( $rules );
		}

		if ( empty( $rules ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $rules as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = is_array( $value ) ? self::sanitize_rules_array( $value ) : sanitize_text_field( wp_unslash( $value ) );
		}

		return $sanitized;
	}

	/**
	 * Decode an operator that was stored HTML-encoded.
	 *
	 * sanitize_text_field() turns a lone '<' into '&lt;', so rules saved by
	 * affected versions hold '&lt;' / '&lt;=' instead of '<' / '<='.
	 *
	 * @param mixed $operator Stored operator.
	 *
	 * @return mixed Decoded operator, or the value unchanged when it is not an encoded comparison operator.
	 */
	public static function normalize_rule_operator( $operator ) {
		if ( ! is_string( $operator ) || false === strpos( $operator, '&' ) ) {
			return $operator;
		}

		$decoded = wp_specialchars_decode( $operator, ENT_QUOTES );

		return in_array( $decoded, array( '<', '<=', '>', '>=' ), true ) ? $decoded : $operator;
	}

	/**
	 * Restore each rule's operator to an allowed operator key of its rule type.
	 *
	 * Operators such as '<' and '<=' must not go through sanitize_text_field(),
	 * so they are whitelisted against the rule object's operators instead.
	 *
	 * @param array $rules Rules already passed through sanitize_rules_array().
	 *
	 * @return array
	 */
	private static function sanitize_rules_operators( $rules ) {
		foreach ( $rules as $group_id => $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			foreach ( $group as $rule_id => $rule ) {
				if ( ! is_array( $rule ) || ! isset( $rule['operator'] ) ) {
					continue;
				}
				$operator    = self::normalize_rule_operator( $rule['operator'] );
				$rule_object = isset( $rule['rule_type'] ) ? self::woocommerce_xlwcty_rule_get_rule_object( $rule['rule_type'] ) : null;
				if ( is_object( $rule_object ) ) {
					$allowed = array_map( 'strval', array_keys( (array) $rule_object->get_possibile_rule_operators() ) );
					if ( empty( $allowed ) ) {
						// Rules without operators submit a hidden '==' field.
						$allowed = array( '==' );
					}
					$operator = in_array( $operator, $allowed, true ) ? $operator : '';
				}

				$rules[ $group_id ][ $rule_id ]['operator'] = $operator;
			}
		}

		return $rules;
	}

	/**
	 * Saves the data for the xlwcty post type.
	 *
	 * @param int $post_id Post ID
	 * @param WP_Post Post Object
	 *
	 */
	public static function save_data( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( is_numeric( wp_is_post_revision( $post ) ) ) {
			return;
		}
		if ( is_numeric( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		if ( $post->post_type != self::get_thank_you_page_post_type_slug() ) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$key = 'xlwcty_instances';
		$compat = self::get_multilingual_compat();
		if ( $compat && method_exists( $compat, 'get_current_language' ) ) {
			$current_lang = $compat->get_current_language();
			if ( $current_lang ) {
				$key .= '_' . $current_lang;
			}
		} elseif ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE !== '' ) {
			$key .= '_' . ICL_LANGUAGE_CODE;
		}
		/** Same key/group the page query caches under (XLWCTY_Data::setup_thankyou_post) */
		if ( class_exists( 'XL_Transient' ) ) {
			XL_Transient::get_instance()->delete_transient( $key, 'nextmove' );
		}

		if ( isset( $_POST['xlwcty_settings_location'] ) ) {
			$location = explode( ':', $_POST['xlwcty_settings_location'] );
			$settings = array(
				'location' => $location[0],
				'hook'     => $location[1],
			);
			if ( $settings['hook'] === 'custom' ) {
				$settings['custom_hook']     = isset( $_POST['xlwcty_settings_location_custom_hook'] ) ? sanitize_text_field( wp_unslash( $_POST['xlwcty_settings_location_custom_hook'] ) ) : '';
				$settings['custom_priority'] = isset( $_POST['xlwcty_settings_location_custom_priority'] ) ? absint( $_POST['xlwcty_settings_location_custom_priority'] ) : 10;
			} else {
				$settings['custom_hook']     = '';
				$settings['custom_priority'] = '';
			}
			$settings['type'] = isset( $_POST['xlwcty_settings_type'] ) ? sanitize_text_field( wp_unslash( $_POST['xlwcty_settings_type'] ) ) : '';
			update_post_meta( $post_id, '_xlwcty_settings', $settings );
		}
		if ( isset( $_POST['xlwcty_rule'] ) ) {
			$rules = $_POST['xlwcty_rule'];
			if ( is_array( $rules ) ) {
				// Recursively sanitize nested array structure.
				$rules = self::sanitize_rules_array( $rules );
				$rules = self::sanitize_rules_operators( $rules );
			} else {
				$rules = sanitize_text_field( $rules );
			}
			update_post_meta( $post_id, 'xlwcty_rule', $rules );
		}
	}

	public static function get_post_table_data( $trigger = 'all' ) {
		if ( $trigger == 'all' ) {
			$args = array(
				'post_type'      => self::get_thank_you_page_post_type_slug(),
				'post_status'    => array( 'publish', XLWCTY_SHORT_SLUG . 'disabled' ),
				'posts_per_page' => 20,
				'paged'          => isset( $_GET['paged'] ) ? $_GET['paged'] : 1,
			);
		} else {
			$meta_q      = array();
			$post_status = '';
			if ( $trigger == 'deactivated' ) {
				$post_status = XLWCTY_SHORT_SLUG . 'disabled';
			}
			$args = array(
				'post_type'      => self::get_thank_you_page_post_type_slug(),
				'post_status'    => array( 'publish', XLWCTY_SHORT_SLUG . 'disabled' ),
				'posts_per_page' => 20,
				'paged'          => isset( $_GET['paged'] ) ? $_GET['paged'] : 1,
			);

			if ( $post_status != '' ) {
				$args['post_status'] = $post_status;
			} else {
				$args['post_status'] = 'publish';
			}
			if ( is_array( $meta_q ) && count( $meta_q ) > 0 ) {
				$args['meta_query'] = $meta_q;
			}
		}

		$q           = new WP_Query( $args );
		$found_posts = array();
		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				$status           = get_post_status( get_the_ID() );
				$row_actions      = array();
				$deactivation_url = wp_nonce_url( add_query_arg( 'page', 'wc-settings', add_query_arg( 'tab', self::get_wc_settings_tab_slug(), add_query_arg( 'action', 'xlwcty-post-deactivate', add_query_arg( 'postid', get_the_ID(), add_query_arg( 'trigger', $trigger ) ), network_admin_url( 'admin.php' ) ) ) ), 'xlwcty-post-deactivate_' . get_the_ID() );

				$row_actions[] = array(
					'action' => 'manage_components',
					'text'   => __( 'Components', 'woo-thank-you-page-nextmove-lite' ),
					'link'   => self::get_builder_link( get_the_ID() ),
					'attrs'  => '',
				);
				$row_actions[] = array(
					'action' => 'edit',
					'text'   => __( 'Edit/ Rules', 'woo-thank-you-page-nextmove-lite' ),
					'link'   => get_edit_post_link( get_the_ID() ),
					'attrs'  => '',
				);

				if ( $status == XLWCTY_SHORT_SLUG . 'disabled' ) {
					$text           = __( 'Activate', 'woo-thank-you-page-nextmove-lite' );
					$link           = get_post_permalink( get_the_ID() );
					$activation_url = wp_nonce_url( add_query_arg( 'page', 'wc-settings', add_query_arg( 'tab', self::get_wc_settings_tab_slug(), add_query_arg( 'action', 'xlwcty-post-activate', add_query_arg( 'postid', get_the_ID(), add_query_arg( 'trigger', $trigger ) ), network_admin_url( 'admin.php' ) ) ) ), 'xlwcty-post-activate_' . get_the_ID() );

					$row_actions[] = array(
						'action' => 'activate',
						'text'   => __( 'Activate', 'woo-thank-you-page-nextmove-lite' ),
						'link'   => $activation_url,
						'attrs'  => '',
					);
				} else {

					$row_actions[] = array(
						'action' => 'deactivate',
						'text'   => __( 'Deactivate', 'woo-thank-you-page-nextmove-lite' ),
						'link'   => $deactivation_url,
						'attrs'  => '',
					);
				}

				$row_actions[] = array(
					'action' => 'xlwcty_duplicate',
					'text'   => __( 'Duplicate', 'woo-thank-you-page-nextmove-lite' ),
					'link'   => wp_nonce_url( add_query_arg( 'page', 'wc-settings', add_query_arg( 'tab', self::get_wc_settings_tab_slug(), add_query_arg( 'action', 'xlwcty-duplicate', add_query_arg( 'postid', get_the_ID(), add_query_arg( 'trigger', $trigger ) ), network_admin_url( 'admin.php' ) ) ) ), 'xlwcty-duplicate_' . get_the_ID() ),
					'attrs'  => '',
				);
				$row_actions[] = array(
					'action' => 'delete',
					'text'   => __( 'Delete Permanently', 'woo-thank-you-page-nextmove-lite' ),
					'link'   => get_delete_post_link( get_the_ID(), '', true ),
					'attrs'  => '',
				);
				array_push( $found_posts, array(
					'id'             => get_the_ID(),
					'trigger_status' => $status,
					'row_actions'    => $row_actions,
				) );
			}
		}
		$found_posts['found_posts'] = $q->found_posts;

		return $found_posts;
	}

	public static function get_wc_settings_tab_slug() {
		return 'xl-thank-you';
	}

	public static function get_builder_link( $id ) {
		return admin_url( 'admin.php' ) . '?page=xlwcty_builder&id=' . $id;
	}

	/*
	 *  register_post_status
	 *
	 *  This function will register custom post statuses
	 *
	 *  @type   function
	 *  @date   22/10/2015
	 *  @since  5.3.2
	 *
	 *  @param  $post_id (int)
	 *  @return $post_id (int)
	 */

	public static function register_post_status() {
		// acf-disabled
		register_post_status( XLWCTY_SHORT_SLUG . 'disabled', array(
			'label'                     => __( 'Disabled', 'woo-thank-you-page-nextmove-lite' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of disabled thank you pages */
			'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'woo-thank-you-page-nextmove-lite' ),
		) );
	}

	public static function get_parent_slug( $slug ) {
		foreach ( self::get_thank_you_page_statuses() as $key => $trigger_list ) {
			if ( isset( $trigger_list['triggers'] ) && is_array( $trigger_list['triggers'] ) && count( $trigger_list['triggers'] ) > 0 ) {
				foreach ( $trigger_list['triggers'] as $trigger ) {
					if ( $trigger['slug'] == $slug ) {
						return $key;
					}
				}
			}
		}
	}

	public static function xlwcty_get_between( $content, $start, $end ) {
		$r = explode( $start, $content );
		if ( isset( $r[1] ) ) {
			$r = explode( $end, $r[1] );

			return $r[0];
		}

		return '';
	}

	public static function xlwcty_xl_init() {
		remove_action( 'xl_loaded', array( 'XL_Common', 'load_text_domain' ), 10 );
		XL_Common::include_xl_core();
	}

	public static function xlwcty_contain_current_query() {
		global $post, $wp_query;
		self::$xlwcty_post  = $post;
		self::$xlwcty_query = $wp_query;
		if ( is_front_page() && is_home() ) {
			self::$is_front_page = true;
		} elseif ( is_front_page() ) {
			self::$is_front_page = true;
		}
	}

	public static function get_timezone_difference() {
		$date_obj_utc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$diff         = timezone_offset_get( timezone_open( self::wc_timezone_string() ), $date_obj_utc );

		return $diff;
	}

	/**
	 * Function to get timezone string by checking WordPress timezone settings
	 * @return mixed|string|void
	 */
	public static function wc_timezone_string() {
		/** WP 5.3+: exact site timezone (or ±HH:MM offset) instead of the approximate offset map below */
		if ( function_exists( 'wp_timezone_string' ) ) {
			return wp_timezone_string();
		}
		// if site timezone string exists, return it
		if ( $timezone = get_option( 'timezone_string' ) ) {
			return $timezone;
		}
		// get UTC offset, if it isn't set then return UTC
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
			return 'UTC';
		}

		// get timezone using offset manual
		return XLWCTY_Common::get_timezone_by_offset( $utc_offset );
	}

	/**
	 * Function to get timezone string based on specified offset
	 *
	 * @param $offset
	 *
	 * @return string
	 * @see XLWCTY_Common::wc_timezone_string()
	 *
	 */
	public static function get_timezone_by_offset( $offset ) {
		switch ( $offset ) {
			case '-12':
				return 'GMT-12';
				break;
			case '-11.5':
				return 'Pacific/Niue'; // 30 mins wrong
				break;
			case '-11':
				return 'Pacific/Niue';
				break;
			case '-10.5':
				return 'Pacific/Honolulu'; // 30 mins wrong
				break;
			case '-10':
				return 'Pacific/Tahiti';
				break;
			case '-9.5':
				return 'Pacific/Marquesas';
				break;
			case '-9':
				return 'Pacific/Gambier';
				break;
			case '-8.5':
				return 'Pacific/Pitcairn'; // 30 mins wrong
				break;
			case '-8':
				return 'Pacific/Pitcairn';
				break;
			case '-7.5':
				return 'America/Hermosillo'; // 30 mins wrong
				break;
			case '-7':
				return 'America/Hermosillo';
				break;
			case '-6.5':
				return 'America/Belize'; // 30 mins wrong
				break;
			case '-6':
				return 'America/Belize';
				break;
			case '-5.5':
				return 'America/Belize'; // 30 mins wrong
				break;
			case '-5':
				return 'America/Panama';
				break;
			case '-4.5':
				return 'America/Lower_Princes'; // 30 mins wrong
				break;
			case '-4':
				return 'America/Curacao';
				break;
			case '-3.5':
				return 'America/Paramaribo'; // 30 mins wrong
				break;
			case '-3':
				return 'America/Recife';
				break;
			case '-2.5':
				return 'America/St_Johns';
				break;
			case '-2':
				return 'America/Noronha';
				break;
			case '-1.5':
				return 'Atlantic/Cape_Verde'; // 30 mins wrong
				break;
			case '-1':
				return 'Atlantic/Cape_Verde';
				break;
			case '+1':
				return 'Africa/Luanda';
				break;
			case '+1.5':
				return 'Africa/Mbabane'; // 30 mins wrong
				break;
			case '+2':
				return 'Africa/Harare';
				break;
			case '+2.5':
				return 'Indian/Comoro'; // 30 mins wrong
				break;
			case '+3':
				return 'Asia/Baghdad';
				break;
			case '+3.5':
				return 'Indian/Mauritius'; // 30 mins wrong
				break;
			case '+4':
				return 'Indian/Mauritius';
				break;
			case '+4.5':
				return 'Asia/Kabul';
				break;
			case '+5':
				return 'Indian/Maldives';
				break;
			case '+5.5':
				return 'Asia/Kolkata';
				break;
			case '+5.75':
				return 'Asia/Kathmandu';
				break;
			case '+6':
				return 'Asia/Urumqi';
				break;
			case '+6.5':
				return 'Asia/Yangon';
				break;
			case '+7':
				return 'Antarctica/Davis';
				break;
			case '+7.5':
				return 'Asia/Jakarta'; // 30 mins wrong
				break;
			case '+8':
				return 'Asia/Manila';
				break;
			case '+8.5':
				return 'Asia/Pyongyang';
				break;
			case '+8.75':
				return 'Australia/Eucla';
				break;
			case '+9':
				return 'Asia/Tokyo';
				break;
			case '+9.5':
				return 'Australia/Darwin';
				break;
			case '+10':
				return 'Australia/Brisbane';
				break;
			case '+10.5':
				return 'Australia/Lord_Howe';
				break;
			case '+11':
				return 'Antarctica/Casey';
				break;
			case '+11.5':
				return 'Pacific/Auckland'; // 30 mins wrong
				break;
			case '+12':
				return 'Pacific/Wallis';
				break;
			case '+12.75':
				return 'Pacific/Chatham';
				break;
			case '+13':
				return 'Pacific/Fakaofo';
				break;
			case '+13.75':
				return 'Pacific/Chatham'; // 1 hr wrong
				break;
			case '+14':
				return 'Pacific/Kiritimati';
				break;
			default:
				return 'UTC';
				break;
		}
	}

	public static function get_loop_count( $start_date_timestamp, $todayDate, $total_gap ) {
		$incre = 0;
		if ( $total_gap > 0 ) {
			$incre = ( ( $todayDate - $start_date_timestamp ) / ( $total_gap * 3600 ) );
			$incre = ceil( $incre ) + 1;
		}

		return (int) $incre;
	}

	public static function array_recursive( $aArray1, $aArray2 ) {
		$aReturn = array();
		if ( $aArray1 && count( $aArray1 ) > 0 ) {
			foreach ( $aArray1 as $mKey => $mValue ) {
				if ( array_key_exists( $mKey, $aArray2 ) ) {
					if ( is_array( $mValue ) ) {
						$aRecursiveDiff = self::array_recursive( $mValue, $aArray2[ $mKey ] );
						if ( count( $aRecursiveDiff ) ) {
							$aReturn[ $mKey ] = $aRecursiveDiff;
						}
					} else {
						if ( $mValue != $aArray2[ $mKey ] ) {
							$aReturn[ $mKey ] = $mValue;
						}
					}
				} else {
					$aReturn[ $mKey ] = $mValue;
				}
			}
		}

		return $aReturn;
	}

	public static function is_load_admin_assets( $cur_screen = 'single' ) {
		/** return if function not exists, sometimes throw fatal error */
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen       = get_current_screen();
		$wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

		if ( $cur_screen == 'all' ) {
			if ( is_object( $screen ) && ( $screen->base == 'post' && $screen->post_type == XLWCTY_Common::get_thank_you_page_post_type_slug() ) ) {
				return true;
			}
			if ( is_object( $screen ) && ( $screen->base == $wc_screen_id . '_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == self::get_wc_settings_tab_slug() ) ) {
				return true;
			}
			if ( filter_input( INPUT_GET, 'page' ) == 'xlwcty_builder' && filter_input( INPUT_GET, 'id' ) !== '' ) {
				return true;
			}
			if ( filter_input( INPUT_GET, 'page' ) == 'xlwcty_settings_admin_menu' ) {
				return true;
			}
		} elseif ( $cur_screen == 'single' ) {
			if ( is_object( $screen ) && ( $screen->base == 'post' && $screen->post_type == XLWCTY_Common::get_thank_you_page_post_type_slug() ) ) {
				return true;
			}

			if ( is_object( $screen ) && ( $screen->base == "toplevel_page_xlwcty_builder" ) ) {
				return true;
			}
		} elseif ( $cur_screen == 'listing' ) {
			if ( is_object( $screen ) && ( $screen->base == $wc_screen_id . '_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == self::get_wc_settings_tab_slug() ) ) {
				return true;
			}
		} elseif ( $cur_screen == 'builder' ) {
			if ( filter_input( INPUT_GET, 'page' ) == 'xlwcty_builder' && filter_input( INPUT_GET, 'id' ) !== '' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $item_id
	 *
	 * @return array
	 */
	public static function get_item_data( $item_id ) {
		global $wpdb;

		$xl_cache_obj     = XL_Cache::get_instance();
		$xl_transient_obj = XL_Transient::get_instance();

		$parseObj   = array();
		$meta_query = apply_filters( 'xlwcty_product_meta_query', $wpdb->prepare( "SELECT meta_key,meta_value  FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s", $item_id, '%_xlwcty_%' ) );
		$cache_key  = 'xlwcty_thankyou_meta_' . $item_id;

		/**
		 * Setting xl cache and transient for NextMove page meta
		 */
		$cache_data = $xl_cache_obj->get_cache( $cache_key, 'nextmove' );
		if ( false !== $cache_data ) {
			$parseObj = $cache_data;
		} else {
			$transient_data = $xl_transient_obj->get_transient( $cache_key, 'nextmove' );
			if ( false !== $transient_data ) {
				$parseObj = $transient_data;
			} else {
				$product_meta                    = $wpdb->get_results( $meta_query, ARRAY_A );
				$product_meta                    = self::get_parsed_query_results_meta( $product_meta );
				$get_product_xlwcty_meta_default = self::parse_default_args_by_trigger( $product_meta );
				$parseObj                        = wp_parse_args( $product_meta, $get_product_xlwcty_meta_default );
				$xl_transient_obj->set_transient( $cache_key, $parseObj, 21600, 'nextmove' );
			}
			$xl_cache_obj->set_cache( $cache_key, $parseObj, 'nextmove' );
		}

		$fields = array();
		if ( $parseObj && is_array( $parseObj ) && count( $parseObj ) > 0 ) {
			foreach ( $parseObj as $key => $val ) {
				$newKey = $key;
				if ( strpos( $key, '_xlwcty_' ) !== false ) {
					$newKey = str_replace( '_xlwcty_', '', $key );
				}
				$fields[ $newKey ] = $val;
			}
		}

		return $fields;
	}

	public static function get_parsed_query_results_meta( $results ) {
		$parsed_results = array();
		if ( is_array( $results ) && count( $results ) > 0 ) {
			foreach ( $results as $key => $result ) {
				if ( is_array( $result ) && isset( $result['meta_key'] ) ) {
					$parsed_results[ $result['meta_key'] ] = $result['meta_value'];
				} else {
					$parsed_results[ $key ] = $result;
				}
			}
		}

		return $parsed_results;
	}

	public static function parse_default_args_by_trigger( $data ) {
		$field_option_data = self::get_default_settings();
		foreach ( $field_option_data as $slug => $value ) {
			if ( strpos( $slug, '_xlwcty_' ) !== false ) {
				$data[ $slug ] = $value;
			}
		}

		return $data;
	}

	public static function get_default_settings() {
		self::$default = array();

		return self::$default;
	}

	public static function xlwcty_get_timestamp_wc_native( $dt ) {
		$timezone      = self::wc_timezone_string();
		$date          = new DateTime( $dt, new DateTimeZone( $timezone ) );
		$ret_timestamp = $date->getTimestamp();

		return $ret_timestamp;
	}

	public static function maybe_render_elements() {
		if ( is_admin() ) {
			return __return_empty_string();
		}

		return XLWCTY_Core()->public->maybe_render_elements();
	}

	public static function maype_parse_merge_tags( $content = '', $obj = false ) {
		if ( empty( $content ) ) {
			return;
		}
		$content = XLWCTY_ShortCode_Merge_Tags::maybe_parse_merge_tags( $content );
		$content = XLWCTY_Dynamic_Merge_Tags::maybe_parse_merge_tags( $content );
		$content = XLWCTY_Static_Merge_Tags::maybe_parse_merge_tags( $content );
		$content = apply_filters( 'xlwcty_decode_coupon_merge_tags', $content );
		$content = apply_filters( 'xlwcty_parse_shortcode', $content );

		return $content;
	}

	public static function check_license_state() {
		$license = new XLWCTY_EDD_License( XLWCTY_PLUGIN_FILE, XLWCTY_FULL_NAME, XLWCTY_VERSION, 'xlplugins', null, apply_filters( 'xlwcty_edd_api_url', 'https://xlplugins.com/' ) );
		$license->weekly_license_check();
	}

	public static function get_options_defaults() {
		return array(
			'xlwcty_preview_mode'     => 'live',
			'google_map_api'          => '',
			// Translate only once translations may load (defaults are also read on plugins_loaded, before init).
			'google_map_error_txt'    => did_action( 'init' ) ? __( 'Map unable to load, something wrong with address.', 'woo-thank-you-page-nextmove-lite' ) : 'Map unable to load, something wrong with address.',
			'allowed_order_statuses'  => array( 'wc-processing', 'wc-pending', 'wc-on-hold', 'wc-completed' ),
			'wrap_left_right_padding' => '0',
		);
	}

	public static function get_builder_layouts() {
		return array(
			array(
				'name'    => __( 'One Column', 'woo-thank-you-page-nextmove-lite' ),
				'slug'    => 'basic',
				'preview' => plugin_dir_url( XLWCTY_PLUGIN_FILE ) . 'admin/assets/img/layout-1.jpg',
			),
			array(
				'name'    => __( 'Two Column', 'woo-thank-you-page-nextmove-lite' ),
				'slug'    => 'two_column',
				'preview' => plugin_dir_url( XLWCTY_PLUGIN_FILE ) . 'admin/assets/img/layout-2.jpg',
			),
		);
	}

	public static function setup_global_options() {
		if ( is_null( XLWCTY_Core()->data ) || ! XLWCTY_Core()->data instanceof XLWCTY_Data ) {
			return;
		}
		XLWCTY_Core()->data->setup_options();
	}

	public static function toolbar_link_to_xlplugins( $wp_admin_bar ) {
		if ( is_admin() ) {
			return;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$args = array(
			'id'    => 'xlwcty_admin_page_node',
			'title' => __( 'XL NextMove', 'woo-thank-you-page-nextmove-lite' ),
			'href'  => admin_url( 'admin.php?page=wc-settings&tab=' . XLWCTY_Common::get_wc_settings_tab_slug() ),
			'meta'  => array(
				'class' => 'xlwcty_admin_page_node',
			),
		);
		$wp_admin_bar->add_node( $args );
	}

	public static function get_formatted_date_from_date( $date, $format = 'Y-m-d' ) {
		if ( ! $date instanceof DateTime ) {
			if ( is_numeric( $date ) ) {
				$time = $date;
				$date = new DateTime();
				$date->setTimestamp( $time );
			} else {
				$date = new DateTime( $date );
			}
		}

		return date_i18n( $format, $date->getTimestamp() );
	}

	/**
	 * Convert a hexa decimal color code to its RGB equivalent
	 *
	 * @param string $hexStr (hexadecimal color value)
	 * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise returns associative array)
	 * @param string $seperator (to separate RGB values. Applicable only if second parameter is true.)
	 *
	 * @return array or string (depending on second parameter. Returns False if invalid hex color value)
	 */
	public static function hex2rgb( $hexStr, $returnAsString = false, $seperator = ',' ) {
		$hexStr   = preg_replace( '/[^0-9A-Fa-f]/', '', $hexStr );
		$rgbArray = array();
		if ( strlen( $hexStr ) == 6 ) {
			$colorVal          = hexdec( $hexStr );
			$rgbArray['red']   = 0xFF & ( $colorVal >> 0x10 );
			$rgbArray['green'] = 0xFF & ( $colorVal >> 0x8 );
			$rgbArray['blue']  = 0xFF & $colorVal;
		} elseif ( strlen( $hexStr ) == 3 ) {
			$rgbArray['red']   = hexdec( str_repeat( substr( $hexStr, 0, 1 ), 2 ) );
			$rgbArray['green'] = hexdec( str_repeat( substr( $hexStr, 1, 1 ), 2 ) );
			$rgbArray['blue']  = hexdec( str_repeat( substr( $hexStr, 2, 1 ), 2 ) );
		} else {
			return false;
		}

		return $returnAsString ? implode( $seperator, $rgbArray ) : $rgbArray; // returns the rgb string or the associative array
	}

	public static function xlwcty_get_pages_for_order() {

		if ( ! wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ), filter_input( INPUT_POST, 'action' ) ) ) {
			wp_send_json( array(
				'result'     => 'error',
				'error_text' => __( 'Unauthorized access. ', 'woo-thank-you-page-nextmove-lite' ),
			) );
		}

		// Read the order once so both the admin and the order-key paths use it.
		$order_id = isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0;

		// Security: Check user capabilities or validate order key for unauthenticated users
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			// For unauthenticated users, require order key validation
			$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';

			if ( empty( $order_id ) || empty( $order_key ) ) {
				wp_send_json( array(
					'result'     => 'error',
					'error_text' => __( 'Order key required for unauthenticated requests.', 'woo-thank-you-page-nextmove-lite' ),
				) );
			}

			// Verify order key matches the order
			$order = wc_get_order( $order_id );
			if ( ! $order || ! hash_equals( (string) XLWCTY_Compatibility::get_order_data( $order, 'order_key' ), (string) $order_key ) ) {
				wp_send_json( array(
					'result'     => 'error',
					'error_text' => __( 'Invalid order key.', 'woo-thank-you-page-nextmove-lite' ),
				) );
			}
		}

		if ( filter_input( INPUT_POST, 'order' ) !== null && filter_input( INPUT_POST, 'order' ) !== '' ) {

			// Security: For nopriv requests, verify order key or capability
			if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
				if ( empty( $order_key ) ) {
					wp_send_json( array(
						'result'     => 'error',
						'error_text' => __( 'Order key required for unauthenticated requests.', 'woo-thank-you-page-nextmove-lite' ),
					) );
					exit;
				}
				// Verify order key matches the order
				$order = wc_get_order( $order_id );
				if ( ! $order || ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
					wp_send_json( array(
						'result'     => 'error',
						'error_text' => __( 'Invalid order key.', 'woo-thank-you-page-nextmove-lite' ),
					) );
					exit;
				}
			}

			XLWCTY_Core()->data->setup_thankyou_post( $order_id );
			XLWCTY_Core()->data->load_order( $order_id );
			$page      = XLWCTY_Core()->data->get_page();
			$page_link = XLWCTY_Core()->data->get_page_link();
			if ( is_numeric( $page ) ) {

				$page = get_post( $page );

				$tem = get_post_meta( $page->ID, '_wp_page_template', true );
				XLWCTY_Core()->data->load_thankyou_metadata();
				$page->xlwcty_template = $tem;

				$get_layout = XLWCTY_Core()->data->get_layout();

				$allowed_layouts = wp_list_pluck( self::get_builder_layouts(), 'slug' );
				$get_layout      = in_array( $get_layout, $allowed_layouts, true ) ? $get_layout : '';

				$file_data                   = ( '' !== $get_layout ) ? get_file_data( plugin_dir_path( XLWCTY_PLUGIN_FILE ) . 'templates/' . $get_layout . '.php', array( 'XLWCTY Template Name' ) ) : array();
				$page->xlwcty_layout         = ( ! empty( $file_data ) ) ? $file_data[0] : '';
				$page->xlwcty_componets_html = '';
				ob_start();
				try {
					XLWCTY_Core()->public->include_template();
				} catch ( Exception $ex ) {
					echo '';
				}
				$contain = ob_get_clean();
				unset( $contain );
				$page->xlwcty_componets_html = '<strong>Page Layout</strong>: ' . $page->xlwcty_layout . '</li><li><strong>Page Components</strong>:';
				$page->xlwcty_componets_html .= '<ul>';
				unset( XLWCTY_Core()->public->header_info[0] );
				foreach ( XLWCTY_Core()->public->header_info as $header_logs ) {
					if ( strpos( $header_logs, 'Template:' ) === false ) {
						$page->xlwcty_componets_html .= '<li>' . esc_html( $header_logs ) . '</li>';
					}
				}
				$page->xlwcty_componets_html .= '</ul>';
				// Security fix: Use validated $order_id instead of $_POST['order']
				$order_obj = wc_get_order( $order_id );
				if ( $order_obj ) {
					$page->public_link = self::prepare_single_post_url( $page_link, $order_obj );
				} else {
					$page->public_link = $page_link;
				}
				wp_send_json( array(
					'result' => 'success',
					'page'   => get_post( $page ),
				) );
			}
		}

		wp_send_json( array(
			'result'     => 'error',
			'error_text' => __( 'No Thank You Page found. Create one or check rules for the existing page to see if Rules set for the page match selected order.', 'woo-thank-you-page-nextmove-lite' ),
		) );
	}

	/**
	 * Prepares single post url and add query arg to that woocommerce pick that url
	 *
	 * @param $link
	 * @param $order
	 *
	 * @return mixed|void
	 */
	public static function prepare_single_post_url( $link, $order ) {
		$link = add_query_arg( 'key', XLWCTY_Compatibility::get_order_data( $order, 'order_key' ), $link );
		$link = add_query_arg( 'order_id', XLWCTY_Compatibility::get_order_id( $order ), $link );

		return apply_filters( 'xlwcty_woocommerce_get_checkout_order_received_url', $link, $order );
	}

	/**
	 * Build the NextMove thank you redirect URL for an order.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string|false
	 */
	public static function get_thankyou_redirect_url_for_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$default_settings = XLWCTY_Core()->data->get_option();
		if ( isset( $default_settings['xlwcty_preview_mode'] ) && 'sandbox' === $default_settings['xlwcty_preview_mode'] ) {
			return false;
		}

		$multilingual_compat = self::get_multilingual_compat();
		$previous_language   = null;

		if ( $multilingual_compat && method_exists( $multilingual_compat, 'get_order_language' ) && method_exists( $multilingual_compat, 'switch_to_language' ) ) {
			$order_lang = $multilingual_compat->get_order_language( $order );
			if ( $order_lang ) {
				$previous_language = $multilingual_compat->switch_to_language( $order_lang );
			}
		}

		$get_link = XLWCTY_Core()->data->setup_thankyou_post( XLWCTY_Compatibility::get_order_id( $order ), false )->get_page_link();

		if ( $multilingual_compat && method_exists( $multilingual_compat, 'restore_language' ) ) {
			$multilingual_compat->restore_language( $previous_language );
		}

		if ( false === $get_link || empty( $get_link ) ) {
			return false;
		}

		$get_link = trim( $get_link );
		$get_link = wp_specialchars_decode( $get_link );

		return self::prepare_single_post_url( $get_link, $order );
	}

	/**
	 * filters and returned valid order statuses for order queries
	 * @return mixed|void
	 */
	public static function get_order_statuses() {
		return apply_filters( 'xlwcty_get_order_statuses', array( 'wc-processing', 'wc-on-hold', 'wc-completed' ) );
	}

	public static function maybe_duplicate_post() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'xlwcty-duplicate' ) {

			$original_id = isset( $_GET['postid'] ) ? absint( $_GET['postid'] ) : 0;
			$nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			if ( $original_id && wp_verify_nonce( $nonce, 'xlwcty-duplicate_' . $original_id ) ) {

				$section = isset( $_GET['trigger'] ) ? sanitize_key( wp_unslash( $_GET['trigger'] ) ) : '';

				// Only NextMove thank-you pages may be duplicated, and only by users who can edit them.
				$original_post = get_post( $original_id );
				if ( ! $original_post instanceof WP_Post || self::get_thank_you_page_post_type_slug() !== $original_post->post_type || ! current_user_can( 'edit_post', $original_id ) ) {
					wp_die( esc_html__( 'Unable to Duplicate', 'woo-thank-you-page-nextmove-lite' ), '', array( 'response' => 403 ) );
				}

				if ( $original_id ) {

					// Get the post as an array
					$duplicate = get_post( $original_id, 'ARRAY_A' );

					// A duplicated page always starts deactivated, so it does not go live before it is edited.
					$settings = $defaults = array(
						'status'                => XLWCTY_SHORT_SLUG . 'disabled',
						'type'                  => 'same',
						'timestamp'             => 'current',
						'title'                 => __( 'Copy', 'woo-thank-you-page-nextmove-lite' ),
						'slug'                  => 'copy',
						'time_offset'           => false,
						'time_offset_days'      => 0,
						'time_offset_hours'     => 0,
						'time_offset_minutes'   => 0,
						'time_offset_seconds'   => 0,
						'time_offset_direction' => 'newer',
					);

					// Modify some of the elements
					$appended                = ( $settings['title'] != '' ) ? ' ' . $settings['title'] : '';
					$duplicate['post_title'] = $duplicate['post_title'] . ' ' . $appended;
					$duplicate['post_name']  = sanitize_title( $duplicate['post_name'] . '-' . $settings['slug'] );

					// Set the status
					if ( $settings['status'] != 'same' ) {
						$duplicate['post_status'] = $settings['status'];
					}

					// Set the type
					if ( $settings['type'] != 'same' ) {
						$duplicate['post_type'] = $settings['type'];
					}

					// Set the post date
					$timestamp     = ( $settings['timestamp'] == 'duplicate' ) ? strtotime( $duplicate['post_date'] ) : current_time( 'timestamp', 0 );
					$timestamp_gmt = ( $settings['timestamp'] == 'duplicate' ) ? strtotime( $duplicate['post_date_gmt'] ) : current_time( 'timestamp', 1 );

					if ( $settings['time_offset'] ) {
						$offset = intval( $settings['time_offset_seconds'] + $settings['time_offset_minutes'] * 60 + $settings['time_offset_hours'] * 3600 + $settings['time_offset_days'] * 86400 );
						if ( $settings['time_offset_direction'] == 'newer' ) {
							$timestamp     = intval( $timestamp + $offset );
							$timestamp_gmt = intval( $timestamp_gmt + $offset );
						} else {
							$timestamp     = intval( $timestamp - $offset );
							$timestamp_gmt = intval( $timestamp_gmt - $offset );
						}
					}
					$duplicate['post_date']         = gmdate( 'Y-m-d H:i:s', $timestamp );
					$duplicate['post_date_gmt']     = gmdate( 'Y-m-d H:i:s', $timestamp_gmt );
					$duplicate['post_modified']     = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) );
					$duplicate['post_modified_gmt'] = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) );

					// The copy belongs to the user who made it.
					$duplicate['post_author'] = get_current_user_id();

					// Remove some of the keys
					unset( $duplicate['ID'] );
					unset( $duplicate['guid'] );
					unset( $duplicate['comment_count'] );

					// Insert the post into the database (wp_insert_post() expects slashed data).
					$duplicate_id = wp_insert_post( wp_slash( $duplicate ) );
					if ( empty( $duplicate_id ) || is_wp_error( $duplicate_id ) ) {
						wp_die( esc_html__( 'Unable to Duplicate', 'woo-thank-you-page-nextmove-lite' ) );
					}

					// Duplicate all the taxonomies/terms
					$taxonomies = get_object_taxonomies( $duplicate['post_type'] );
					foreach ( $taxonomies as $taxonomy ) {
						$terms = wp_get_post_terms( $original_id, $taxonomy, array(
							'fields' => 'names',
						) );
						wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
					}

					// Duplicate all the custom fields
					$custom_fields = get_post_custom( $original_id );
					foreach ( $custom_fields as $key => $value ) {
						// Skip WordPress edit-lock data so the copy does not show as locked by another user.
						if ( in_array( $key, array( '_edit_lock', '_edit_last' ), true ) ) {
							continue;
						}
						if ( is_array( $value ) && count( $value ) > 0 ) {
							foreach ( $value as $v ) {
								add_post_meta( $duplicate_id, wp_slash( $key ), wp_slash( maybe_unserialize( $v ) ) );
							}
						}
					}
					do_action( 'xlwcty_post_duplicated', $original_id, $duplicate_id, $settings );

					wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=' . XLWCTY_Common::get_wc_settings_tab_slug() . '&section=' . $section ) );
					exit;
				}
			} else {
				wp_die( esc_html__( 'Unable to Duplicate', 'woo-thank-you-page-nextmove-lite' ) );
			}
		}
	}

	/**
	 * Handle Quick View logic for NextMove Lite.
	 *
	 * This function performs the following:
	 * - Checks for available Thank You pages.
	 * - Verifies permalink state.
	 * - Queries recent WooCommerce orders based on allowed statuses.
	 * - Prepares a response with status indicators and possible solutions.
	 */
	public static function handle_quick_view() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'woo-thank-you-page-nextmove-lite' ) ) );

			return;
		}

		// Verify nonce for CSRF protection.
		if ( ! isset( $_POST['security'] ) || ! check_ajax_referer( 'xlwctyaction-admin', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'woo-thank-you-page-nextmove-lite' ) ) );

			return;
		}

		// Initialize states
		$permalink_state         = false;
		$available_thankyou_page = false;
		$recent_order_state      = false;

		// Get the current mode (sandbox or live)
		$mode = XLWCTY_Core()->data->get_option( 'xlwcty_preview_mode' );

		/**
		 * Step 1: Check if a Thank You page exists.
		 * - Query published Thank You pages ordered by the menu order.
		 */
		$args = array(
			'post_type'   => XLWCTY_Common::get_thank_you_page_post_type_slug(),
			'post_status' => 'publish',
			'nopaging'    => true,
			'meta_key'    => '_xlwcty_menu_order',
			'orderby'     => 'meta_value_num',
			'order'       => 'ASC',
			'fields'      => 'ids',
			'showposts'   => 1,
		);

		$get_posts_all = get_posts( $args );
		if ( ! empty( $get_posts_all ) ) {
			$available_thankyou_page = true;
			$get_link                = get_permalink( $get_posts_all[0] );
		}

		/**
		 * Step 2: Verify permalink state.
		 * - Check the accessibility of the Thank You page.
		 */
		$get_posts_check = get_posts( array(
			'post_type'   => XLWCTY_Common::get_thank_you_page_post_type_slug(),
			'fields'      => 'ids',
			'post_status' => array( 'publish', XLWCTY_SHORT_SLUG . 'disabled' ),
			'showposts'   => 1,
		) );

		if ( ! empty( $get_posts_check ) ) {
			$get_page_id    = $get_posts_check[0];
			$get_link_check = get_permalink( $get_page_id );
			$get_link_check = self::parse_url_for_ssl( $get_link_check );

			// The loopback request is slow, so reuse a recent result (failures are kept only briefly so a permalink reset shows up quickly).
			$transient_key = 'xlwcty_permalink_check_' . md5( (string) $get_link_check );
			$cached_state  = get_transient( $transient_key );

			if ( false !== $cached_state ) {
				$permalink_state = ( 'yes' === $cached_state );
			} else {
				$remote = wp_remote_get( add_query_arg( array( 'permalink_check' => 'yes' ), $get_link_check ), array(
					'timeout' => 5,
				) );

				$response_code = wp_remote_retrieve_response_code( $remote );

				// If no errors and the response code is valid, set permalink state
				if ( is_wp_error( $remote ) ) {
					// For WPML sites, permalink check might fail due to language requirements
					// Check if permalink can be generated instead
					$test_permalink = get_permalink( $get_page_id );
					if ( $test_permalink && ! is_wp_error( $test_permalink ) ) {
						$permalink_state = true; // Assume state is OK if permalink can be generated
					}
				} elseif ( $response_code !== 404 ) {
					$permalink_state = true;
				}

				$matches      = array();
				$api_response = preg_match( '/{"status.*}/', wp_remote_retrieve_body( $remote ), $matches );
				$api_response = current( $matches );
				$api_response = json_decode( $api_response, true );
				if ( is_array( $api_response ) && isset( $api_response['status'] ) && $api_response['status'] === 'success' ) {
					$permalink_state = true;
				}

				set_transient( $transient_key, $permalink_state ? 'yes' : 'no', $permalink_state ? HOUR_IN_SECONDS : MINUTE_IN_SECONDS );
			}
		}

		/**
		 * Step 3: Query recent WooCommerce orders based on allowed statuses.
		 * - Retrieve allowed statuses from options.
		 * - Use WC_Order_Query to check for recent orders.
		 */
		$nm_allowed_order_statuses = XLWCTY_Core()->data->get_option( 'allowed_order_statuses' );
		$clean_statuses            = array_map( function ( $status ) {
			return trim( sanitize_key( $status ) );
		}, $nm_allowed_order_statuses );

		$order_query = new WC_Order_Query( array(
			'status'     => $clean_statuses,
			'limit'      => 1,  // Only fetch one order
			'return'     => 'ids',
			'post_types' => array( 'shop_order', 'shop_order_placehold' ),
		) );

		$recent_orders = $order_query->get_orders();

		// Check if there are recent orders
		if ( ! empty( $recent_orders ) ) {
			$recent_order_state = true;
		}

		/**
		 * Step 4: Prepare status response and suggestions.
		 * - Include suggestions for fixing issues if any conditions fail.
		 */
		$nextmove_state = 'failed';

		$check_preview = '<p>' . __( 'Something is wrong with settings. Thank You page won\'t appear until all points turn green.', 'woo-thank-you-page-nextmove-lite' ) . '</p><h3>' . esc_html__( 'Possible Solutions', 'woo-thank-you-page-nextmove-lite' ) . '</h3><ul>';

		// Add suggestions for each failed condition
		if ( $mode === 'sandbox' ) {
			$change_mod_link = ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=xl-thank-you&section=settings' ) . '" target="_blank">' . __( 'Live', 'woo-thank-you-page-nextmove-lite' ) . '</a>';
			$check_preview   .= '<li>' . __( 'NextMove mode is Sandbox. Click here to change it to', 'woo-thank-you-page-nextmove-lite' ) . $change_mod_link . '</li>';
		}

		if ( ! $permalink_state ) {
			/* translators: %s: URL of the Permalinks settings screen */
			$check_preview .= '<li>' . sprintf( __( 'Permalink needs reset. Click here to <a href="%s" target="_blank">reset</a> it.', 'woo-thank-you-page-nextmove-lite' ), esc_url( admin_url( 'options-permalink.php' ) ) ) . '</li>';
		}

		if ( ! $available_thankyou_page ) {
			$check_preview .= '<li>' . __( 'There are no Active Thank You pages. Create a New page or Activate an existing one.', 'woo-thank-you-page-nextmove-lite' ) . '</li>';
		}

		if ( ! $recent_order_state ) {
			$check_preview .= '<li>' . __( 'There are no Active WooCommerce Orders with selected order statuses', 'woo-thank-you-page-nextmove-lite' ) . ' (' . self::order_status_label_output( $nm_allowed_order_statuses ) . '). ' . __( 'Kindly create an order to see the preview.', 'woo-thank-you-page-nextmove-lite' ) . '</li>';
		}

		/* translators: %s: URL of the XLPlugins support screen */
		$check_preview .= '</ul><p><strong>' . sprintf( __( 'If still unable to set up the Thank You page, create a <a target="_blank" href="%s">support ticket</a>.', 'woo-thank-you-page-nextmove-lite' ), esc_url( admin_url( 'admin.php?page=xlplugins&tab=support' ) ) ) . '</strong></p>';

		// Mark success if all conditions are met
		if ( $mode === 'live' && $available_thankyou_page && $permalink_state && $recent_order_state ) {
			$nextmove_state = 'success';
			/* translators: %s: order preview URL */
			$check_preview  = '<p>' . sprintf( __( 'Looks Good! <a target="_blank" href="%s">See Order Preview</a>', 'woo-thank-you-page-nextmove-lite' ), esc_url( $get_link ) ) . '</p>';
		}

		// Prepare final HTML for the status
		$yes = __( 'Yes', 'woo-thank-you-page-nextmove-lite' );
		$no  = __( 'No', 'woo-thank-you-page-nextmove-lite' );

		/* translators: 1: mode name (Live / Sandbox), 2: settings URL */
		$html = '<li><i class="xl_circle_' . ( ( $mode === 'live' ) ? 'success' : 'error' ) . '"></i>' . sprintf( __( 'Mode: %1$s (<a href="%2$s" target="_blank">Change</a>)', 'woo-thank-you-page-nextmove-lite' ), esc_html( ( $mode === 'live' ) ? __( 'Live', 'woo-thank-you-page-nextmove-lite' ) : ( ( $mode === 'sandbox' ) ? __( 'Sandbox', 'woo-thank-you-page-nextmove-lite' ) : ucfirst( $mode ) ) ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=xl-thank-you&section=settings' ) ) ) . '</li>';

		/* translators: 1: OK / Needs Reset, 2: optional "Reset" link */
		$html .= '<li><i class="xl_circle_' . ( ( $permalink_state ) ? 'success' : 'error' ) . '"></i>' . sprintf( __( 'Permalink State: %1$s %2$s', 'woo-thank-you-page-nextmove-lite' ), ( $permalink_state ) ? esc_html__( 'OK', 'woo-thank-you-page-nextmove-lite' ) : esc_html__( 'Needs Reset', 'woo-thank-you-page-nextmove-lite' ), ( $permalink_state ) ? '' : '<a target="_blank" href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Reset', 'woo-thank-you-page-nextmove-lite' ) . '</a>' ) . '</li>';

		/* translators: %s: Yes / No */
		$html .= '<li><i class="xl_circle_' . ( ( $available_thankyou_page ) ? 'success' : 'error' ) . '"></i>' . sprintf( __( 'Active Pages: %s', 'woo-thank-you-page-nextmove-lite' ), esc_html( ( $available_thankyou_page ) ? $yes : $no ) ) . '</li>';

		/* translators: %s: Yes / No */
		$html .= '<li><i class="xl_circle_' . ( ( $recent_order_state ) ? 'success' : 'error' ) . '"></i>' . sprintf( __( 'Active WC Orders: %s', 'woo-thank-you-page-nextmove-lite' ), esc_html( ( $recent_order_state ) ? $yes : $no ) ) . '</li>';

		// Send JSON response
		wp_send_json( array(
			'status'         => 'success',
			'nextmove_state' => $nextmove_state,
			'html'           => $html,
			'after_text'     => $check_preview,
		) );
	}


	public static function parse_url_for_ssl( $url ) {
		if ( ! empty( $url ) ) {
			$replace_to = 'http://';
			if ( is_ssl() ) {
				$replace_to = 'https://';
			}
			$url = preg_replace( '(^https?://)', $replace_to, $url );

			return $url;
		}

		return false;
	}

	public static function order_status_label_output( $order_statuses ) {
		$all_order_statuses = wc_get_order_statuses();
		$output             = '';
		$order_single       = array();

		if ( is_array( $order_statuses ) && count( $order_statuses ) > 0 ) {
			foreach ( $order_statuses as $order_status_single ) {
				if ( isset( $all_order_statuses[ $order_status_single ] ) ) {
					$order_single[] = $all_order_statuses[ $order_status_single ];
				}
			}
		}

		if ( count( $order_single ) > 0 ) {
			$last   = array_slice( $order_single, - 1 );
			$first  = join( ', ', array_slice( $order_single, 0, - 1 ) );
			$both   = array_filter( array_merge( array( $first ), $last ), 'strlen' );
			$output = join( ' & ', $both );
		}

		return $output;
	}

	public static function render_quick_view_metabox() {
		?>
        <div class="postbox xlwcty_side_content">
            <div class="inside">
                <h3 class="xlwcty_first_elem">NextMove Summary</h3>
                <ul class="xlwcty_quick_view xlwcty-quick-view-ajaxwrap">
                    <script type="text/html" id="tmpl-xlwcty-quick-view-template">{{{data.html}}}</script>
                </ul>
                <div class="xlwcty_status_support_text"><img style="margin-left: 40%" src="<?php echo plugin_dir_url( XLWCTY_PLUGIN_FILE ) . '/admin/assets/img/spinner.gif'; ?>"></div>
            </div>
        </div>
		<?php
	}

	/**
	 * Get Product parent id  for both version of woocommerce 2.6 and >3.0
	 *
	 * @param WC_Product $product
	 *
	 * @return integer
	 */
	public static function get_product_parent_id( $product ) {
		$parent_id = 0;

		if ( $product instanceof WC_Product ) {
			$parent_id = XLWCTY_Compatibility::get_product_parent_id( $product );
			if ( $parent_id == false ) {
				$parent_id = $product->get_id();
			}
		} elseif ( 0 !== $product ) {
			$parent_id = wp_get_post_parent_id( $product );

			if ( $parent_id == false ) {
				$parent_id = (int) $product;
			}
		}

		return $parent_id;
	}

	/**
	 * Get active multilingual compatibility handler (WPML or Polylang).
	 *
	 * @return object|false
	 */
	public static function get_multilingual_compat() {
		static $compat = null;

		if ( null !== $compat ) {
			return false === $compat ? false : $compat;
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'XLWCTY_WPML' ) ) {
			$wpml = XLWCTY_WPML::get_instance();
			if ( $wpml->is_wpml_active() ) {
				$compat = $wpml;

				return $compat;
			}
		}

		if ( function_exists( 'pll_current_language' ) && class_exists( 'XLWCTY_Polylang' ) ) {
			$polylang = XLWCTY_Polylang::get_instance();
			if ( $polylang->is_polylang_active() ) {
				$compat = $polylang;

				return $compat;
			}
		}

		$compat = false;

		return false;
	}

	/**
	 * Expand post/term IDs to include all linked translations.
	 *
	 * @param array  $ids  IDs to expand.
	 * @param string $type Either 'post' or 'term'.
	 *
	 * @return array
	 */
	public static function expand_ids_with_translations( $ids, $type = 'post' ) {
		$ids = array_filter( array_map( 'absint', (array) $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$expanded = $ids;

		if ( 'term' === $type && function_exists( 'pll_get_term_translations' ) ) {
			foreach ( $ids as $id ) {
				$translations = pll_get_term_translations( $id );
				if ( is_array( $translations ) ) {
					$expanded = array_merge( $expanded, array_values( $translations ) );
				}
			}
		} elseif ( 'post' === $type && function_exists( 'pll_get_post_translations' ) ) {
			foreach ( $ids as $id ) {
				$translations = pll_get_post_translations( $id );
				if ( is_array( $translations ) ) {
					$expanded = array_merge( $expanded, array_values( $translations ) );
				}
			}
		} elseif ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) ) {
			global $sitepress;
			if ( $sitepress instanceof SitePress ) {
				$active_languages = $sitepress->get_active_languages();
				foreach ( $ids as $id ) {
					foreach ( array_keys( $active_languages ) as $lang ) {
						if ( 'term' === $type ) {
							$taxonomy     = 'product_cat';
							$translated   = apply_filters( 'wpml_object_id', $id, $taxonomy, false, $lang );
						} else {
							$post_type  = get_post_type( $id ) ?: 'product';
							$translated = apply_filters( 'wpml_object_id', $id, $post_type, false, $lang );
						}
						if ( $translated ) {
							$expanded[] = (int) $translated;
						}
					}
				}
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $expanded ) ) ) );
	}

	/**
	 * @param $url
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function get_video_id( $url, $type = 'youtube' ) {
		if ( empty( $url ) ) {
			return;
		}
		if ( 'youtube' == $type ) {
			preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match );
			if ( isset( $match[1] ) && ! empty( $match[1] ) ) {
				return $match[1];
			}

			return;
		} elseif ( 'vimeo' == $type ) {
			preg_match( '%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $match );
			if ( isset( $match[3] ) && ! empty( $match[3] ) ) {
				return $match[3];
			}

			return;
		}

		return $url;

	}

	/**
	 * Return modified local store time or formatted time if format given
	 *
	 * @param $time unix timestamp
	 * @param string $format
	 *
	 * @return int|string
	 */
	public static function modified_timestamp_by_local_time( $time, $format = '' ) {
		$local_time = current_time( 'timestamp' );
		$gmt0_time  = time();
		$diff       = $local_time - $gmt0_time;

		$mod_time = $time + $diff;

		if ( empty( $format ) ) {
			return $mod_time;
		} else {
			return date_i18n( $format, $mod_time );
		}

	}

	public static function get_thankyou_page_id( $page_id ) {
		if ( is_null( self::$custom_ty_pages ) ) {
			$custom_pages = get_option( 'xlwcty_custom_thank_you_pages', array() );
			if ( empty( $custom_pages ) ) {
				self::$custom_ty_pages = [];

				return;
			}
			self::$custom_ty_pages = $custom_pages;
		}

		if ( empty( self::$custom_ty_pages ) ) {
			return;
		}

		foreach ( self::$custom_ty_pages as $key => $value ) {
			if ( $value == $page_id ) {
				/** @var checking if the  $thankyou_post not exists than continue */
				$thankyou_post = get_post( $key );
				if ( ! $thankyou_post instanceof WP_POST ) {
					continue;
				}
				XLWCTY_Core()->public->xlwcty_is_thankyou = true;
				XLWCTY_Core()->data->page_id              = $key;

				return $key;
			}
		}
	}

	/** premium component
	 * @return array
	 */
	public static function get_premium_components() {
		$premium_components = array(
			'_xlwcty_coupon',
			'_xlwcty_text_2',
			'_xlwcty_text_3',
			'_xlwcty_text_4',
			'_xlwcty_text_5',
			'_xlwcty_image_2',
			'_xlwcty_image_3',
			'_xlwcty_image_4',
			'_xlwcty_image_5',
			'_xlwcty_simple_text_2',
			'_xlwcty_simple_text_3',
			'_xlwcty_simple_text_4',
			'_xlwcty_simple_text_5',
			'_xlwcty_video_2',
			'_xlwcty_video_3',
			'_xlwcty_video_4',
			'_xlwcty_video_5',
			'_xlwcty_cross_sell_product',
			'_xlwcty_social_sharing',
			'_xlwcty_recently_viewed_product',
			'_xlwcty_related_product',
			'_xlwcty_social_coupons',
			'_xlwcty_share_order',
			'_xlwcty_specific_product',
			'_xlwcty_upsell_product',
			'_xlwcty_track_order',
		);

		return $premium_components;
	}

	public static function is_lmfwc_activated() {
		// is_plugin_active() lives in an admin include that WP < 6.8 does not load on the front end.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$is_activated = is_plugin_active( 'license-manager-for-woocommerce/license-manager-for-woocommerce.php' );

		return $is_activated ? true : false;
	}
}
