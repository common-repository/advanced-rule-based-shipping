<?php

namespace Advanced_Rule_Based_Shipping;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin class of the plugin
 */
final class Admin {

	public $shipping_list = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->include_files();
		$this->hooks();
	}

	/**
	 * Include files for admin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function include_files() {
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/admin/class-shipping-rule-list.php';
		require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'inc/admin/class-shipping-rule-table.php';
	}

	/**
	 * Include files for admin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		$this->shipping_list = new Admin\Shipping_Rule_List();
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_footer', array($this, 'add_component'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 100);
		add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 1);
		add_action('wp_ajax_advanced_rule_based_shipping/save_shipping_rule', array($this, 'save_shipping_rule'));
		add_action('wp_ajax_advanced_rule_based_shipping/get_select2_data', array($this, 'get_select2_data'));
	}

	/**
	 * Add menu page for shipping rules
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_menu() {
		$shipping_rule_menu = add_submenu_page(
			'woocommerce',
			__('Shipping Rules', 'advanced-rule-based-shipping'),
			__('Shipping Rules', 'advanced-rule-based-shipping'),
			'manage_options',
			'advanced-rule-based-shipping',
			array($this, 'shipping_rules_screen'),
		);

		if (!isset($_GET['id'])) {
			add_action("load-$shipping_rule_menu", array($this->shipping_list, 'screen_option'));
		}
	}

	/**
	 * Save shipping rule
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_shipping_rule() {
		check_ajax_referer('_nonce_shipping_rule_form');

		$shipping_rule_id = 0;
		if (isset($_POST['id'])) {
			$shipping_rule_id = absint($_POST['id']);
		}

		$shipping_rule = new Shipping_Rule($shipping_rule_id);
		if (isset($_POST['title'])) {
			$shipping_rule->title = wp_unslash($_POST['title']);
		}

		if (isset($_POST['rules'])) {
			$rules = json_decode(wp_unslash($_POST['rules']), true);
			if (is_array($rules)) {
				$shipping_rule->rules = $rules;
			}
		}

		$post_id = $shipping_rule->save();

		if ($post_id > 0) {
			wp_send_json_success(array(
				'redirect' => !$shipping_rule->exists(),
				'redirect_url' => add_query_arg('id', $post_id, admin_url('admin.php?page=advanced-rule-based-shipping'))
			));
		}

		wp_send_json_error();
	}

	/**
	 * Register styles and scripts
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_scripts() {
		if (defined('CODIEPRESS_DEVELOPMENT')) {
			wp_register_script('advanced-rule-based-shipping-vue', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/vue.js', [], '3.5.12', true);
		} else {
			wp_register_script('advanced-rule-based-shipping-vue', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/vue.min.js', [], '3.5.12', true);
		}
	}

	/**
	 * Enqueue script on backend
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if (get_current_screen()->id !== 'woocommerce_page_advanced-rule-based-shipping') {
			return;
		}

		wp_register_script('sortable', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/sortable.min.js', [], '1.15.2', true);
		wp_register_script('vue-sortable', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/vue-sortable.js', ['advanced-rule-based-shipping-vue', 'sortable'], '1.0.7', true);

		$wc_countries = new \WC_Countries();

		wp_register_style('select2', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/select2.min.css');
		wp_enqueue_style('advanced-rule-based-shipping', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/admin.min.css', ['select2'], ADVANCED_RULE_BASED_SHIPPING_VERSION);

		do_action('advanced_rule_based_shipping/admin_enqueue_scripts');

		wp_enqueue_script('advanced-rule-based-shipping', ADVANCED_RULE_BASED_SHIPPING_URI . 'assets/admin.min.js', ['jquery', 'advanced-rule-based-shipping-vue', 'select2', 'vue-sortable'], ADVANCED_RULE_BASED_SHIPPING_VERSION, true);
		wp_localize_script('advanced-rule-based-shipping', 'advanced_rule_based_shipping_admin', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'countries' => $wc_countries->get_countries(),
			'rule_values' => Utils::get_rule_values(),
			'condition_values' => Utils::get_condition_values(),
			'condition_ui_values' => Utils::get_condition_ui_values(),
			'select2_values_map' => Utils::get_select2_values_map(),
			'shipping_cost_types' => Utils::get_shipping_cost_types(),
			'nonce_select2_data' => wp_create_nonce('_nonce_advanced_rule_based_shipping/get_select2_data'),
			'i10n' => array(
				'copy' => __('copy', 'advanced-rule-based-shipping'),
				'delete_rule_warning' => __('Do you want to delete this shipping rule?', 'advanced-rule-based-shipping'),
				'delete_condition_warning' => __('Do you want to delete this condition?', 'advanced-rule-based-shipping'),
				'restrict_delete_rule' => __('Shipping method attached with this shipping rule. You can\'t delete this rule. Please remove this shipping rule from shipping methods before deleting.', 'advanced-rule-based-shipping'),
				'delete_shipping_rule_item_warning' => __('Do you want to delete this rule item?', 'advanced-rule-based-shipping'),
				'error_shipping_rule_title_missing' => __('Please enter shipping rule title.', 'advanced-rule-based-shipping'),
				'error_title_missing' => __('Please enter shipping method title of rule.', 'advanced-rule-based-shipping'),
			)
		));
	}

	/**
	 * Admin screen of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_rules_screen() {
		if (isset($_GET['id'])) {
			$shipping_rule = new Shipping_Rule(absint($_GET['id']));
			require_once ADVANCED_RULE_BASED_SHIPPING_PATH . 'templates/shipping-rules-edit.php';
		} else {
			$this->shipping_list->list_page();
		}
	}

	/**
	 * Add vuejs component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component() {
		if (get_current_screen()->id !== 'woocommerce_page_advanced-rule-based-shipping') {
			return;
		}

		echo '<template id="component-shipping-rule-item">';
		include_once ADVANCED_RULE_BASED_SHIPPING_PATH . '/templates/shipping-rule-item.php';
		echo '</template>';

		echo '<template id="component-shipping-rule-condition">';
		include_once ADVANCED_RULE_BASED_SHIPPING_PATH . '/templates/shipping-rule-condition.php';
		echo '</template>';
	}

	/**
	 * Get select2 dropdown data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_select2_data() {
		check_ajax_referer('_nonce_advanced_rule_based_shipping/get_select2_data', 'security');
		$results = $search_args = array();

		$search_term = !empty($_POST['term']) ? sanitize_text_field($_POST['term'])  : '';
		$query_type = !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
		$query_type = explode(':', $query_type);

		$object_type = !empty($query_type[0]) ? $query_type[0] : '';
		$object_slug = !empty($query_type[1]) ? $query_type[1] : '';

		if ('taxonomy' == $object_type && !empty($object_slug)) {
			$search_args = array('hide_empty' => false, 'taxonomy' => $object_slug);

			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['ids']) && is_array($_POST['ids'])) {
				$search_args['include'] = array_map('absint', $_POST['ids']);
			}

			$terms = get_terms($search_args);

			$results = array_map(function ($term) {
				return array('id' => $term->term_id, 'name' => $term->name);
			}, $terms);
		}

		if ('users' == $object_type) {
			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['ids']) && is_array($_POST['ids'])) {
				$search_args['include'] = array_map('absint', $_POST['ids']);
			}

			$get_users = get_users($search_args);
			$results = array_map(function ($user) {
				return array('id' => $user->id, 'name' => $user->display_name);
			}, $get_users);
		}

		if ('states' == $object_type) {
			if (empty($_POST['country'])) {
				wp_send_json_error(array(
					'error' => esc_html__('Country Missing', 'advanced-rule-based-shipping')
				));
			}

			$wc_countries = new \WC_Countries();
			$states = $wc_countries->get_states(sanitize_text_field($_POST['country']));

			if (!empty($search_term)) {
				$states = array_filter($states, function ($state) use ($search_term) {
					return stripos($state, $search_term) !== false;
				});
			}

			if (!is_array($states)) {
				$states = [];
			}

			$results = array_map(function ($state, $code) {
				return array('id' => $code, 'name' => html_entity_decode($state));
			}, $states, array_keys($states));
		}

		if ('post_type' == $object_type && !empty($object_slug)) {
			$search_args['post_type'] = $object_slug;
			if (!empty($search_term)) {
				$search_args['s'] = $search_term;
			}

			if (isset($_POST['ids']) && is_array($_POST['ids'])) {
				$search_args['post__in'] = array_map('absint', $_POST['ids']);
			}

			$posts = get_posts($search_args);
			$results = array_map(function ($item) {
				return array('id' => $item->ID, 'name' => $item->post_title);
			}, $posts);
		}

		do_action('advanced_rule_based_shipping/get_select2_data', $query_type, $search_term);

		wp_send_json_success($results);
	}
}
