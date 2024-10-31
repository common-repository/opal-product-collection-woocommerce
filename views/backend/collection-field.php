<?php
/** 
 * OPCW Rule Item Block
 * 
 * @uses term (option)
 * @uses show_logo
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly    

$include_select_val = [];
$exclude_select_val = [];
$relation  = 'any';
if ( !empty($term) && $term ) {
    $logo        = get_term_meta( $term->term_id, 'opcw_logo', true ) ?: '';

    $relation  = get_term_meta( $term->term_id, 'opcw_condition_relation', true );
    
    $conditions  = get_term_meta( $term->term_id, 'opcw_conditions', true );
    $conditions  = $conditions ? (array) $conditions : [];

    $include     = get_term_meta( $term->term_id, 'opcw_include_products', true );
    $include  = $include ? $include : [];
    
    $exclude     = get_term_meta( $term->term_id, 'opcw_exclude_products', true );
    $exclude  = $exclude ? $exclude : [];

    $table_start = '<table class="form-table">';
    $table_end   = '</table>';
    $tr_start    = '<tr class="form-field">';
    $tr_end      = '</tr>';
    $th_start    = '<th scope="row">';
    $th_end      = '</th>';
    $td_start    = '<td>';
    $td_end      = '</td>';

    if (!empty($include)) {
        foreach ($include as $id) {
            $title = get_the_title($id);
            if (empty($title)) continue;
            $include_select_val[$id] = $title;
        }
    }
    if (!empty($exclude)) {
        foreach ($exclude as $id) {
            $title = get_the_title($id);
            if (empty($title)) continue;
            $exclude_select_val[$id] = $title;
        }
    }
} else {
    // add new
    $logo        = '';
    $rules_data  = [];
    $include     = [];
    $exclude     = [];
    $table_start = '';
    $table_end   = '';
    $tr_start    = '<div class="form-field">';
    $tr_end      = '</div>';
    $th_start    = '';
    $th_end      = '';
    $td_start    = '';
    $td_end      = '';
}

$allow_html = [
    'table' => [
        'class' => []
    ],
    'tr' => [
        'class' => []
    ],
    'th' => [
        'scope' => []
    ],
    'td' => [
        'class' => []
    ],
    'div' => [
        'class' => []
    ],
];

echo wp_kses($table_start, $allow_html);

if ($show_logo) {
    // new row
    echo wp_kses($tr_start . $th_start, $allow_html);
    ?>
    <label for="opcw_logo"><?php esc_html_e( 'Logo', 'opal-product-collection-woocommerce' ); ?></label>
    <?php
    echo wp_kses($th_end . $td_start, $allow_html);
    ?>
    <div class="opcw_image_uploader">
        <input type="hidden" name="opcw_logo" id="opcw_logo" class="opcw_image_val" value="<?php echo esc_attr( $logo ); ?>"/>
        <a href="#" id="opcw_logo_select" class="button"><?php esc_html_e( 'Select image', 'opal-product-collection-woocommerce' ); ?></a>
        <div class="opcw_selected_image" style="<?php echo esc_attr(empty( $logo ) ? 'display: none' : ''); ?>">
            <figure class="opcw_selected_image_img"><?php echo wp_get_attachment_image( $logo, 'medium' ); ?></figure>
            <span class="opcw_remove_image"><?php esc_html_e( '×', 'opal-product-collection-woocommerce' ); ?></span>
        </div>
    </div>
    <?php
    echo wp_kses($td_end . $tr_end, $allow_html);
}
// new row
echo wp_kses($tr_start . $th_start, $allow_html);
?>
<label for="opcw_rules_data"><?php esc_html_e( 'Conditions', 'opal-product-collection-woocommerce' ); ?></label>
<?php
echo wp_kses($th_end . $td_start, $allow_html);
?>
<div class="opcw_rules_collection">
    <div class="opcw_condition_relation" style="margin-bottom: 20px;">
        <?php
        woocommerce_wp_select(
            array(
                'id'          => 'opcw_condition_relation',
                'value'       => $relation,
                'label'       => __( 'Products must match:', 'opal-product-collection-woocommerce' ),
                'options'     => [
                    'any' => __('Any condition', 'opal-product-collection-woocommerce'),
                    'all' => __('All conditions', 'opal-product-collection-woocommerce'),
                ],
                'wrapper_class' => 'opcw_setting_form', 
                'class' => 'opcw_setting_field',
                'style' => 'width:95%;margin-left:0',
            )
        );
        ?>
    </div>
    <div class="opcw_wrapper_rules">
        <?php
        if (!empty($conditions) && is_array($conditions)) {
            foreach ($conditions as $i => $condition_item) {
                OPCW_Admin::view('rule-item', ['condition_item' => $condition_item, 'index' => $i]);
            }
        }
        else {
            OPCW_Admin::view('rule-item', ['condition_item' => [], 'index' => 0]);
        }
        ?>
        <nav class="repeater_btn opcw-flex opcw_flex_justify_content_end"><a href="javascript:void(0)" class="button rpt_btn_add"><?php esc_html_e('+ Add Rule', 'opal-product-collection-woocommerce') ?></a></nav>
    </div>
</div>
<?php
echo wp_kses($td_end . $tr_end, $allow_html);
// new row
echo wp_kses($tr_start . $th_start, $allow_html);
?>
<label for="opcw_include_products"><?php esc_html_e( 'Include products', 'opal-product-collection-woocommerce' ); ?></label>
<?php
echo wp_kses($th_end . $td_start, $allow_html);
?>
<div class="opcw-product-search">
    <?php
    $wrap_class = 'opcw_setting_form opcw_field_nolabel opcw_wrapper_rules_apply';
    woocommerce_wp_select(
        array(
            'id'          => 'opcw_include_products[]',
            'value'       => $include,
            'options'     => $include_select_val,
            'wrapper_class' => $wrap_class, 
            'label'       => '',
            'class' => 'opcw_setting_field opcw_rules_apply opcw_init_select2',
            'style' => 'width:95%;margin-left:0',
            'custom_attributes' => [
                'multiple' => "multiple",
                'data-placeholder' => __( 'Typing to select', 'opal-product-collection-woocommerce' ),
            ]
        )
    );
    ?>
</div>
<?php
echo wp_kses($td_end . $tr_end, $allow_html);
// new row
echo wp_kses($tr_start . $th_start, $allow_html);
?>
<label for="opcw_exclude_products"><?php esc_html_e( 'Exclude products', 'opal-product-collection-woocommerce' ); ?></label>
<?php
echo wp_kses($th_end . $td_start, $allow_html);
?>
<div class="opcw-product-search">
    <?php
    $wrap_class = 'opcw_setting_form opcw_field_nolabel opcw_wrapper_rules_apply';
    woocommerce_wp_select(
        array(
            'id'          => 'opcw_exclude_products[]',
            'value'       => $exclude,
            'options'     => $exclude_select_val,
            'wrapper_class' => $wrap_class, 
            'label'       => '',
            'class' => 'opcw_setting_field opcw_rules_apply opcw_init_select2',
            'style' => 'width:95%;margin-left:0',
            'custom_attributes' => [
                'multiple' => "multiple",
                'data-placeholder' => __( 'Typing to select', 'opal-product-collection-woocommerce' ),
            ]
        )
    );
    ?>
</div>
<?php
echo wp_kses($td_end . $tr_end, $allow_html);
// new row
echo wp_kses($tr_start . $th_start, $allow_html);
?>
<label for="opcw_note"><?php esc_html_e( 'Note', 'opal-product-collection-woocommerce' ); ?></label>
<?php
echo wp_kses($th_end . $td_start, $allow_html);
?><?php esc_html_e( 'After changing the conditions above, please click Rescan to update the product according to the conditions for Collection!', 'opal-product-collection-woocommerce' ); ?><?php
echo wp_kses($td_end . $tr_end . $table_end, $allow_html);