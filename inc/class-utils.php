<?php

namespace Advanced_Rule_Based_Shipping;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utilities class 
 */
class Utils {

	/**
	 * Check if pro version installed
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function has_pro_installed() {
		return file_exists(WP_PLUGIN_DIR . '/advanced-rule-based-shipping-pro/advanced-rule-based-shipping-pro.php');
	}

	/**
	 * Check if pro plugin activated
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_pro_activated() {
		return class_exists('\Advanced_Rule_Based_Shipping_Pro\Main');
	}

	/**
	 * Check if pro plugin activated the license
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function license_activated() {
		if (!class_exists('\Advanced_Rule_Based_Shipping_Pro\Upgrade')) {
			return false;
		}

		return \Advanced_Rule_Based_Shipping_Pro\Upgrade::license_activated();
	}

	/**
	 * Get rule values
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public static function get_rule_values() {
		$values = apply_filters('advanced_rule_based_shipping/rule_values', array());

		return array_merge($values, array(
			'disabled' => false,
			'title' => '',
			'description' => '',
			'collapse' => false,
			'private_note' => '',
			'shipping_cost' => '',
			'conditions' => array(),
			'active_on' => 'immediately',
			'start_after_date' => '',
			'shipping_cost_extra_charge' => '',
			'shipping_cost_type' => 'fixed_amount',
			'shipping_cost_operator' => 'multiply',
			'min_shipping_cost' => '',
			'max_shipping_cost' => '',
			'condition_relationship' => 'match_all',
		));
	}

	/**
	 * Get conditions values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_condition_values() {
		$values = apply_filters('advanced_rule_based_shipping/condition_values', array());

		return array_merge($values, array(
			'value' => '',
			'value2' => '',
			'type' => 'cart:subtotal',
			'operator' => 'greater_than',
		));
	}

	/**
	 * Get condition extra values for UI management
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_condition_ui_values() {
		return apply_filters('advanced_rule_based_shipping/condition_ui_values', array('loading' => false));
	}

	/**
	 * Get condition operators
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators($operators = array()) {
		$supported_operators = array(
			'equal_to' => __('Equal To', 'advanced-rule-based-shipping'),
			'less_than' => __('Less than ( < )', 'advanced-rule-based-shipping'),
			'less_than_or_equal' => __('Less than or equal ( <= )', 'advanced-rule-based-shipping'),
			'greater_than_or_equal' => __('Greater than or equal ( >= )', 'advanced-rule-based-shipping'),
			'greater_than' => __('Greater than ( > )', 'advanced-rule-based-shipping'),
			'between' => __('Between', 'advanced-rule-based-shipping'),
			'any_in_list' => __('Any in list', 'advanced-rule-based-shipping'),
			'all_in_list' => __('All in list', 'advanced-rule-based-shipping'),
			'not_in_list' => __('Not in list', 'advanced-rule-based-shipping'),

			'before' => __('Before', 'advanced-rule-based-shipping'),
			'after' => __('After', 'advanced-rule-based-shipping'),
		);

		$return_operators = [];
		while ($key = current($operators)) {
			if (isset($supported_operators[$key])) {
				$return_operators[$key] = $supported_operators[$key];
			}

			next($operators);
		}

		return $return_operators;
	}

	/**
	 * Get condition operators dropdown
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators_options($args = array()) {
		$operators = self::get_operators($args);

		$options = array_map(function ($label, $key) {
			return sprintf('<option value="%s">%s</option>', $key, $label);
		}, $operators, array_keys($operators));

		echo wp_kses(implode('', $options), array(
			'option' => array(
				'value' => true
			)
		));
	}

	/**
	 * Group of conditions
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_condition_groups() {
		return apply_filters('advanced_rule_based_shipping/condition_groups', array(
			'cart' => __('Cart', 'advanced-rule-based-shipping'),
			'cart_products' => __('Cart Products', 'advanced-rule-based-shipping'),
			'date' => __('Date', 'advanced-rule-based-shipping'),
			'billing' => __('Billing', 'advanced-rule-based-shipping'),
			'shipping' => __('Shipping', 'advanced-rule-based-shipping'),
			'user' => __('Customer', 'advanced-rule-based-shipping'),
			'order_history' => __('Order History', 'advanced-rule-based-shipping'),
			'others' => __('Others', 'advanced-rule-based-shipping'),
		));
	}

	/**
	 * Get condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_all_conditions() {
		return apply_filters('advanced_rule_based_shipping/condition_types', array(
			'cart:subtotal' => array(
				'group' => 'cart',
				'priority' => 10,
				'label' => __('Subtotal', 'advanced-rule-based-shipping'),
			),
			'cart:total_quantity' => array(
				'group' => 'cart',
				'priority' => 15,
				'label' => __('Total quantity', 'advanced-rule-based-shipping'),
			),
			'cart:total_weight' => array(
				'group' => 'cart',
				'priority' => 20,
				'label' => __('Total weight', 'advanced-rule-based-shipping'),
			),
			'cart:coupons' => array(
				'group' => 'cart',
				'priority' => 25,
				'label' => __('Coupons', 'advanced-rule-based-shipping'),
			),

			/** Cart Product related field types */
			'cart_products:products' => array(
				'group' => 'cart_products',
				'priority' => 5,
				'label' => __('Products', 'advanced-rule-based-shipping'),
			),

			/** Date field types */
			'date:time' => array(
				'group' => 'date',
				'priority' => 5,
				'label' => __('Time', 'advanced-rule-based-shipping'),
			),
			'date:date' => array(
				'group' => 'date',
				'priority' => 10,
				'label' => __('Date', 'advanced-rule-based-shipping'),
			),
			'date:weekly_days' => array(
				'group' => 'date',
				'priority' => 15,
				'label' => __('Weekly Days', 'advanced-rule-based-shipping'),
			),

			/** Billing  field types */
			'billing:city' => array(
				'group' => 'billing',
				'priority' => 10,
				'label' => __('City', 'advanced-rule-based-shipping'),
			),
			'billing:zipcode' => array(
				'group' => 'billing',
				'priority' => 20,
				'label' => __('Zip code', 'advanced-rule-based-shipping'),
			),
			'billing:state' => array(
				'group' => 'billing',
				'priority' => 25,
				'label' => __('State', 'advanced-rule-based-shipping'),
			),
			'billing:country' => array(
				'group' => 'billing',
				'priority' => 30,
				'label' => __('Country', 'advanced-rule-based-shipping'),
			),

			'shipping:city' => array(
				'group' => 'shipping',
				'priority' => 10,
				'label' => __('City', 'advanced-rule-based-shipping'),
			),
			'shipping:zipcode' => array(
				'group' => 'shipping',
				'priority' => 15,
				'label' => __('Zip code', 'advanced-rule-based-shipping'),
			),
			'shipping:state' => array(
				'group' => 'shipping',
				'priority' => 20,
				'label' => __('State', 'advanced-rule-based-shipping'),
			),
			'shipping:country' => array(
				'group' => 'shipping',
				'priority' => 25,
				'label' => __('Country', 'advanced-rule-based-shipping'),
			),

			'user:users' => array(
				'group' => 'user',
				'priority' => 10,
				'label' => __('Users', 'advanced-rule-based-shipping'),
			),
			'user:roles' => array(
				'group' => 'user',
				'priority' => 15,
				'label' => __('Roles', 'advanced-rule-based-shipping'),
			),
			'user:logged_in' => array(
				'group' => 'user',
				'priority' => 20,
				'label' => __('Logged In', 'advanced-rule-based-shipping'),
			),

			/** Order History related field types */
			'order_history:first_purchase' => array(
				'group' => 'order_history',
				'priority' => 5,
				'label' => __('First Purchase', 'advanced-rule-based-shipping'),
			),
		));
	}

	/**
	 * Get conditions of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_conditions_by_group($group) {
		$all_conditions = self::get_all_conditions();

		$group_conditions = [];

		foreach ($all_conditions as $key => $condition) {
			if ($group !== $condition['group']) {
				continue;
			}

			$group_conditions[$key] = $condition;
		}

		uasort($group_conditions, function ($a, $b) {
			return $a['priority'] > $b['priority'] ? 1 : -1;
		});

		return $group_conditions;
	}

	/**
	 * Select2 ajax values map
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public static function get_select2_values_map($args = null) {
		return wp_parse_args($args, array(
			'model' => 'placeholder',
			'data_type' => 'data_type_placeholder',
			'hold_data' => 'hold_data_placeholder',
		));
	}

	/**
	 * Get type of shipping cost
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public static function get_shipping_cost_types() {
		$types = apply_filters('advanced_rule_based_shipping/shipping_cost_types', array());

		return array_merge($types, array(
			'free_shipping' => __('Free shipping', 'advanced-rule-based-shipping'),
			'fixed_amount' => __('Fixed amount', 'advanced-rule-based-shipping'),
			'based_on_weight' => __('Based on weight', 'advanced-rule-based-shipping'),
			'based_on_quantity' => __('Based on quantity', 'advanced-rule-based-shipping'),
		));
	}

	/**
	 * Get shipping cost operator
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public static function get_shipping_cost_operators() {
		$operators = apply_filters('advanced_rule_based_shipping/shipping_cost_operators', array());

		return array_merge($operators, array(
			'multiply' => __('Multiply', 'advanced-rule-based-shipping'),
			'divide' => __('Divide', 'advanced-rule-based-shipping'),
		));
	}

	/**
	 * Free lock message
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function field_lock_message() {
		if (self::has_pro_installed()) {
			if (class_exists('\Advanced_Rule_Based_Shipping_Pro\Upgrade')) {
				if (!\Advanced_Rule_Based_Shipping_Pro\Upgrade::license_activated()) {
					echo '<div class="locked-message locked-message-activate-license">';
					$message = sprintf(
						/* translators: %1$s: Link open, %2$s: Link close */
						esc_html__('Please activate your license on the %1$sShipping Rules page%2$s for unlock this feature.', 'advanced-rule-based-shipping'),
						'<a href="' . esc_url(menu_page_url('advanced-rule-based-shipping', false)) . '">',
						'</a>'
					);
					echo wp_kses($message, array('a' => array('href' => true,  'target' => true)));
					echo '</div>';
				}
			} else {
				echo '<div class="locked-message">';
				esc_html_e('Please activate the Advanced Rule Based Shipping Pro plugin.', 'advanced-rule-based-shipping');
				echo '</div>';
			}
		} else {
			echo '<div class="locked-message">Get the <a target="_blank" :href="get_pro_link">pro version</a> for unlock this feature.</div>';
		}
	}

	/**
	 * Get registered taxonomies of product
	 * 
	 * @since 1.0.4
	 * @return array
	 */
	public static function get_product_taxonomies() {
		$taxonomies = get_object_taxonomies('product', 'objects');
		foreach ($taxonomies as $tax_slug => $taxonomy) {
			if (false === $taxonomy->public) {
				unset($taxonomies[$tax_slug]);
			}
		}

		$taxonomies = array_map(function ($taxonomy) {
			return (object) array(
				'slug' => $taxonomy->name,
				'label' => $taxonomy->label,
			);
		}, $taxonomies);

		return $taxonomies;
	}
}
