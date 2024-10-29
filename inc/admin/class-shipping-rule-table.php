<?php

namespace Advanced_Rule_Based_Shipping\Admin;

use Advanced_Rule_Based_Shipping\Shipping_Rule;
use Advanced_Rule_Based_Shipping\Utils;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * WP List table of Shipping rules
 */
class Shipping_Rules_Table extends \WP_List_Table {
	/**
	 * Entry per page
	 * 
	 * @since 1.0.0
	 */
	public $per_page = 15;

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->per_page = $this->get_items_per_page('shipping_rules_per_page', 15);
		parent::__construct(array('singular' => 'advanced_rule_based_shipping_rule_table', 'plural' => 'advanced_rule_based_shipping_rules_table', 'ajax' => false));
	}

	/**
	 * Prepare the items for the table to process
	 * 
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$this->_column_headers = array($this->get_columns());

		$query = new \WP_Query(array(
			'paged' => $this->get_pagenum(),
			'posts_per_page' => $this->per_page,
			'post_type' => Shipping_Rule::POST_TYPE,
			'post_status' => 'any'
		));

		$shipping_rules = array_map(function ($post) {
			return new Shipping_Rule($post->ID);
		}, $query->posts);

		$this->items = $shipping_rules;

		$this->set_pagination_args(array(
			'total_items' => $query->found_posts,
			'per_page'    => $this->per_page
		));
	}

	/**
	 * Set bulk action for table
	 * 
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => __('Delete', 'advanced-rule-based-shipping'),
		];

		return $actions;
	}

	/**
	 * Get all available column of table
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb' => '<input type="checkbox" />',
			'title' => __('Shipping Rule Title', 'advanced-rule-based-shipping'),
			'rules' => __('Rules', 'advanced-rule-based-shipping'),
			'attached_shipping_rates' => __('Attached Shipping Rates', 'advanced-rule-based-shipping'),
			'created_on' => __('Created Date', 'advanced-rule-based-shipping'),
		];
	}

	/**
	 * Define what data to show on each column of the table
	 * 
	 * @param  String $column_name - Current column name
	 * @since 1.0.0
	 */
	public function column_default($shipping_rule, $column_name) {
	}

	/**
	 * Checkbox column 
	 * 
	 * @since 1.0.0
	 */
	public function column_cb($shipping_rule) {
		$zone_rates = $shipping_rule->get_attached_shipping_rates();

		$disabled = '';
		if (count($zone_rates) > 0) {
			$disabled = 'disabled="disabled"';
		}

		return sprintf('<input type="checkbox" name="shipping-rules[]" value="%d" %s />', $shipping_rule->get_id(), $disabled);
	}

	/**
	 * Title column 
	 * 
	 * @since 1.0.0
	 */
	public function column_title($shipping_rule) {
		$edit_url = add_query_arg('id', $shipping_rule->get_id(), menu_page_url('advanced-rule-based-shipping', false));
		printf('<strong><a class="row-title" href="%s">%s</a></strong>', esc_url($edit_url), esc_html($shipping_rule->title));

		$menu_page = menu_page_url('advanced-rule-based-shipping', false);
		$delete_url = add_query_arg(array('id' => $shipping_rule->get_id(), 'delete' => wp_create_nonce('_nonce_delete_advanced_rule_based_shipping_rule_' . $shipping_rule->get_id())), $menu_page);

		$row_actions[] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'advanced-rule-based-shipping'));

		$clone_url = add_query_arg(array('id' => $shipping_rule->get_id(), 'clone' => wp_create_nonce('_nonce_clone_advanced_rule_based_shipping_rule_' . $shipping_rule->get_id())), $menu_page);
		$row_actions[] = sprintf('<a href="%s">%s</a>', esc_url($clone_url), __('Clone', 'advanced-rule-based-shipping'));

		$zone_rates = $shipping_rule->get_attached_shipping_rates();
		$delete_button_class = '';
		if (count($zone_rates) > 0) {
			$delete_button_class = 'delete-restrict-rule';
		}

		$row_actions[] = sprintf('<a href="%s" class="delete-shipping-rule %s">%s</a>', esc_url($delete_url), $delete_button_class, __('Delete', 'advanced-rule-based-shipping'));

		echo '<div class="row-actions">' . wp_kses_post(implode(' | ', $row_actions)) . '</div>';
	}

	/**
	 * Show all rules of current item
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_rules($shipping_rule) {
		$active_rules = $shipping_rule->get_active_rules();

		$shipping_cost_types = Utils::get_shipping_cost_types();

		$rules_line = array();
		foreach ($active_rules as $rule) {
			$shipping_cost_data = wc_price($rule['shipping_cost']);
			if ('free_shipping' == $rule['shipping_cost_type']) {
				$shipping_cost_data = wc_price(0.00);
			}

			if (!in_array($rule['shipping_cost_type'], array('free_shipping', 'fiexed_amount'))) {
				$shipping_cost_data = $rule['shipping_cost'] . ' + ' . wc_price($rule['shipping_cost_extra_charge']);
			}

			$cost_type = $rule['shipping_cost_type'];
			$below_line = isset($shipping_cost_types[$cost_type]) ? $shipping_cost_types[$cost_type] : '';
			$rules_line[] = sprintf('<li>%s (%s) <div class="shipping-cost-type">%s</div></li>', $rule['title'], $shipping_cost_data, $below_line);
		}

		if (count($rules_line) === 0) {
			$rules_line[] = '<li>' . esc_html__('No rule', 'advanced-rule-based-shipping') . '</li>';
		}

		echo '<ul class="available-rules">' . wp_kses_post(implode('', $rules_line)) . '</ul>';
	}

	/**
	 * Show attached shipping rates
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_attached_shipping_rates($shipping_rule) {
		$shipping_rates = $shipping_rule->get_attached_shipping_rates();

		echo '<div class="attached-shipping-rates">';
		foreach ($shipping_rates as $zone_name => $shipping_methods) {
			echo '<h4>' . esc_html($zone_name) . '</h4>';
			echo '<ul class="shipping-rates">';
			foreach ($shipping_methods as $shipping_method) {
				echo '<li>' . esc_html($shipping_method->get_option('name')) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	/**
	 * Create on column 
	 * 
	 * @since 1.0.0
	 */
	public function column_created_on($shipping_rule) {
		$created_timestamp = strtotime(wp_date('Y-m-d H:i:s', strtotime($shipping_rule->get_meta('post_date'))));

		$readable_diff_time = strtotime(wp_date('Y-m-d H:i:s', strtotime('-3days')));
		if ($created_timestamp > $readable_diff_time) {
			echo wp_kses_post(human_time_diff($created_timestamp, current_time('timestamp')) . ' ago<br>');
		}

		printf(
			'%s at %s',
			esc_html(gmdate(get_option('date_format'), $created_timestamp)),
			esc_html(gmdate(get_option('time_format'), $created_timestamp))
		);
	}
}
