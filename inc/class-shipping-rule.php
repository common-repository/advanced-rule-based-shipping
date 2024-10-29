<?php

namespace Advanced_Rule_Based_Shipping;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class for Shipping Rule
 */
final class Shipping_Rule {

	/**
	 * Post type of shipping rule
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	const POST_TYPE = 'shipping_rule';

	/**
	 * Shipping Rule ID
	 * 
	 * @since 1.0.0
	 * @var int
	 */
	public $id = 0;

	/**
	 * Title of Shipping Rule
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $title = '';

	/**
	 * Rules of current Shipping Rule
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $rules = [];

	/**
	 * Hold the shipping rule post object
	 * 
	 * @since 1.0.0
	 * @var object
	 */
	public $post = null;

	/**
	 * Constructor.
	 */
	public function __construct($id = 0) {
		if (absint($id) == 0) {
			return;
		}

		$shipping_rule = get_post($id);
		if (isset($shipping_rule->post_type) && self::POST_TYPE == $shipping_rule->post_type) {
			$this->id = absint($id);
			$this->sanitize_data($shipping_rule);
		}
	}

	/**
	 * Sanitize post data for rule
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function sanitize_data($shipping_rule) {
		$this->post = $shipping_rule;

		$this->title = $shipping_rule->post_title;

		$rules = json_decode($shipping_rule->post_content, true);
		if (!is_array($rules)) {
			$rules = [];
		}

		foreach ($rules as $rule_key => $rule) {
			$conditions = isset($rule['conditions']) && is_array($rule['conditions']) ? $rule['conditions'] : [];

			$conditions = array_map(function ($condition) {
				$condition = wp_parse_args($condition, Utils::get_condition_values());
				if ('between_values' === $condition['operator']) {
					$condition['operator'] = 'between';
				}

				if ('cart_products:categories' === $condition['type']) {
					$condition['type'] = 'cart_products:product_cat';
					if (isset($condition['categories']) && is_array($condition['categories'])) {
						$condition['cart_products_product_cat'] = $condition['categories'];
					}
				}

				if ('cart_products:tags' === $condition['type']) {
					$condition['type'] = 'cart_products:product_tag';
					if (isset($condition['tags']) && is_array($condition['tags'])) {
						$condition['cart_products_product_tag'] = $condition['tags'];
					}
				}

				if ('cart_products:shipping_classes' === $condition['type']) {
					$condition['type'] = 'cart_products:product_shipping_class';
					if (isset($condition['shipping_classes']) && is_array($condition['shipping_classes'])) {
						$condition['cart_products_product_shipping_class'] = $condition['shipping_classes'];
					}
				}

				return apply_filters('advanced_rule_based_shipping/migrate_data', $condition);
			}, $conditions);

			$rules[$rule_key]['conditions'] = $conditions;
		}

		$this->rules = $rules;
	}

	/**
	 * Get metadata from post
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_meta($key) {
		if (!is_null($this->post)) {
			return $this->post->$key;
		}

		return null;
	}

	/**
	 * Save current shipping rule
	 * 
	 * @since 1.0.0
	 * @return int|WP_Error
	 */
	public function save() {
		$shipping_rule_data = array(
			'ID' => $this->id,
			'post_type' => self::POST_TYPE,
			'post_title' => $this->title,
			'post_date' => gmdate('Y-m-d H:i:s'),
			'post_content' => wp_json_encode($this->rules)
		);

		if (!is_null($this->post)) {
			$shipping_rule_data['post_date'] = $this->get_meta('post_date');
			$shipping_rule_data['post_date_gmt'] = $this->get_meta('post_date_gmt');
		}

		return wp_insert_post($shipping_rule_data);
	}

	/**
	 * Check if shipping rule exists
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function exists() {
		return $this->id > 0;
	}

	/**
	 * Get id of current shipping rule
	 * 
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get rules of current shipping rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_rules() {
		return $this->rules;
	}

	/**
	 * Get sanitize rules of current shipping rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_sanitize_rules() {
		return array_map(function ($rule) {
			return wp_parse_args($rule, Utils::get_rule_values());
		}, $this->rules);
	}

	/**
	 * Get active rules of current shipping rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_active_rules() {
		$rules = array_filter($this->get_sanitize_rules(), function ($rule) {
			if ('start_after_date' === $rule['active_on']) {
				$active_on_date = strtotime($rule['start_after_date']);
				if ($active_on_date && $active_on_date > current_time('timestamp')) {
					return false;
				}
			}

			return false == $rule['disabled'] && $rule['conditions'] > 0;
		});

		return $rules;
	}

	/**
	 * Get attached shipping rates
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_attached_shipping_rates() {
		$zones = \WC_Shipping_Zones::get_zones();
		$rest_world_zone = new \WC_Shipping_Zone(0);

		$zones[] = array(
			'zone_name' => esc_html__('Rest World', 'advanced-rule-based-shipping'),
			'shipping_methods' => $rest_world_zone->get_shipping_methods(false, 'admin')
		);

		$shipping_methods = array();
		foreach ($zones as $zone) {
			foreach ($zone['shipping_methods'] as $shipping_method) {
				if ('advanced_rule_based_shipping' == $shipping_method->id) {
					if ($this->get_id() == $shipping_method->get_option('shipping_rule')) {
						$shipping_methods[$zone['zone_name']][] = $shipping_method;
					}
				}
			}
		}

		return $shipping_methods;
	}
}
