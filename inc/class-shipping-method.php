<?php

namespace Advanced_Rule_Based_Shipping;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Advanced Rule Based Shipping method 
 */
class Shipping_Method extends \WC_Shipping_Method {

	/**
	 * Hold matched rule item
	 * 
	 * @var array
	 */
	private $matched_rule = array();

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @return void
	 */

	public function __construct($instance_id = 0) {
		parent::__construct($instance_id);

		$this->id = 'advanced_rule_based_shipping';
		$this->enabled = 'yes';
		$this->method_title = esc_html__('Advanced Rule Based Shipping', 'advanced-rule-based-shipping');
		$this->method_description = esc_html__('Create multiple rule based shipping for customers.', 'advanced-rule-based-shipping');

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->instance_form_fields = $this->get_settings();
		$this->title = $this->get_option('name', 'Advanced Rule Based Shipping');
		$this->tax_status = $this->get_option('tax_status');

		$this->matched_rule = $this->get_matched_rule();
	}

	/**
	 * Get the settings options of shipping method
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings() {
		global $wpdb;


		$settings = array(
			'name' => array(
				'title'       => __('Name', 'advanced-rule-based-shipping'),
				'type'        => 'text',
				'default'     => __('Advanced Rule Based Shipping', 'advanced-rule-based-shipping'),
			),

			'tax_status' => array(
				'title'   => __('Tax status', 'advanced-rule-based-shipping'),
				'type'    => 'select',
				'default' => 'taxable',
				'class'   => 'wc-enhanced-select',
				'options' => array(
					'taxable' => __('Taxable', 'advanced-rule-based-shipping'),
					'none'    => __('None', 'advanced-rule-based-shipping'),
				),
			),

			'shipping_rule' => array(
				'title'   => __('Shipping Rule', 'advanced-rule-based-shipping'),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'options' => array(
					'' => esc_html__('Choose a Shipping Rule', 'advanced-rule-based-shipping')
				),
			),

			'product_ids' => array(
				'title'       => __('Product IDs', 'advanced-rule-based-shipping'),
				'type'        => 'text',
				'default'     => '',
				'description' => __('Enable this shipping method if the cart has one of the above product IDs. Use comma for separate multiple product IDs.', 'advanced-rule-based-shipping'),
				'desc_tip'    => true,
			),

			'coupons' => array(
				'title'       => __('Cart Coupons', 'advanced-rule-based-shipping'),
				'type'        => 'text',
				'default'     => '',
				'description' => __('Enable this shipping method if the cart applied one of the above coupon codes. Use comma for separate multiple coupon.', 'advanced-rule-based-shipping'),
				'desc_tip'    => true,
			),
		);

		$shipping_rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = %s", Shipping_Rule::POST_TYPE));
		foreach ($shipping_rules as $shipping_rule) {
			$settings['shipping_rule']['options'][$shipping_rule->ID] = $shipping_rule->post_title;
		}

		return $settings;
	}

	/**
	 * Check if this shipping method availble for this cart
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_available($package) {
		if (!parent::is_available($package)) {
			return false;
		}

		$shipping_rule = new Shipping_Rule($this->get_option('shipping_rule'));
		if (!$shipping_rule->exists()) {
			return false;
		}

		$cart = WC()->cart;
		if (is_null($cart)) {
			return parent::is_available($package);
		}

		$product_ids = trim($this->get_option('product_ids'));
		if (!empty($product_ids)) {
			$product_ids = explode(',', $product_ids);
			$product_ids = array_map(function ($id) {
				return absint($id);
			}, $product_ids);

			$product_ids = array_filter($product_ids, function ($id) {
				return $id > 0;
			});

			if (count($product_ids) > 0) {
				$has_product_id = false;
				foreach ($cart->get_cart() as $cart_item) {
					if (in_array($cart_item['product_id'], $product_ids)) {
						$has_product_id = true;
						continue;
					}
				}

				if (false == $has_product_id) {
					return false;
				}
			}
		}

		$coupons = trim(strtolower($this->get_option('coupons')));
		if (!empty($coupons)) {
			$coupons = array_filter(array_map('trim', explode(',', $coupons)));
			if (count($coupons) > 0) {
				$common_coupons = array_intersect($cart->applied_coupons, $coupons);
				if (count($common_coupons) === 0) {
					return false;
				}
			}
		}

		return parent::is_available($package);
	}

	/**
	 * Get matched rule
	 * 
	 * @since 1.0.0
	 * @return false|array
	 */
	public function get_matched_rule() {
		if (is_null(WC()->cart)) {
			return false;
		}

		$shipping_rule = new Shipping_Rule($this->get_option('shipping_rule'));
		if (!$shipping_rule->exists()) {
			return false;
		}

		$rules = array_map(function ($rule) {
			$matched_conditions = array_filter($rule['conditions'], function ($condition) {
				return apply_filters('advanced_rule_based_shipping/condition_matched', false, wp_parse_args($condition, Utils::get_condition_values()));
			});

			$rule['matched_conditions'] = count($matched_conditions);

			return $rule;
		}, $shipping_rule->get_active_rules());

		$matched_rules = array_filter($rules, function ($rule) {
			if ('match_any' == $rule['condition_relationship'] && $rule['matched_conditions'] > 0) {
				return true;
			}

			if ('match_all' == $rule['condition_relationship'] && $rule['matched_conditions'] === count($rule['conditions'])) {
				return true;
			}

			return false;
		});

		if (count($matched_rules) === 0) {
			return false;
		}

		array_walk($matched_rules, function (&$rule) {
			//unset($rule['conditions']);
			if (empty($rule['shipping_cost_operator'])) {
				$rule['shipping_cost_operator'] = 'multiply';
			}

			$shipping_cost = floatval($rule['shipping_cost']);

			if (in_array($rule['shipping_cost_type'], array('based_on_weight', 'based_on_quantity'))) {
				$shipping_cost_type_value = 0.00;
				if ('based_on_weight' === $rule['shipping_cost_type']) {
					$shipping_cost_type_value = WC()->cart->cart_contents_weight;
				}

				if ('based_on_quantity' === $rule['shipping_cost_type']) {
					$shipping_cost_type_value = WC()->cart->get_cart_contents_count();
				}

				if (0 != $shipping_cost) {
					if ('multiply' === $rule['shipping_cost_operator']) {
						$shipping_cost = $shipping_cost_type_value * $shipping_cost;
					}

					if ('divide' === $rule['shipping_cost_operator']) {
						$shipping_cost = $shipping_cost_type_value / $shipping_cost;
					}
				}

				$shipping_cost = $shipping_cost + floatval($rule['shipping_cost_extra_charge']);

				$min_shipping_cost = floatval($rule['min_shipping_cost']);
				if ($shipping_cost < $min_shipping_cost) {
					$shipping_cost = $min_shipping_cost;
				}

				$max_shipping_cost = strlen($rule['max_shipping_cost']) == 0 ? $shipping_cost : floatval($rule['max_shipping_cost']);
				if ($shipping_cost > $max_shipping_cost) {
					$shipping_cost = $max_shipping_cost;
				}
			}

			if ('free_shipping' == $rule['shipping_cost_type']) {
				$shipping_cost = 0.00;
			}

			$rule['shipping_cost'] = $shipping_cost;
		});

		usort($matched_rules, function ($a, $b) {
			return $a['shipping_cost'] > $b['shipping_cost'] ? -1 : 1;
		});

		usort($matched_rules, function ($a, $b) {
			return $a['matched_conditions'] > $b['matched_conditions'] ? 1 : -1;
		});

		return end($matched_rules);
	}

	/**
	 * Calculate the shipping cost
	 * 
	 * @since 1.0.0
	 */
	public function calculate_shipping($package = array()) {
		if (false === $this->matched_rule) {
			return;
		}

		$rate = array(
			'package' => $package,
			'id' => $this->get_rate_id(),
			'label' => $this->matched_rule['title'],
			'cost' => floatval($this->matched_rule['shipping_cost']),
		);

		$this->add_rate($rate);
	}

	/**
	 * Get description for show below the shipping rate
	 * 
	 * @since 1.0.0
	 * @return html
	 */
	public function get_description() {
		$description = $this->get_option('description');

		if (false !== $this->matched_rule) {
			$description = $this->matched_rule['description'];
		}

		return do_shortcode($description);
	}
}
