<?php

/**
 * Plugin Name: Advanced Rule Based Shipping
 * Description: Advanced Rule Based Shipping lets you set custom shipping costs based on cart total, weight, dates, addresses, and user roles.
 * Version: 1.0.4
 * Author: Repon Hossain
 * Author URI: https://workwithrepon.com
 * Text Domain: advanced-rule-based-shipping
 * 
 * Requires Plugins: woocommerce
 * Requires at least: 6.2.0
 * Requires PHP: 7.4.3
 * Tested up to: 6.6.2
 * 
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

define('ADVANCED_RULE_BASED_SHIPPING_FILE', __FILE__);
define('ADVANCED_RULE_BASED_SHIPPING_VERSION', '1.0.4');
define('ADVANCED_RULE_BASED_SHIPPING_BASENAME', plugin_basename(__FILE__));
define('ADVANCED_RULE_BASED_SHIPPING_URI', trailingslashit(plugins_url('/', __FILE__)));
define('ADVANCED_RULE_BASED_SHIPPING_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('ADVANCED_RULE_BASED_SHIPPING_PHP_MIN', '7.4.3');

define('ADVANCED_RULE_BASED_SHIPPING_API_URI', 'https://codiepress.com');
define('ADVANCED_RULE_BASED_SHIPPING_PLUGIN_ID', 737);

/**
 * Check PHP version. Show notice if version of PHP less than our 7.4.3 
 * 
 * @since 1.0.0
 * @return void
 */
function advanced_rule_based_shipping_php_missing_notice() {
	$notice = sprintf(
		/* translators: 1 for plugin name, 2 for PHP, 3 for PHP version */
		esc_html__('%1$s need %2$s version %3$s or greater.', 'advanced-rule-based-shipping'),
		'<strong>Advanced Rule Based Shipping for WooCommerce</strong>',
		'<strong>PHP</strong>',
		ADVANCED_RULE_BASED_SHIPPING_PHP_MIN
	);

	printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
}

/**
 * Admin notice for missing woocommerce
 * 
 * @since 1.0.0
 * @return void
 */
function advanced_rule_based_shipping_woocommerce_missing() {
	if (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
		$notice_title = __('Activate WooCommerce', 'advanced-rule-based-shipping');
		$notice_url = wp_nonce_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=all&paged=1', 'activate-plugin_woocommerce/woocommerce.php');
	} else {
		$notice_title = __('Install WooCommerce', 'advanced-rule-based-shipping');
		$notice_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
	}

	$notice = sprintf(
		/* translators: 1 for plugin name, 2 for WooCommerce, 3 WooCommerce link */
		esc_html__('%1$s need %2$s to be installed and activated to function properly. %3$s', 'advanced-rule-based-shipping'),
		'<strong>Advanced Rule Based Shipping for WooCommerce</strong>',
		'<strong>WooCommerce</strong>',
		'<a href="' . esc_url($notice_url) . '">' . $notice_title . '</a>'
	);

	printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
}

/**
 * Load our plugin main file of pass our plugin requirement
 * 
 * @since 1.0.0
 * @return void
 */
function advanced_rule_based_shipping_load_plugin() {
	if (version_compare(PHP_VERSION, ADVANCED_RULE_BASED_SHIPPING_PHP_MIN, '<')) {
		return add_action('admin_notices', 'advanced_rule_based_shipping_php_missing_notice');
	}

	//Check WooCommerce activate
	if (!class_exists('WooCommerce', false)) {
		return add_action('admin_notices', 'advanced_rule_based_shipping_woocommerce_missing');
	}

	require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/class-main.php';
}
add_action('plugins_loaded', 'advanced_rule_based_shipping_load_plugin');
