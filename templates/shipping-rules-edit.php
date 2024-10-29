<?php

use Advanced_Rule_Based_Shipping\Utils;

if (!defined('ABSPATH')) {
	exit;
}

$shipping_rule_data = array(
	'id' => $shipping_rule->id,
	'title' => $shipping_rule->title,
	'rules' => $shipping_rule->get_rules()
);

$title = __('Shipping Rule', 'advanced-rule-based-shipping');
if ($shipping_rule->exists()) {
	$title = __('Edit Shipping Rule', 'advanced-rule-based-shipping');
}

?>

<div class="wrap wrap-advanced-rule-based-shipping">

	<h1 class="wp-heading-inline"><?php echo esc_html($title) ?></h1>
	<a href="<?php menu_page_url('advanced-rule-based-shipping') ?>&id=new" class="page-title-action"><?php esc_html_e('Add New Shipping Rule', 'advanced-rule-based-shipping') ?></a>

	<hr class="wp-header-end">

	<form id="form-advanced-rule-based-shipping" method="post" data-settings="<?php echo esc_attr(wp_json_encode($shipping_rule_data)); ?>">
		<div id="titlediv">
			<input v-model="title" id="title" type="text" placeholder="<?php esc_attr_e('Shipping Rule Title', 'advanced-rule-based-shipping') ?>">
		</div>

		<input type="hidden" ref="nonce" value="<?php echo esc_attr(wp_create_nonce('_nonce_shipping_rule_form')) ?>">

		<div class="shipping-rule-error" v-if="error.length">{{error}}</div>

		<div class="shipping-rule-empty-container" v-if="rules.length == 0">
			<a class="btn-large-border" href="#" @click.prevent="add_new_rule()"><?php esc_html_e('Add your first rule', 'advanced-rule-based-shipping'); ?></a>
		</div>

		<div class="advance-rule-based-shipping-rules-container" v-sortable="{options: {handle: '.item-move-handle'}}" @end="onOrderChange">
			<shipping-rule v-for="(rule, index) in rules" :rule="rule" :rule-no="index" :key="rule.id"></shipping-rule>
		</div>

		<div class="advance-rule-based-shipping-footer">
			<button href="#" @click.prevent="add_new_rule()" class="button btn-add-new-rule">
				<?php esc_html_e('Add new rule', 'advanced-rule-based-shipping'); ?>
				<span class="dashicons dashicons-lock" v-if="rules.length >= get_free_rule_count && !has_pro()"></span>
			</button>
			<button @click.prevent="save_shipping_rule()" :class="get_button_classes()">
				<?php esc_html_e('Save Changes', 'advanced-rule-based-shipping'); ?>
			</button>
		</div>

		<?php if (!Utils::has_pro_installed()) : ?>
			<div id="advanced-rule-based-shipping-pro-modal" v-if="show_pro_modal">
				<div class="modal-body">
					<a @click.prevent="show_pro_modal = false" href="#" class="btn-modal-close dashicons dashicons-no-alt"></a>

					<span class="modal-icon dashicons dashicons-lock"></span>

					<div>
						<?php
						$text = sprintf(
							/* translators: %s for link */
							esc_html__('For adding more rules and updates, please get a pro version from %s.', 'advanced-rule-based-shipping'),
							'<a target="_blank" href="https://codiepress.com/plugins/advanced-rule-based-shipping-pro/?utm_campaign=advanced+rule+based+shipping&utm_source=shipping+rule&utm_medium=modal">' . esc_html__('here', 'advanced-rule-based-shipping') . '</a>'
						);

						echo wp_kses($text, array('a' => array('href' => true, 'target' => true)));
						?>
					</div>

					<div class="modal-footer">
						<a @click.prevent="show_pro_modal = false" class="button" href="#"><?php esc_html_e('Close', 'advanced-rule-based-shipping'); ?></a>
						<a @click="show_pro_modal = false" class="button button-get-pro" href="https://codiepress.com/plugins/advanced-rule-based-shipping-pro/?utm_campaign=advanced+rule+based+shipping&utm_source=shipping+rule&utm_medium=modal" target="_blank"><?php esc_html_e('Get Pro', 'advanced-rule-based-shipping'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if (Utils::has_pro_installed() && !Utils::is_pro_activated()) : ?>
			<div id="advanced-rule-based-shipping-pro-modal" v-if="show_pro_modal">
				<div class="modal-body">
					<a @click.prevent="show_pro_modal = false" href="#" class="btn-modal-close dashicons dashicons-no-alt"></a>

					<span class="modal-icon dashicons dashicons-lock"></span>

					<div class="pro-deactivated">
						<?php esc_html_e('Please activate the "Advanced Rule Based Shipping Pro" plugin on the plugins page.', 'advanced-rule-based-shipping'); ?>
					</div>

					<div class="modal-footer">
						<a @click.prevent="show_pro_modal = false" class="button" href="#"><?php esc_html_e('Close', 'advanced-rule-based-shipping'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if (Utils::is_pro_activated() && !Utils::license_activated()) : ?>
			<div id="advanced-rule-based-shipping-pro-modal" v-if="show_pro_modal">
				<div class="modal-body">
					<a @click.prevent="show_pro_modal = false" href="#" class="btn-modal-close dashicons dashicons-no-alt"></a>
					<span class="modal-icon dashicons dashicons-lock"></span>

					<?php
					printf(
						esc_html__('You need to activate the license key on the %sshipping rules%s page. After activating the license, please reload this page.', 'advanced-rule-based-shipping'),
						'<a target="_blank" href="' . menu_page_url('advanced-rule-based-shipping', false) . '">',
						'</a>',
					);

					?>

					<div class="modal-footer">
						<a @click.prevent="show_pro_modal = false" class="button" href="#"><?php esc_html_e('Close', 'advanced-rule-based-shipping'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>

	</form>
</div>