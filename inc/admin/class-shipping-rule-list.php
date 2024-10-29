<?php

namespace Advanced_Rule_Based_Shipping\Admin;

use Advanced_Rule_Based_Shipping\Shipping_Rule;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shipping Rule List
 */
final class Shipping_Rule_List {

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('init', array($this, 'handle_delete'));
		add_action('init', array($this, 'handle_clone'));
		add_action('init', array($this, 'process_bulk_delete_action'));
		add_filter('set-screen-option', array(__CLASS__, 'set_screen'), 20, 3);
	}

	/**
	 * Handle delete of shipping rule item
	 * 
	 * @since 1.0.0
	 */
	public function handle_delete() {
		if (!isset($_GET['id']) || !isset($_GET['delete'])) {
			return;
		}

		$shipping_rule_id = absint($_GET['id']);
		if (!wp_verify_nonce(wp_unslash($_GET['delete']), '_nonce_delete_advanced_rule_based_shipping_rule_' .  $shipping_rule_id)) {
			return;
		}

		wp_delete_post($shipping_rule_id);
		wp_safe_redirect(remove_query_arg(array('id', 'delete'))); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}


	/**
	 * Close shipping rule
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_clone() {
		if (!isset($_GET['id']) || !isset($_GET['clone'])) {
			return;
		}

		$shipping_rule_id = absint($_GET['id']);
		if (!wp_verify_nonce(wp_unslash($_GET['clone']), '_nonce_clone_advanced_rule_based_shipping_rule_' .  $shipping_rule_id)) {
			return;
		}

		$shipping_rule = new Shipping_Rule($shipping_rule_id);
		$shipping_rule->id = 0;
		$shipping_rule->title = $shipping_rule->title . ' - ' . esc_html__('copy', 'advanced-rule-based-shipping');
		$shipping_rule->post->post_date = gmdate('Y-m-d H:i:s');

		$shipping_rule->save();
		wp_safe_redirect(remove_query_arg(array('id', 'clone'))); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Set screen option $value.
	 * 
	 * @since 1.0.0
	 */
	public static function set_screen($status, $option, $value) {
		return $value;
	}

	/**
	 * Handle bulk delete action
	 * 
	 * @since 1.0.0
	 */
	public function process_bulk_delete_action() {
		if (wp_doing_ajax() || empty($_POST['shipping-rules']) || !is_array($_POST['shipping-rules'])) {
			return;
		}

		if (empty($_POST['_wpnonce']) || empty($_POST['action'])) {
			return;
		}

		$action = 'bulk-advanced_rule_based_shipping_rules_table';
		if (!wp_verify_nonce(wp_unslash($_POST['_wpnonce']), $action)) {
			return;
		}

		if ('bulk-delete' !== wp_unslash($_POST['action'])) {
			return;
		}

		$shipping_rules = array_map('absint', $_POST['shipping-rules']);
		foreach ($shipping_rules as $shipping_rule_id) {
			$shipping_rule = new Shipping_Rule($shipping_rule_id);
			$zone_rates = $shipping_rule->get_attached_shipping_rates();
			if (count($zone_rates) > 0) {
				continue;
			}

			if ($shipping_rule->exists()) {
				wp_delete_post($shipping_rule_id);
			}
		}
	}

	/**
	 * Add options for screen setting.
	 * 
	 * @since 1.0.0
	 */
	public function screen_option() {
		add_screen_option('per_page', [
			'label' => __('Rules Per Page', 'advanced-rule-based-shipping'),
			'default' => 15,
			'option' => 'shipping_rules_per_page'
		]);
	}

	/**
	 * Show list of quote in table
	 * 
	 * @since 1.0.0
	 */
	public function list_page() {
		$quotes_table = new Shipping_Rules_Table();
		$quotes_table->prepare_items();

		echo '<div class="wrap wrap-advanced-rule-based-shipping">';
		echo '<h1 class="wp-heading-inline">' . esc_html__('Shipping Rules', 'advanced-rule-based-shipping') . '</h1>';
		echo '<a href="' . menu_page_url('advanced-rule-based-shipping', false) . '&id=new" class="page-title-action">' . esc_html__('Add New Shipping Rule', 'advanced-rule-based-shipping') . '</a>';
		echo '<hr class="wp-header-end">';

		do_action('advanced_rule_based_shipping/before_shipping_rule_list');

		echo '<form method="post">';
		$quotes_table->display();
		echo '</form>';
		echo '</div>';
	}
}
