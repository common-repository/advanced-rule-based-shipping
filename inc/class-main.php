<?php

namespace Advanced_Rule_Based_Shipping;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class plugin
 */
final class Main {

	/**
	 * Hold the current instance of plugin
	 * 
	 * @since 1.0.0
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Main
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold admin class
	 * 
	 * @since 1.0.0
	 * @var Admin
	 */
	public $admin = null;

	/**
	 * Conditions classes
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $conditions = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->include_files();
		$this->init();
		$this->hooks();
	}

	/**
	 * Load plugin files
	 * 
	 * @version 1.0.0
	 * @return void
	 */
	public function include_files() {
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-utils.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-shipping-rule.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-admin.php';

		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-condition-cart.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-condition-date.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-condition-user.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-condition-cart-products.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-condition-order-history.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-condition-billing-shipping.php';
	}

	/**
	 * Initialize classes
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		$this->admin = new Admin();
		$this->conditions['cart'] = new Condition\Cart();
		$this->conditions['date'] = new Condition\Date();
		$this->conditions['user'] = new Condition\User();
		$this->conditions['cart_products'] = new Condition\Cart_Products();
		$this->conditions['order_history'] = new Condition\Order_History();
		$this->conditions['billing_shipping'] = new Condition\Billing_Shipping();
	}

	/**
	 * Add hooks of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		add_action('init', array($this, 'register_shipping_rule_post_type'));
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
		add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
		add_action('woocommerce_after_shipping_rate', array($this, 'shipping_rate_description'));
	}

	/**
	 * Add Shipping Rules link in plugin links
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (ADVANCED_RULE_BASED_SHIPPING_BASENAME == $plugin_file) {
			$new_links[] = sprintf('<a href="%s">%s</a>', menu_page_url('advanced-rule-based-shipping', false), __('Shipping Rules', 'advanced-rule-based-shipping'));
			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Register post type for shipping rules
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_shipping_rule_post_type() {
		register_post_type(Shipping_Rule::POST_TYPE, array(
			'public' => false,
		));
	}

	/**
	 * Register the shipping method to WooCommerce.
	 * 
	 * @since 1.0.0
	 * @param array $methods List of shipping methods.
	 * @return array List of modified shipping methods.
	 */
	public function add_shipping_method($methods) {
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-shipping-method.php';
		$methods['advanced_rule_based_shipping'] = '\Advanced_Rule_Based_Shipping\Shipping_Method';
		return $methods;
	}

	/**
	 * Add description below the shipping rate
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_rate_description($shipping_rate) {
		if ($shipping_rate->get_method_id() !== 'advanced_rule_based_shipping') {
			return;
		}

		$method = new Shipping_Method($shipping_rate->get_instance_id());

		$description = $method->get_description();
		if (!empty($description)) {
			echo '<div class="advanced-rule-based-shipping-method-description">' . wp_kses_post($description) . '</div>';
		}
	}
}

Main::get_instance();
