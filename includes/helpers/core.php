<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly    

if (!function_exists('opcw_check_woocommerce_active')) {
    function opcw_check_woocommerce_active() {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return true;
        }
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins['woocommerce/woocommerce.php']))
                return true;
        }
        return false;
    }
}

if (!function_exists('opcw_get_available_shipping_methods')) {
    function opcw_get_available_shipping_methods(){

        $normalized_shipping_methods = array(
            'default' => __('All shipping method', 'opal-product-collection-woocommerce')
        );
        
        if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
            return $normalized_shipping_methods;
        }

        $zones = WC_Shipping_Zones::get_zones();

        if ( ! is_array( $zones ) ) {
            return $normalized_shipping_methods;
        }

        
        foreach ($zones as $zone) {
            $zone_id = $zone['zone_id'];
            $zone_name = $zone['zone_name'];
            $shipping_methods = $zone['shipping_methods'];
        
            foreach ( $shipping_methods as $i => $class ) {
                if($class->enabled == 'yes') {
                    $normalized_shipping_methods[ $class->id.':'.$zone_id ] = $zone_name.' - '.$class->method_title;
                }
            }
        }
        return $normalized_shipping_methods;

    }
}

if (!function_exists('opcw_parse_attr_html')) {
    /**
     * Calculate Price Of Field By Percent
     *
     * @since  1.0.0
     */
    function opcw_parse_attr_html(array $attr, $print = false) {
        $attr_return = implode(' ', array_map(function ($key, $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
    
            return esc_html($key) . "='" . $value . "'";
        }, array_keys($attr), $attr));

        if ($print) {
            add_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);

            echo esc_html($attr_return);

            remove_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
        } 
        else {
            return $attr_return;
        }
        
    }
}

if (!function_exists('opcw_str_short')) {
    /**
     * Short String Middle
     *
     * @since  1.0.0
     */
    function opcw_str_short($string, $length, $lastLength = 0, $symbol = '...')
    {
        if (strlen($string) > $length) {
            $result = substr($string, 0, $length - $lastLength - strlen($symbol)) . $symbol;
            return $result . ($lastLength ? substr($string, - $lastLength) : '');
        }

        return $string;
    }
}

if (!function_exists('opcw_swapPos')) {
    function opcw_swapPos(&$arr, $pos1, $pos2){
        $keys = array_keys($arr);
        $vals = array_values($arr);
        $key1 = array_search($pos1, $keys);
        $key2 = array_search($pos2, $keys);
    
        $tmp = $keys[$key1];
        $keys[$key1] = $keys[$key2];
        $keys[$key2] = $tmp;
    
        $tmp = $vals[$key1];
        $vals[$key1] = $vals[$key2];
        $vals[$key2] = $tmp;
    
        $arr = array_combine($keys, $vals);

    }
}

if (!function_exists('opcw_get_option')) {
    /**
     * @return string
     */
    function opcw_get_option($option, $default = false, $settings_value = false)
    {
        if(!get_option(OPCW_SETTINGS_KEY)) return $default;
        $settings = (!$settings_value) ? get_option(OPCW_SETTINGS_KEY) : $settings_value;
        
        $settings = apply_filters('opcw_configurations', json_decode($settings, true));
        $response = (isset($settings[$option]) && !empty($settings[$option])) ? $settings[$option] : $default;
        
        return $response;
    }

}

if (!function_exists('opcw_send_file_headers')) {
    function opcw_send_file_headers( $file_name, $file_size ) {
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename=' . $file_name );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . $file_size );
    }
}

if (!function_exists('opcw_convert_weekday_to_iso')) {
    function opcw_convert_weekday_to_iso( $weekdays = [] ) {
        $return = [];
        foreach ($weekdays as $day) {
            $return[] = gmdate('N', strtotime($day));;
        }
        return $return;
    }
}

if (!function_exists('opcw_is_product_in_taxs')) {
    function opcw_is_product_in_taxs($product_id, $tax_ids, $taxonomy) {
        // Check empty list tax ids
        if (empty($tax_ids)) {
            return false;
        }

        // Check exist product
        if (!is_numeric($product_id) || !get_post($product_id) || get_post_type($product_id) !== 'product') {
            return false;
        }

        // Get all terms of product
        $product_taxs = wp_get_post_terms($product_id, $taxonomy, array('fields' => 'ids'));

        // Check
        foreach ($tax_ids as $category_id) {
            if (in_array($category_id, $product_taxs)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('opcw_prevent_escape_html')) {
    /**
     * opcw_prevent_escape_html 
     */
    function opcw_prevent_escape_html($safe_text, $text){
        return $text;
    }
}

/**
 * Output a checkbox input box.
 *
 * @param array   $field Field data.
 * @param WC_Data $data WC_Data object, will be preferred over post object when passed.
 */
function opcw_wp_checkbox( $field, WC_Data $data = null ) {
	global $post;

	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'checkbox';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = $field['value'] ?? \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_object_meta( $post, $data, $field['id'], true );
	$field['cbvalue']       = isset( $field['cbvalue'] ) ? $field['cbvalue'] : 'yes';
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$field['checkbox_ui']   = isset( $field['checkbox_ui'] ) && $field['checkbox_ui'];

	// Custom attribute handling
	$custom_attributes = array();

	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {

		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

    if ( $field['checkbox_ui'] ) {
		$field['wrapper_class'] .= ' opcw_toggle';
	}

    $html = '';
	$html .= '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '"><strong>' . wp_kses_post( $field['label'] ) . '</strong></label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		$html .= esc_attr(wc_help_tip( $field['description'] ));
	}

    if ( $field['checkbox_ui'] ) {
		$field['class'] .= ' opcwv_toggle_input';
	}

	$html .= '<input type="checkbox" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['cbvalue'] ) . '" ' . checked( $field['value'], $field['cbvalue'], false ) . '  ' . implode( ' ', $custom_attributes ) . '/>';

    if ( $field['checkbox_ui'] ) {
		$html .= '<label for="' . esc_attr( $field['id'] ) . '" class="opcw_toggle_switch"></label>';
	}

    
	$html .= '</p>';
	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		$html .= '<p class="description">' . wp_kses_post( $field['description'] ) . '</p>';
	}

    add_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
    echo esc_html($html);
    remove_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
}

/**
 * Output a text input box.
 *
 * @param array   $field Field data.
 * @param WC_Data $data WC_Data object, will be preferred over post object when passed.
 */
function opcw_wp_text_input( $field, WC_Data $data = null ) {
	global $post;

	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = $field['value'] ?? Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_object_meta( $post, $data, $field['id'], true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$data_type              = empty( $field['data_type'] ) ? '' : $field['data_type'];

	switch ( $data_type ) {
		case 'price':
			$field['class'] .= ' wc_input_price';
			$field['value']  = wc_format_localized_price( $field['value'] );
			break;
		case 'decimal':
			$field['class'] .= ' wc_input_decimal';
			$field['value']  = wc_format_localized_decimal( $field['value'] );
			break;
		case 'stock':
			$field['class'] .= ' wc_input_stock';
			$field['value']  = wc_stock_amount( $field['value'] );
			break;
		case 'url':
			$field['class'] .= ' wc_input_url';
			$field['value']  = esc_url( $field['value'] );
			break;

		default:
			break;
	}

	// Custom attribute handling
	$custom_attributes = array();

	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {

		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

    $html = '';
	$html .= '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		$html .= esc_attr(wc_help_tip( $field['description'] ));
	}

	$html .= '<input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';

    if (in_array($field['type'], ['time', 'date', 'datetime-local'])) {
        ?>
        <a class="input-button" title="clear" data-clear>
            <i class="icon-close"></i>
        </a>
        <?php
    }

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		$html .= '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	$html .= '</p>';

    add_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
    echo esc_html($html);
    remove_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
}

function opcw_get_count_product_by_term( $term_id, $taxonomy ) {
    $count = 0;
    $term = get_term($term_id, $taxonomy);
    if (!is_wp_error($term) && !empty($term->count)) {
        $count = $term->count;
    }
	return $count;
}

function opcw_get_product_collections_list( $product_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $product_id, OPCW_TAXONOMY, $before, $sep, $after );
}

function opcw_sanitize_array( $arr ) {
    foreach ( (array) $arr as $k => $v ) {
        if ( is_array( $v ) ) {
            $arr[ $k ] = opcw_sanitize_array( $v );
        } else {
            $arr[ $k ] = sanitize_text_field( $v );
        }
    }

    return $arr;
}

function opcw_get_all_variants() {
    $variants = array();
    $attr_tax = wc_get_attribute_taxonomies();
    if (!empty($attr_tax)) {
        foreach ($attr_tax as $atr) { 
            $terms = get_terms(array(
                'taxonomy' => wc_attribute_taxonomy_name($atr->attribute_name),
                'hide_empty' => false,
            ));

            foreach ($terms as $term) {
                $variants['attribute_pa_'.$atr->attribute_name.'_'.$term->slug] = ucwords($atr->attribute_label . ' - ' . $term->name);
            }
        }
    }
    return $variants;
}

function opcw_get_variations_of_product($product) {
    $variations = $product->get_available_variations();

    $var_arr = [];
    if ($variations && !empty($variations)) {
        foreach($variations as $var) {
            $attrs = $var['attributes'];
            foreach($attrs as $key => $attr) {
                $var_arr[] = $key.'_'.$attr;
            }   
        }
    }

    return $var_arr;
}

function opcw_check_string_has_char($string, $charsToCheck) {
    $string = strtolower($string);
    $charsToCheck = strtolower($charsToCheck);
    $containsChar = strpos($string, $charsToCheck);
    return $containsChar !== false;
}

function opcw_check_string_start_with_char($string, $charsToCheck) {
    $string = strtolower($string);
    $charsToCheck = strtolower($charsToCheck);
    return strpos($string, $charsToCheck) === 0;
}

function opcw_check_string_end_with_char($string, $charsToCheck) {
    $string = strtolower($string);
    $charsToCheck = strtolower($charsToCheck);
    $endsWithLength = strlen($charsToCheck);
    $endOfString = substr($string, -$endsWithLength);

    return $endOfString === $charsToCheck;
}

function opcw_check_product_belong_cat_ids($product, $cat_ids) {
    $category_ids = $product->get_category_ids();

    $belongs_to_category = false;
    foreach ($category_ids as $category_id) {
        if (in_array($category_id, $cat_ids)) {
            $belongs_to_category = true;
            break;
        }
    }

    return $belongs_to_category;
}

function opcw_check_product_belong_tag_ids($product, $tag_ids_to_check) {
    $tag_ids = $product->get_tag_ids();

    $belongs_to_tag = false;
    foreach ($tag_ids as $tag_id) {
        if (in_array($tag_id, $tag_ids_to_check)) {
            $belongs_to_tag = true;
            break;
        }
    }

    return $belongs_to_tag;
}

// function opcw_p($var) {
//     echo '<pre>';
//     print_r($var);
//     echo '</pre>';
// }