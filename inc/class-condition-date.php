<?php

namespace Advanced_Rule_Based_Shipping\Condition;

use Advanced_Rule_Based_Shipping\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Date Condition class
 */
final class Date {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'filters'), 10, 2);
		add_filter('advanced_rule_based_shipping/condition_values', array($this, 'condition_values'));

		add_action('advanced_rule_based_shipping/condition_templates', array($this, 'weekly_days'));
		add_action('advanced_rule_based_shipping/condition_templates', array($this, 'time_template'));
		add_action('advanced_rule_based_shipping/condition_templates', array($this, 'date_template'));
	}

	/**
	 * Date condition values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function condition_values($values) {
		return array_merge($values, array(
			'time_one' => '',
			'time_two' => '',
			'date_one' => '',
			'date_two' => '',
			'weekly_days' => [],
		));
	}


	/**
	 * Date related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function filters($matched, $condition) {
		$operator = $condition['operator'];

		if ('date:weekly_days' === $condition['type']) {
			$weekly_days = isset($condition['weekly_days']) && is_array($condition['weekly_days']) ? $condition['weekly_days'] : array();
			$current_day = strtolower(current_time('l'));

			if ('any_in_list' == $operator && in_array($current_day, $weekly_days)) {
				return true;
			}

			if ('not_in_list' == $operator && !in_array($current_day, $weekly_days)) {
				return true;
			}
		}

		if ('date:time' === $condition['type']) {
			$time_one = strtotime($condition['time_one']);
			if (false === $time_one) {
				return $matched;
			}

			if ('before' === $operator) {
				return current_time('timestamp') < $time_one;
			}

			if ('after' === $operator) {
				return current_time('timestamp') > $time_one;
			}

			if ('between' === $operator) {
				$time_two = strtotime($condition['time_two']);
				if (false === $time_two) {
					return $matched;
				}

				$current_time = current_time('timestamp');

				return ($current_time >= $time_one && $current_time <= $time_two);
			}
		}

		if ('date:date' === $condition['type']) {
			$date_one = strtotime($condition['date_one']);
			if (false === $date_one) {
				return $matched;
			}

			if ('before' === $operator) {
				return current_time('timestamp') < $date_one;
			}

			if ('after' === $operator) {
				return current_time('timestamp') > $date_one;
			}

			if ('between' === $operator) {
				$date_two = strtotime($condition['date_two']);
				if (false === $date_two) {
					return $matched;
				}

				$current_time = current_time('timestamp');
				return ($current_time >= $date_one && $current_time <= $date_two);
			}
		}

		return $matched;
	}

	/**
	 * Add weekly days template of condition
	 * 
	 * @since 1.0.3
	 * @return void
	 */
	public function time_template() { ?>
		<template v-if="type == 'date:time'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('before', 'after', 'between')); ?>
			</select>

			<input type="time" v-model="time_one">
			<input type="time" v-model="time_two" v-if="operator == 'between'">
		</template>
	<?php
	}

	/**
	 * Add date template
	 * 
	 * @since 1.0.3
	 * @return void
	 */
	public function date_template() { ?>
		<template v-if="type == 'date:date'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('before', 'after', 'between')); ?>
			</select>

			<input type="datetime-local" v-model="date_one">
			<input type="datetime-local" v-model="date_two" v-if="operator == 'between'">
		</template>
	<?php
	}

	/**
	 * Add weekly days template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function weekly_days() { ?>
		<template v-if="type == 'date:weekly_days'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select v-model="weekly_days" data-model="weekly_days" ref="select2_dropdown" data-placeholder="<?php esc_attr_e('Select days', 'advanced-rule-based-shipping'); ?>" multiple>
				<option value="sunday"><?php esc_html_e('Sunday', 'advanced-rule-based-shipping'); ?></option>
				<option value="monday"><?php esc_html_e('Monday', 'advanced-rule-based-shipping'); ?></option>
				<option value="tuesday"><?php esc_html_e('Tuesday', 'advanced-rule-based-shipping'); ?></option>
				<option value="wednesday"><?php esc_html_e('Wednesday', 'advanced-rule-based-shipping'); ?></option>
				<option value="thursday"><?php esc_html_e('Thursday', 'advanced-rule-based-shipping'); ?></option>
				<option value="friday"><?php esc_html_e('Friday', 'advanced-rule-based-shipping'); ?></option>
				<option value="saturday"><?php esc_html_e('Saturday', 'advanced-rule-based-shipping'); ?></option>
			</select>
		</template>
<?php
	}
}
