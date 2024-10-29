<?php

if (!defined('ABSPATH')) {
	exit;
}

use Advanced_Rule_Based_Shipping\Utils;

$condition_groups = Utils::get_condition_groups(); ?>

<table class="advanced-rule-based-shipping-condition-item">
	<tr>
		<td class="condition-type-field-column" style="vertical-align: top;">
			<select v-model="type">
				<?php
				foreach ($condition_groups as $group_key => $group_label) {
					$conditions = Utils::get_conditions_by_group($group_key);
					if (count($conditions) == 0) {
						continue;
					}

					echo '<optgroup label="' . esc_attr($group_label) . '">';
					foreach ($conditions as $key => $condition) {
						echo '<option value="' . esc_attr($key) . '">' . esc_html($condition['label']) . ' </option>';
					}
					echo '</optgroup>';
				}
				?>
			</select>
		</td>

		<td class="condition-fields-column">
			<?php do_action('advanced_rule_based_shipping/condition_templates'); ?>
			<a href="#" class="btn-condition-delete dashicons dashicons-no-alt" @click.prevent="delete_item()"></a>
		</td>
	</tr>
</table>