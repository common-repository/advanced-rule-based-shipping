<?php

use Advanced_Rule_Based_Shipping\Utils;

if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="table-advanced-rule-based-shipping-rule">
    <tr class="rule-item-heading-row">
        <td colspan="2">
            <div class="rule-item-heading">
                <span class="item-move-handle dashicons dashicons-menu-alt"></span>

                <div class="rule-title">
                    {{get_header_title}}
                </div>

                <div class="empty-space-holder item-move-handle" @click="toggle_collapse()"></div>

                <div class="rule-item-actions">
                    <a class="dashicons dashicons-arrow-down-alt2" href="#" @click.prevent="toggle_collapse()" v-if="!collapse"></a>
                    <a class="dashicons dashicons-arrow-up-alt2" href="#" @click.prevent="toggle_collapse()" v-if="collapse"></a>
                    <a class="dashicons dashicons-admin-page" href="#" @click.prevent="duplicate_rule()"></a>
                    <a class="dashicons dashicons-trash" href="#" @click.prevent="delete_rule()"></a>
                </div>
            </div>
        </td>
    </tr>

    <template v-if="!collapse">
        <tr>
            <th><?php esc_html_e('Disable Rule', 'advanced-rule-based-shipping'); ?></th>
            <td>
                <label>
                    <input class="switch-checkbox" type="checkbox" v-model="disabled">
                    <?php esc_html_e('Yes', 'advanced-rule-based-shipping'); ?>
                </label>
            </td>
        </tr>

        <tr>
            <th class="vcenter"><?php esc_html_e('Active on', 'advanced-rule-based-shipping'); ?></th>
            <td>
                <select v-model="active_on">
                    <option value="immediately"><?php esc_html_e('Immediately', 'advanced-rule-based-shipping'); ?></option>
                    <option value="start_after_date"><?php esc_html_e('After', 'advanced-rule-based-shipping'); ?></option>
                </select>

                <input type="datetime-local" v-if="active_on == 'start_after_date'" v-model="start_after_date">
            </td>
        </tr>

        <tr>
            <th class="vcenter">
                <label :for="`shipping-method-title-${ruleNo}`">
                    <?php esc_html_e('Shipping Method Title', 'advanced-rule-based-shipping'); ?>
                </label>
            </th>
            <td>
                <input v-model="title" :id="`shipping-method-title-${ruleNo}`" class="full-width" type="text">
            </td>
        </tr>

        <tr>
            <th class="vcenter">
                <label :for="`shipping-method-description-${ruleNo}`">
                    <?php esc_html_e('Shipping Method Description', 'advanced-rule-based-shipping'); ?>
                </label>
            </th>
            <td>
                <textarea v-model="description" :id="`shipping-method-description-${ruleNo}`" class="full-width"></textarea>
            </td>
        </tr>

        <tr>
            <th class="vcenter">
                <label :for="`private-note-${ruleNo}`">
                    <?php esc_html_e('Private Note', 'advanced-rule-based-shipping'); ?>
                </label>
            </th>
            <td>
                <textarea v-model="private_note" :id="`private-note-${ruleNo}`" class="full-width"></textarea>
            </td>
        </tr>

        <tr>
            <th class="vcenter">
                <label :for="`shipping-cost-${ruleNo}`">
                    <?php esc_html_e('Shipping Cost', 'advanced-rule-based-shipping'); ?>
                </label>
            </th>
            <td>
                <select v-model="shipping_cost_type">
                    <?php foreach (Utils::get_shipping_cost_types() as $type => $type_label) : ?>
                        <option value="<?php echo esc_attr($type) ?>"><?php echo esc_html($type_label); ?></option>
                    <?php endforeach; ?>
                </select>

                <select v-model="shipping_cost_operator" v-if="['based_on_weight', 'based_on_quantity'].includes(shipping_cost_type)">
                    <?php foreach (Utils::get_shipping_cost_operators() as $operator_key => $operator_label) : ?>
                        <option value="<?php echo esc_attr($operator_key) ?>"><?php echo esc_html($operator_label); ?></option>
                    <?php endforeach; ?>
                </select>

                <input v-if="shipping_cost_type != 'free_shipping'" v-model="shipping_cost" :id="`shipping-cost-${ruleNo}`" placeholder="0.00" type="number" step="0.001">

                <template v-if="['based_on_weight', 'based_on_quantity'].includes(shipping_cost_type)">
                    <span style="margin-inline: 5px;font-size: 20px;position:relative;top:2px">+</span>
                    <input v-model="shipping_cost_extra_charge" title="<?php esc_html_e('Extra charge', 'advanced-rule-based-shipping'); ?>" placeholder="<?php esc_html_e('Extra charge', 'advanced-rule-based-shipping'); ?>" type="number" step="0.001" style="width: 120px">
                </template>
            </td>
        </tr>

        <tr v-if="['based_on_weight', 'based_on_quantity'].includes(shipping_cost_type)">
            <th class="vcenter">
                <?php esc_html_e('Min & Max Shipping Cost', 'advanced-rule-based-shipping'); ?>
            </th>
            <td>
                <input v-model="min_shipping_cost" placeholder="<?php echo esc_attr('Min', 'advanced-rule-based-shipping'); ?>" type="number" step="0.001" min="0" title="<?php esc_html_e('Allow minimum shipping cost.', 'advanced-rule-based-shipping'); ?>">
                <input v-model="max_shipping_cost" placeholder="<?php echo esc_attr('Max', 'advanced-rule-based-shipping'); ?>" type="number" step="0.001" min="0" title="<?php esc_html_e('Allow maximum shipping cost.', 'advanced-rule-based-shipping'); ?>">
            </td>
        </tr>

        <tr>
            <th :class="{vcenter: conditions.length === 0}">
                <?php esc_html_e('Conditions', 'advanced-rule-based-shipping'); ?>
                <div v-if="conditions.length > 0" class="field-note">
                    <?php
                    $condition_note = sprintf(
                        /* translators: %s link of contact page */
                        esc_html__('If you don\'t see the condition you want within the list, please get in touch with us %1$shere%2$s.', 'advanced-rule-based-shipping'),
                        '<a target="_blank" href="https://codiepress.com/contact/">',
                        '</a>'
                    );

                    echo wp_kses($condition_note, array('a' => array('href' => true, 'target' => true)));
                    ?>
                </div>
            </th>
            <td>
                <a class="btn-large-border" v-if="conditions.length === 0" href="#" @click.prevent="conditions.push({})">
                    <?php esc_html_e('Add a condition', 'advanced-rule-based-shipping'); ?>
                </a>

                <template v-else>
                    <shipping-rule-condition v-for="(item, number) in conditions" :key="item.id" :condition="item" :number="number" @delete="delete_condition(number)"></shipping-rule-condition>
                    <a class="button btn-add-condition" href="#" @click.prevent="conditions.push({})"><?php esc_html_e('Add new condition', 'advanced-rule-based-shipping'); ?></a>
                </template>
            </td>
        </tr>

        <tr v-if="conditions.length > 1">
            <th class="vcenter"><?php esc_html_e('Conditions Relationship', 'advanced-rule-based-shipping'); ?></th>
            <td>
                <div class="condition-relationship-options">
                    <label>
                        <input type="radio" value="match_all" v-model="condition_relationship">
                        <?php esc_html_e('Match All', 'advanced-rule-based-shipping'); ?>
                    </label>

                    <label>
                        <input type="radio" value="match_any" v-model="condition_relationship">
                        <?php esc_html_e('Match Any', 'advanced-rule-based-shipping'); ?>
                    </label>
                </div>
            </td>
        </tr>
    </template>
</table>