<?php
/** 
 * OPCW Settings Page
 * 
 * @uses settings
 * @uses taxs_needed
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly    

$fb_class = '';
?>
<div class="wrap">
    <div class="opcw_header_settings">
        <h2 class="opcw_title_page"><?php esc_html_e('Settings', 'opal-product-collection-woocommerce') ?></h2>
        <h3 class="opcw_subtitle_page"><?php esc_html_e('Opal Product Collection for WooCommerce Settings', 'opal-product-collection-woocommerce') ?></h3>
    </div>
</div>
<div class="wrap opcw_wrap_settings">
    <ul class="opcw_g_set_tabs <?php echo esc_html($fb_class); ?>">
        <li>
            <a href="#opcw_display_settings" class="active">
                <img src="<?php echo esc_url(OPCW_PLUGIN_URL.'/assets/images/display-settings.svg') ?>" width="20" height="20" alt=""><?php esc_html_e('Collection Settings', 'opal-product-collection-woocommerce'); ?>
            </a>
        </li>
        <li>
            <a href="#opcw_import_export">
                <img src="<?php echo esc_url(OPCW_PLUGIN_URL.'/assets/images/backup-settings.svg') ?>" width="20" height="20" alt=""><?php esc_html_e('Import/Export Settings', 'opal-product-collection-woocommerce'); ?>
            </a>
        </li>
    </ul>
    <div class="opcw_g_set_tabcontents <?php echo esc_html($fb_class); ?>">
        <div class="opcw_wrap_tabcontent">
            <div id="opcw_display_settings" class="opcw_tabcontent">
                <div class="options_group">
                    <h3><?php esc_html_e('General', 'opal-product-collection-woocommerce') ?></h3>
                    <ul>
                        <li>
                        <?php
                            $description = wp_kses_post('After change collection slug, If error 404 appears, please update <a href="'.esc_url(admin_url('options-permalink.php')).'">permalinks</a> in the Settings page');
                            woocommerce_wp_text_input(
                                array(
                                    'id'          => 'collection_slug',
                                    'class' => 'opcw_setting_field',
                                    'wrapper_class' => 'opcw_setting_form',
                                    'label'       => esc_html__( 'Collection slug: ', 'opal-product-collection-woocommerce' ),
                                    'placeholder' => 'collection-slug',
                                    'value'       => opcw_get_option('collection_slug', '', $settings),
                                    'description' => $description,
                                    'style' => 'display: block'
                                )
                            );
                        ?>
                        </li>
                        <li>
                        <?php
                            woocommerce_wp_select(
                                array(
                                    'id'          => 'module_in_taxs[]',
                                    'value'       => opcw_get_option('module_in_taxs', '', $settings),
                                    'options'     => $taxs_needed,
                                    'label'       => __( 'Show module scan on taxonomys', 'opal-product-collection-woocommerce' ),
                                    'wrapper_class' => 'opcw_setting_form', 
                                    'class' => 'opcw_setting_field opcw_init_select2 setting_page',
                                    // 'description' => __('Show module choose product and scan follow condition on some taxonomies', 'opal-product-collection-woocommerce'),
                                    'style' => 'width:95%;margin-left:0',
                                    'custom_attributes' => [
                                        'multiple' => "multiple",
                                        'data-placeholder' => __( 'Choose some taxonomys', 'opal-product-collection-woocommerce' ),
                                    ]
                                )
                            );
                        ?>
                        </li>
                    </ul>
                </div>
                <div class="options_group">
                    <h3><?php esc_html_e('Display - Single product', 'opal-product-collection-woocommerce') ?></h3>
                    <ul>
                        <li class="option_item opcw_group_settings_mt">
                        <?php
                            woocommerce_wp_select(
                                array(
                                    'id'          => 'product_render_position',
                                    'value'       => opcw_get_option('product_render_position', '', $settings),
                                    'label'       => __( 'Displays products from the same collection', 'opal-product-collection-woocommerce' ),
                                    'options'     => array(
                                        '' => __( 'Not displayed', 'opal-product-collection-woocommerce' ),
                                        'woocommerce_before_main_content-20'  => __( 'Before main content - 20', 'opal-product-collection-woocommerce' ),
                                        'woocommerce_before_single_product_summary-20'  => __( 'Before summary - 20', 'opal-product-collection-woocommerce' ),
                                        'woocommerce_single_product_summary-99'  => __( 'Inside summary - 99', 'opal-product-collection-woocommerce' ),
                                        'woocommerce_after_single_product_summary-20'  => __( 'After summary - 20', 'opal-product-collection-woocommerce' ),
                                        'woocommerce_after_main_content-20'  => __( 'After main content - 20', 'opal-product-collection-woocommerce' ),
                                    ),
                                    'wrapper_class' => 'opcw_setting_form', 
                                    'class' => 'opcw_setting_field',
                                    'style' => 'width:100%;margin-left:0'
                                )
                            );
                        ?>
                        </li>
                        <li>
                        <?php
                            woocommerce_wp_text_input(
                                array(
                                    'id'          => 'render_position_prioty',
                                    'class' => 'opcw_setting_field',
                                    'wrapper_class' => 'opcw_setting_form',
                                    'label'       => esc_html__( 'Prioty: ', 'opal-product-collection-woocommerce' ),
                                    'placeholder' => '5',
                                    'value'       => opcw_get_option('render_position_prioty', '', $settings),
                                    'style' => 'display: block'
                                )
                            );
                        ?>
                        </li>
                        <li>
                        <?php
                            woocommerce_wp_text_input(
                                array(
                                    'id'          => 'product_limit_display',
                                    'class' => 'opcw_setting_field',
                                    'wrapper_class' => 'opcw_setting_form',
                                    'label'       => esc_html__( 'Limit: ', 'opal-product-collection-woocommerce' ),
                                    'placeholder' => esc_html__( 'Limit: ', 'opal-product-collection-woocommerce' ),
                                    'value'       => opcw_get_option('product_limit_display', 8, $settings),
                                    'style' => 'display: block'
                                )
                            );
                        ?>
                        </li>
                        <li>
                        <?php
                            woocommerce_wp_text_input(
                                array(
                                    'id'          => 'title_more_in_collection',
                                    'class' => 'opcw_setting_field',
                                    'wrapper_class' => 'opcw_setting_form',
                                    'label'       => esc_html__( 'Title: ', 'opal-product-collection-woocommerce' ),
                                    'placeholder' => esc_html__( 'Title: ', 'opal-product-collection-woocommerce' ),
                                    'value'       => opcw_get_option('title_more_in_collection', '', $settings),
                                    'style' => 'display: block'
                                )
                            );
                        ?>
                        </li>
                        <li>
                            <?php
                            opcw_wp_checkbox( array( 
                                'wrapper_class' => 'opcw_setting_form opcw_flex_row_reverse opcw_flex_align_items_center', 
                                'id' => 'wrap_into_container',
                                'class' => 'opcw_setting_field',
                                'label' => esc_html__('Wrap into container', 'opal-product-collection-woocommerce'),
                                'value' => opcw_get_option('wrap_into_container', 0, $settings),
                                'description' => esc_html__('Wrapping "More in Collection" into a container', 'opal-product-collection-woocommerce'),
                                'cbvalue' => 1,
                                'checkbox_ui' => true
                            ) );
                            ?>
                        </li>
                        <li>
                            <?php
                            opcw_wp_checkbox( array( 
                                'wrapper_class' => 'opcw_setting_form opcw_flex_row_reverse opcw_flex_align_items_center', 
                                'id' => 'show_collection_in_meta',
                                'class' => 'opcw_setting_field',
                                'label' => esc_html__('Show collections in product meta', 'opal-product-collection-woocommerce'),
                                'value' => opcw_get_option('show_collection_in_meta', 0, $settings),
                                'description' => esc_html__('Show collections of product in product meta summmary', 'opal-product-collection-woocommerce'),
                                'cbvalue' => 1,
                                'checkbox_ui' => true
                            ) );
                            ?>
                        </li>
                    </ul>
                </div>
                <div class="options_group">
                    <h3><?php esc_html_e('Shortcode', 'opal-product-collection-woocommerce') ?></h3>
                    <p>
                        <?php  
                        echo wp_kses('You can use shortcode <code>[opcw]</code> to list all products of current collection.', ['code' => []]);
                        ?>
                    </p>
                    <p>
                        <?php  
                        echo wp_kses('Or you can also use the collection id in the shortcode to list all products of a specific collection. For example: <code>[opcw collection-id="123"]</code>', ['code' => []]);
                        ?>
                    </p>
                </div>
                <div class="options_group">
                    <h3><?php esc_html_e('Seo/Meta/Description', 'opal-product-collection-woocommerce') ?></h3>
                    <ul>
                        <li>
                            <?php
                            opcw_wp_checkbox( array( 
                                'wrapper_class' => 'opcw_setting_form opcw_flex_row_reverse opcw_flex_align_items_center', 
                                'id' => 'show_seo_data',
                                'class' => 'opcw_setting_field',
                                'label' => esc_html__('Show SEO Meta', 'opal-product-collection-woocommerce'),
                                'value' => opcw_get_option('show_seo_data', 0, $settings),
                                'description' => esc_html__('Helps optimize and be friendly to search engines', 'opal-product-collection-woocommerce'),
                                'cbvalue' => 1,
                                'checkbox_ui' => true
                            ) );
                            ?>
                        </li>
                        <li>
                            <?php
                            $image_sizes = get_intermediate_image_sizes();
                            woocommerce_wp_select(
                                array(
                                    'id'          => 'og_logo_size',
                                    'value'       => opcw_get_option('og_logo_size', 'large', $settings),
                                    'label'       => __( 'Logo size', 'opal-product-collection-woocommerce' ),
                                    'options'     => $image_sizes,
                                    'wrapper_class' => 'opcw_setting_form', 
                                    'class' => 'opcw_setting_field',
                                    'style' => 'width:100%;margin-left:0'
                                )
                            );
                            ?>
                        </li>
                    </ul>
                </div>
                <div class="options_group">
                    <h3><?php esc_html_e('Scan Schedule', 'opal-product-collection-woocommerce') ?></h3>
                    <div class="option_item">
                        <?php
                        opcw_wp_checkbox( array( 
                            'wrapper_class' => 'opcw_setting_form opcw_flex_row_reverse opcw_flex_align_items_center', 
                            'id' => 'enable_scan_schedule',
                            'class' => 'opcw_setting_field opcw_field_trigger',
                            'label' => esc_html__('Enable Scan Schedule', 'opal-product-collection-woocommerce'),
                            'description' => esc_html__('Automatically scan products belonging to collections or categories according to scheduled times', 'opal-product-collection-woocommerce'),
                            // 'desc_tip' => true,
                            'value' => opcw_get_option('enable_scan_schedule', 0, $settings),
                            'cbvalue' => true,
                            'checkbox_ui' => true
                        ) );
                        ?>
                    </div>
                    <ul class="option_list <?php echo (opcw_get_option('enable_scan_schedule', 0, $settings)) ? '' : esc_attr('hidden_setting') ?>" data-condition="enable_scan_schedule">
                        <li>
                            <?php
                                woocommerce_wp_select(
                                    array(
                                        'id'          => 'time_refresh_interval',
                                        'value'       => opcw_get_option('time_refresh_interval', 'daily', $settings),
                                        'label'       => __( 'Time refresh interval', 'opal-product-collection-woocommerce' ),
                                        'options'     => array(
                                            'hourly' => __( 'Once Hourly', 'opal-product-collection-woocommerce' ),
                                            'twicedaily'  => __( 'Twice Daily', 'opal-product-collection-woocommerce' ),
                                            'daily'  => __( 'Once Daily', 'opal-product-collection-woocommerce' ),
                                            'opcw_twodays'  => __( 'Every 2 Days', 'opal-product-collection-woocommerce' ),
                                            'opcw_threedays'  => __( 'Every 3 Days', 'opal-product-collection-woocommerce' ),
                                            'opcw_fourdays'  => __( 'Every 4 Days', 'opal-product-collection-woocommerce' ),
                                            'opcw_fivedays'  => __( 'Every 5 Days', 'opal-product-collection-woocommerce' ),
                                            'opcw_sixdays'  => __( 'Every 6 Days', 'opal-product-collection-woocommerce' ),
                                            'weekly'  => __( 'Once Weekly', 'opal-product-collection-woocommerce' ),
                                        ),
                                        'wrapper_class' => 'opcw_setting_form', 
                                        'class' => 'opcw_setting_field',
                                        'style' => 'width:100%;margin-left:0'
                                    )
                                );
                            ?>
                        </li>
                        <li>
                            <?php
                            woocommerce_wp_radio( array( 
                                'wrapper_class' => 'opcw_setting_form', 
                                'id' => 'scan_option_schedule',
                                'class' => 'opcw_setting_field',
                                'label' => esc_html__('Scan option', 'opal-product-collection-woocommerce'),
                                'value' => opcw_get_option('scan_option_schedule', 'new', $settings),
                                'description' => esc_html__('Scan products that are not part of a collection or all products', 'opal-product-collection-woocommerce'),
                                'options'     => array(
                                    'new' => __( 'Scan new products', 'opal-product-collection-woocommerce' ),
                                    'all' => __( 'Scan all product', 'opal-product-collection-woocommerce' ),
                                ),
                            ) );
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
            <div id="opcw_import_export" class="opcw_tabcontent" style="display: none;">
                <div class="options_group">
                    <div class="opcw_group_option">
                        <img src="<?php echo esc_url(OPCW_PLUGIN_URL.'/assets/images/download-solid.svg') ?>" width="50" alt="">
                        <div>
                            <h3><?php esc_html_e('Export Settings', 'opal-product-collection-woocommerce') ?></h3>
                            <p><?php esc_html_e('Download a backup file of your settings', 'opal-product-collection-woocommerce') ?></p>
                        </div>
                    </div>
                    <div class="opcw_action_button">
                        <a href="<?php echo esc_url(admin_url( 'admin-ajax.php' ).'?action=opcw_settings_export&ajax_nonce_parameter='.wp_create_nonce( "opcw-nonce-ajax" )); ?>" id="opcw_download_settings" class="button button-primary"><?php esc_html_e('Download settings', 'opal-product-collection-woocommerce') ?></a>
                    </div>
                </div>
                <form id="opcw-form-import-settings" class="options_group" method="post" action="<?php echo esc_url(admin_url( 'admin-ajax.php' )) ?>" enctype="multipart/form-data">
                    <div class="opcw_group_option">
                        <img src="<?php echo esc_url(OPCW_PLUGIN_URL.'/assets/images/file-import-solid.svg') ?>" width="50" alt="">
                        <div>
                            <h3><?php esc_html_e('Import Settings', 'opal-product-collection-woocommerce') ?></h3>
                            <fieldset id="opcw-import-form-settings">
                                <input type="hidden" name="action" value="opcw_handle_import_settings">
                                <?php wp_nonce_field('opcw-nonce-ajax', 'ajax_nonce_parameter');  ?>
                                <div class="opcw_field_wrap">
                                    <input type="file" name="opcw_setting_import" accept=".json,application/json" required="">
                                </div>
                                <p class="opcw_notice"><?php esc_html_e('*Notice: All existing settings will be overwritten', 'opal-product-collection-woocommerce') ?></p>
                            </fieldset>
                        </div>
                    </div>
                    <div class="opcw_action_button">
                        <button id="opcw_import_settings" class="button button-primary"><?php esc_html_e('Upload file and import settings', 'opal-product-collection-woocommerce') ?></a>
                    </div>
                </form>
            </div>
        </div>
        <div class="opcw_setting_action mt">
            <input type="hidden" name="action" value="opcw_handle_settings_form">
            <?php wp_nonce_field('opcw-nonce-ajax', 'ajax_nonce_parameter');  ?>
            <button type="button" id="opcw_submit_settings" class="button"><?php esc_html_e('Save settings', 'opal-product-collection-woocommerce') ?></button>
        </div>
    </div>
    <div style="clear: both"></div>
</div>
