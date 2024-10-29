<?php

namespace Advanced_Rule_Based_Shipping\Condition;

use Advanced_Rule_Based_Shipping\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart Condition class
 */
final class Cart {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('advanced_rule_based_shipping/condition_values', array($this, 'condition_values'));
		add_filter('advanced_rule_based_shipping/condition_ui_values', array($this, 'condition_ui_values'));
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'common_filters'), 10, 2);
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'coupon_filters'), 10, 2);

		add_action('advanced_rule_based_shipping/condition_templates', array($this, 'common_templates'));
		add_action('advanced_rule_based_shipping/condition_templates', array($this, 'coupon_template'));
		add_action('advanced_rule_based_shipping/cart_common_fields', array($this, 'cart_common_fields'));
	}

	/**
	 * Condition values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function condition_values($values) {
		return array_merge($values, array(
			'coupons' => [],
			'cart_value_type' => 'in_cart',
		));
	}

	/**
	 * Condition UI values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function condition_ui_values($values) {
		return array_merge($values, array(
			'hold_coupons' => [],
		));
	}

	/**
	 * Cart related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function common_filters($matched, $condition) {
		if (!in_array($condition['type'], array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight'))) {
			return $matched;
		}

		$operator = $condition['operator'];
		$value_one = floatval($condition['value']);
		$value_two = isset($condition['value2']) ? floatval($condition['value2']) : 0.00;

		$compare_value = 0.00;
		if ('cart:subtotal' === $condition['type']) {
			$compare_value = (float) WC()->cart->get_subtotal();
		}

		if ('cart:total_quantity' === $condition['type']) {
			$compare_value = WC()->cart->get_cart_contents_count();
		}

		if ('cart:total_weight' === $condition['type']) {
			$compare_value = WC()->cart->cart_contents_weight;
		}

		$compare_value = apply_filters('advanced_rule_based_shipping/cart_compare_value', $compare_value, $condition);

		if ('equal_to' === $operator && $compare_value == $value_one) {
			return true;
		}

		if ('less_than' === $operator && $compare_value < $value_one) {
			return true;
		}

		if ('less_than_or_equal' === $operator && $compare_value <= $value_one) {
			return true;
		}

		if ('greater_than_or_equal' === $operator && $compare_value >= $value_one) {
			return true;
		}

		if ('greater_than' === $operator && $compare_value > $value_one) {
			return true;
		}

		if ('between' === $operator && $compare_value >= $value_one && $compare_value <= $value_two) {
			return true;
		}

		return $matched;
	}

	/**
	 * Coupon filter
	 * 
	 * @since 1.0.1
	 * @return boolean
	 */
	public function coupon_filters($matched, $condition) {
		if ('cart:coupons' !== $condition['type']) {
			return $matched;
		}

		$applied_coupons = WC()->cart->applied_coupons;
		if (empty($applied_coupons)) {
			return $matched;
		}

		if (isset($condition['coupons']) || is_array($condition['coupons'])) {
			$coupons = array_map(function ($coupon_id) {
				return get_post_field('post_name', $coupon_id);
			}, $condition['coupons']);

			$matched_coupons = array_intersect($coupons, $applied_coupons);
			if ('any_in_list' === $condition['operator'] && count($matched_coupons) > 0) {
				return true;
			}

			if ('not_in_list' === $condition['operator'] && count($matched_coupons) == 0) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Add common template of cart
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function common_templates() { ?>
		<template v-if="['cart:subtotal', 'cart:total_quantity', 'cart:total_weight'].includes(type)">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than_or_equal', 'greater_than', 'between')); ?>
			</select>

			<input type="number" v-model="value" placeholder="<?php echo '0.00'; ?>" step="0.001">
			<input v-if="operator == 'between'" type="number" v-model="value2" placeholder="<?php echo '0.00'; ?>" step="0.001">

			<?php do_action('advanced_rule_based_shipping/cart_common_fields') ?>
		</template>
	<?php
	}

	/**
	 * Coupon template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function coupon_template() {
		$values_map = Utils::get_select2_values_map(array(
			'model' => 'coupons',
			'hold_data' => 'hold_coupons',
			'data_type' => 'post_type:shop_coupon',
		)); ?>

		<template v-if="type == 'cart:coupons'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" v-model="coupons" ref="select2_ajax" multiple v-else data-values-map="<?php echo esc_attr(wp_json_encode($values_map)) ?>" data-placeholder="<?php esc_html_e('Coupons', 'advanced-rule-based-shipping'); ?>">
				<option v-for="coupon in get_ui_data_items('hold_coupons')" :value="coupon.id">{{coupon.name}}</option>
			</select>
		</template>
	<?php
	}

	/**
	 * Cart common fields
	 * 
	 * @since 1.0.1
	 * @return void
	 */
	public function cart_common_fields() { ?>
		<select v-model="cart_value_type">
			<option value="in_cart"><?php esc_html_e('In Cart', 'advanced-rule-based-shipping'); ?></option>
			<?php foreach (\Advanced_Rule_Based_Shipping\Utils::get_product_taxonomies() as $tax_key => $taxonomy_data) : ?>
				<option disabled><?php esc_html_e(sprintf(__('In %s (pro)', 'advanced-rule-based-shipping'), $taxonomy_data->label)); ?></option>
			<?php endforeach; ?>
		</select>
<?php
	}
}
