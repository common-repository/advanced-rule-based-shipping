<?php

namespace Advanced_Rule_Based_Shipping\Condition;

use Advanced_Rule_Based_Shipping\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Order history class
 */
final class Order_History {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('advanced_rule_based_shipping/condition_templates', array($this, 'first_purchase_template'));
	}

	/**
	 * Add first purchase template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function first_purchase_template() { ?>
		<div class="advanced-rule-based-shipping-locked-fields" v-if="type == 'order_history:first_purchase'">
			<select>
				<option value="yes"><?php esc_html_e('Yes', 'advanced-rule-based-shipping'); ?></option>
				<option value="no"><?php esc_html_e('No', 'advanced-rule-based-shipping'); ?></option>
			</select>

			<?php Utils::field_lock_message(); ?>
		</div>
<?php
	}
}
