<?php
/** 
 * OPCW Rule Item Block
 * 
 * @uses condition_item
 * @uses index
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly    

$rule_item = !empty($condition_item['rule_item']) ? $condition_item['rule_item'] : '';
$rule_relation = !empty($condition_item['rule_relation']) ? $condition_item['rule_relation'] : '';

$default_val = in_array($rule_item, ['product_category', 'product_tag']) ? [] : '';
$rule_value = !empty($condition_item['rule_value']) ? $condition_item['rule_value'] : $default_val;

if (is_array($rule_value)) {
    $select_val = [];
    foreach ($rule_value as $id) {
        $term = get_term($id);
        $select_val[$id] = (is_wp_error($term) || !$term) ? $id : $term->name;
    }
}
?>
<ul class="opcw_rules_box">
    <li class="option_item opcw_group_settings_mt opcw_rule_item_wrap">
        <?php
        woocommerce_wp_select(
            array(
                'id'          => 'rule_item_'.$index,
                'value'       => $rule_item,
                'label'       => __( 'Rule aplly for:', 'opal-product-collection-woocommerce' ),
                'options'     => [
                    '' => __('Select rule', 'opal-product-collection-woocommerce'),
                    'product_title' => __( '[Product title]', 'opal-product-collection-woocommerce' ),
                    'product_type' => __( '[Product type]', 'opal-product-collection-woocommerce' ),
                    'product_category' => __( '[Product category]', 'opal-product-collection-woocommerce' ),
                    'product_tag' => __( '[Product tag]', 'opal-product-collection-woocommerce' ),
                    'stock_status' => __( '[Stock status]', 'opal-product-collection-woocommerce' ),
                    'price' => __( '[Price]', 'opal-product-collection-woocommerce' ),
                    'attribute' => __( '[Attributes/Variations]', 'opal-product-collection-woocommerce' ),
                ],
                'wrapper_class' => 'opcw_setting_form', 
                'class' => 'opcw_setting_field opcw_rule_item',
                'style' => 'width:100%;margin-left:0',
                'custom_attributes' => [
                    'data-pattern-name' => 'rule_item_++',
                    'data-pattern-id' => 'rule_item_++',
                    'field-type' => 'condition'
                ]
            )
        );
        ?>
    </li>
    <li class="option_item opcw_group_settings_mt opcw_rule_relation_wrap">
        <?php
        woocommerce_wp_select(
            array(
                'id'          => 'rule_relation_'.$index,
                'value'       => $rule_relation,
                'label'       => __( 'Relation:', 'opal-product-collection-woocommerce' ),
                'options'     => [
                    '' => __('Select relation', 'opal-product-collection-woocommerce'),
                    'is' => __('Is', 'opal-product-collection-woocommerce'),
					'is_not' => __('Is Not', 'opal-product-collection-woocommerce'),
                    'have' => __('Have', 'opal-product-collection-woocommerce'),
					'not_have' => __('Not Have', 'opal-product-collection-woocommerce'),
					'is_in' => __('Is In', 'opal-product-collection-woocommerce'),
					'is_not_in' => __('Is Not In', 'opal-product-collection-woocommerce'),
					'is_empty' => __('Is Empty', 'opal-product-collection-woocommerce'),
					'is_not_empty' => __('Is Not Empty', 'opal-product-collection-woocommerce'),
					'is_greater' => __('Is Greater Than', 'opal-product-collection-woocommerce'),
					'is_lessthan' => __('Is Less Than', 'opal-product-collection-woocommerce'),
					'is_greater_or_equal' => __('Is Greater Than Or Equal', 'opal-product-collection-woocommerce'),
					'is_lessthan_or_equal' => __('Is Less Than Or Equal', 'opal-product-collection-woocommerce'),
					'contains' => __('Text Contains', 'opal-product-collection-woocommerce'),
					'not_contains' => __('Text Not Contains', 'opal-product-collection-woocommerce'),
					'starts_with' => __('Text Starts With', 'opal-product-collection-woocommerce'),
					'ends_with' => __('Text Ends With', 'opal-product-collection-woocommerce')
                ],
                'wrapper_class' => 'opcw_setting_form', 
                'class' => 'opcw_setting_field opcw_rule_relation',
                'style' => 'width:100%;margin-left:0',
                'custom_attributes' => [
                    'data-pattern-name' => 'rule_relation_++',
                    'data-pattern-id' => 'rule_relation_++',
                    'field-type' => 'relation'
                ]
            )
        );
        ?>
    </li>
    <li class="option_item opcw_group_settings_mt opcw_rule_value_wrap">
        <?php
        $value_id = 'rule_value_'.$index;
        $pattern_id = 'rule_value_++';
        if (in_array($rule_item, ['product_title', 'price', ''])) {
            woocommerce_wp_text_input(
                array(
                    'id'          => $value_id,
                    'class' => 'opcw_setting_field opcw_rule_value',
                    'wrapper_class' => 'opcw_setting_form',
                    'label'       => esc_html__( 'Value: ', 'opal-product-collection-woocommerce' ),
                    'placeholder' => 'Value',
                    'value'       => $rule_value,
                    'style' => 'display: block',
                    'custom_attributes' => [
                        'data-pattern-name' => $pattern_id,
                        'data-pattern-id' => $pattern_id,
                    ]
                )
            );
        }
        else {
            $class = 'opcw_setting_field opcw_rule_value';
            $multiple = false;
            $custom_attributes = [
                'data-pattern-name' => $pattern_id,
                'data-pattern-id' => $pattern_id,
                'placeholder' => 'Value',
            ];
            if ($rule_item == 'stock_status') {
                $rule_option_value = wc_get_product_stock_status_options();
            }
            elseif ($rule_item == 'product_type') {
                $rule_option_value = wc_get_product_types();
            }
            elseif ($rule_item == 'attribute') {
                $rule_option_value = opcw_get_all_variants();
            }
            else {
                $rule_option_value = isset($select_val) ? $select_val : [];
                $class .= ' opcw_init_select2';
                $value_id = 'rule_value_'.$index.'[]';
                $custom_attributes['multiple'] = "multiple";
            }
            woocommerce_wp_select(
                array(
                    'id'          => $value_id,
                    'class' => $class,
                    'wrapper_class' => 'opcw_setting_form',
                    'label'       => esc_html__( 'Value: ', 'opal-product-collection-woocommerce' ),
                    'value'       => $rule_value,
                    'options'     => $rule_option_value,
                    'style' => 'display: block',
                    'custom_attributes' => $custom_attributes
                )
            );
        }
        ?>
    </li>
    <div class="rule_action_btn repeater_btn"><a href="javascript:void(0)" class="rpt_btn_remove"><i class="dashicons dashicons-no-alt"></i></a></div>
</ul>