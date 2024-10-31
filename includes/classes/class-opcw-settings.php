<?php
use Automattic\WooCommerce\Admin\Features\Features;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class OPCW_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		OPCW
 * @subpackage	Classes/OPCW_Settings
 * @author		WPOPAL
 * @since		1.0.0
 */
class OPCW_Settings{

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.0.0
	 */
	private $plugin_name;

	/**
	 * Our OPCW_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		$this->plugin_name = OPCW_NAME;
		$plugin = OPCW_PLUGIN_BASE;
		
        add_filter("plugin_action_links_$plugin", array($this, 'add_settings_link'));

		register_activation_hook(OPCW_PLUGIN_FILE, array($this, 'install'));
		register_activation_hook(OPCW_PLUGIN_FILE, array($this, 'opcw_deactive_without_woocommerce'));
		register_deactivation_hook(OPCW_PLUGIN_FILE, array($this, 'deactivation'));
		add_action( 'activate_plugin', [$this, 'prevent_oldversion_activation'], 10, 2 );

		add_action( 'admin_init', array($this, 'opcw_trigger_deactice_addon_without_woocommerce' ));
		add_action( 'admin_menu', [$this, 'opcw_custom_submenu' ] );
		
		add_action( 'wp_ajax_opcw_handle_settings_form', [$this, 'opcw_handle_settings_form'] );
		add_action( 'wp_ajax_opcw_settings_export', [$this, 'opcw_settings_export'] );
		add_action( 'wp_ajax_opcw_handle_import_settings', [$this, 'opcw_handle_import_settings'] );
		
		add_filter( 'cron_schedules', [$this, 'opcw_trigger_schedule_event'], 99);

		add_action( 'init', [$this, 'opcw_update_menu_items_with_new_taxonomy_slug'], 99 );
	}

	/**
	 * Return the plugin name
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	string The plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'OPCW/settings/get_plugin_name', $this->plugin_name );
	}

	public function add_settings_link($links) {
		if ( !opcw_check_woocommerce_active() ) return $links;

        $settings = '<a href="' . admin_url('admin.php?page=opcw-settings') . '">' . esc_html__('Settings', 'opal-product-collection-woocommerce') . '</a>';
        array_push($links, $settings);
        
        return $links;
    }

	public function opcw_deactive_without_woocommerce() {
		if (!class_exists('Woocommerce')) {
			add_action( 'admin_notices', array($this, 'opcw_child_plugin_notice') );
			// deactivate_plugins(OPCW_PLUGIN_BASE);
		}
	}
	
	public function opcw_trigger_deactice_addon_without_woocommerce() {
		if (!class_exists('Woocommerce')) {
			add_action( 'admin_notices', array($this, 'opcw_child_plugin_notice') );
		}
	}
	
	public function opcw_child_plugin_notice(){
		$message = __('<strong>Opal Product Collection for WooCommerce</strong> is an addon extention of <strong>Woocommerce Plugin</strong>. Please active <strong>Woocommerce Plugin</strong> to be able to use this extention!', 'opal-product-collection-woocommerce');
		?>
		<div class="error"><p><?php echo wp_kses_post($message); ?></p></div>
		<?php
	}

	public function install() {
		// Review team forced me to change the plugin url, haizzzz :(
		$this->opcw_update_old_version();
		self::opcw_deactivate_old_version();

		$settings = $this->opcw_add_default_settings();
		$this->opcw_schedule_cron($settings);
	}

	public function deactivation() {
		wp_clear_scheduled_hook(OPCW_CRON_HOOK);
	}

	public function prevent_oldversion_activation($plugin, $network_activation ) {
	    if ( 'opal-woo-product-collection/opal-woo-product-collection.php' === $plugin ) {
	        die();
	    }
	}

	public function opcw_update_menu_items_with_new_taxonomy_slug() {
		// $old_tax_slug = 'owpc-collection';
		// global $wpdb; 
		// $checkIfExists = $wpdb->get_var("SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = '$old_tax_slug'");
		// if (empty($checkIfExists) || !$checkIfExists) return;

		$locations = get_nav_menu_locations();
		foreach ($locations as $location => $menu_id) {
			$menu = wp_get_nav_menu_object( $menu_id );
			$menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) );
			foreach ($menu_items as $menu_item) {
				if (!empty($menu_item->_invalid)) {
					if (isset($menu_item->object) && $menu_item->object == 'owpc-collection') {
						$term = get_term($menu_item->object_id);

						if (!empty($term) && !is_wp_error($term) ) {
							$args = array(
								'menu-item-db-id'         => $menu_item->db_id,
								'menu-item-object-id' => $menu_item->object_id,
								'menu-item-object'        => OPCW_TAXONOMY,
								'menu-item-parent-id'     => $menu_item->menu_item_parent,
								'menu-item-position'      => $menu_item->menu_order,
								'menu-item-type'          => 'taxonomy',
								'menu-item-title'         => $term->name,
								'menu-item-url'           => get_term_link( $term ),
								'menu-item-target'        => $menu_item->target,
								'menu-item-status'        => $menu_item->post_status,
								'menu-item-post-date'     => $menu_item->post_date,
								'menu-item-post-date-gmt' => $menu_item->post_date_gmt,
							);
		
							wp_update_nav_menu_item($menu->term_id, $menu_item->db_id, $args);
						}
					}
				}
			}
		}
	}

	public function opcw_trigger_schedule_event($schedules) {
		$schedules['opcw_twodays'] = array(
			'interval' => 2 * DAY_IN_SECONDS,
			'display'  => __( 'Every 2 Days', 'opal-product-collection-woocommerce' ),
		);
		$schedules['opcw_threedays'] = array(
			'interval' => 3 * DAY_IN_SECONDS,
			'display'  => __( 'Every 3 Days', 'opal-product-collection-woocommerce' ),
		);
		$schedules['opcw_fourdays'] = array(
			'interval' => 4 * DAY_IN_SECONDS,
			'display'  => __( 'Every 4 Days', 'opal-product-collection-woocommerce' ),
		);
		$schedules['opcw_fivedays'] = array(
			'interval' => 5 * DAY_IN_SECONDS,
			'display'  => __( 'Every 5 Days', 'opal-product-collection-woocommerce' ),
		);
		$schedules['opcw_sixdays'] = array(
			'interval' => 6 * DAY_IN_SECONDS,
			'display'  => __( 'Every 6 Days', 'opal-product-collection-woocommerce' ),
		);

		return $schedules;
	}

	private function opcw_add_default_settings() {
		$settings_option = get_option(OPCW_SETTINGS_KEY);
		if (!$settings_option) {
			$settings = $this->opcw_get_settings_default();
			$settings = wp_json_encode($settings);

			update_option(OPCW_SETTINGS_KEY, $settings);
			update_option('opcw_flush_permalink', 'yes');

			return $settings;
		}

		return $settings_option;
	}

	private function opcw_update_old_version() {
		$oldver_option = get_option('opcw_settings_key');
		$old_tax_slug = 'owpc-collection';
		global $wpdb; 
		$checkIfExists = $wpdb->get_var("SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = '$old_tax_slug'");

		if ($oldver_option || $checkIfExists) {
			delete_option('opcw_settings_key');
			delete_option('opcw_flush_permalink');
			delete_option('opcw_is_schedule_scan');
			delete_option('opcw-collection_children');
			
			$wpdb->update($wpdb->options, ['option_name' => 'opcw_settings_key'], ['option_name' => 'owpc_settings_key']);
			$wpdb->update($wpdb->options, ['option_name' => 'opcw_flush_permalink'], ['option_name' => 'owpc_flush_permalink']);
			$wpdb->update($wpdb->options, ['option_name' => 'opcw_is_schedule_scan'], ['option_name' => 'owpc_is_schedule_scan']);
			$wpdb->update($wpdb->options, ['option_name' => 'opcw-collection_children'], ['option_name' => 'owpc-collection_children']);
			
			$wpdb->update($wpdb->term_taxonomy, ['taxonomy' => OPCW_TAXONOMY], ['taxonomy' => $old_tax_slug]);
		}
	}
	
	public static function opcw_deactivate_old_version() {
		if (defined('OWPC_PLUGIN_BASE')) {
			/**
			 * Help moving from our previous plugin (with old name) to this one.
			 * The old version is being used for some customer websites. Therefore we need to deactivate the old version and replace it with the current version.
			 */
			deactivate_plugins(OWPC_PLUGIN_BASE);
		}
	}

	private function opcw_schedule_cron($settings) {
		if (!wp_next_scheduled(OPCW_CRON_HOOK)) {
			$enable = opcw_get_option('enable_scan_schedule', 0, $settings);
			if ($enable) {
				$recurrence = opcw_get_option('time_refresh_interval', 'daily', $settings);

				$next_run = time() + HOUR_IN_SECONDS;

				wp_schedule_event($next_run, $recurrence, OPCW_CRON_HOOK);
			}
		}
	}

	public function opcw_get_settings_default() {
		$settings = [
			'collection_slug' => '',
			'product_render_position' => 'woocommerce_after_main_content-20',
			'render_position_prioty' => '',
			'product_limit_display' => 8,
			'title_more_in_collection' => 'More in Collection',
			'wrap_into_container' => 1,
			'show_collection_in_meta' => 1,
			'module_in_taxs' => [OPCW_TAXONOMY],
			'show_seo_data' => 1,
			'og_logo_size' => 'large',
			'enable_scan_schedule' => 0,
			'time_refresh_interval' => 'daily',
			'scan_option_schedule' => 'new',
		];

		return $settings;
	}

	public function opcw_get_settings_data() {
		$settings = get_option(OPCW_SETTINGS_KEY, wp_json_encode($this->opcw_get_settings_default()));
		return $settings;
	}

	public function opcw_custom_submenu() {
		global $pagenow;

		add_submenu_page(
			'woocommerce',
			__( 'OPCW Setting', 'opal-product-collection-woocommerce' ),
			__( 'Collection Settings', 'opal-product-collection-woocommerce' ),
			'manage_options',
			'opcw-settings',
			[$this, 'opcw_setting_page_callback'],
		);

		if (isset($_GET['page']) && $_GET['page'] == 'opcw-settings') {
			remove_all_actions( 'admin_notices' );
		}
		
	}

	public function opcw_setting_page_callback() {
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'wc-admin-layout' );
		wp_enqueue_script( 'woocommerce_admin' );
		wp_enqueue_script( 'jquery-tiptip' );
		
		$settings_data = $this->opcw_get_settings_data();

		$taxonomies = get_object_taxonomies('product', 'objects');
		$taxs_needed = [];
		if (!empty($taxonomies)) {
			foreach ($taxonomies as $tax => $tax_obj) {
				if (in_array($tax, ['product_type', 'product_visibility', 'product_shipping_class'])) {
					continue;
				}
				if (opcw_check_string_start_with_char($tax, 'pa_')) {
					continue;
				}
				$taxs_needed[$tax] = $tax_obj->label;
			}
		}

		OPCW_Admin::view('admin-settings', [
			'settings' => $settings_data,
			'taxs_needed' => $taxs_needed,
		]);
	}	

	public function prepare_settings_export() {
		$settings = $this->opcw_get_settings_data();
		
		$file_data = [
			'name' => 'opcw-data-settings-' . gmdate( 'Y-m-d' ) . '.json',
			'content' =>  $settings,
		];

		return $file_data;
	}

	public function opcw_handle_settings_form() {
		check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' );

		$settings = $this->opcw_get_settings_default();

		foreach ($settings as $name => $field) {
			$field_val = isset($_POST[$name]) ? wc_clean($_POST[$name]) : 0;
			$settings[$name] = $field_val;
		}

		if (isset($settings['enable_scan_schedule'])) {
			$new_enable_status = absint($settings['enable_scan_schedule']);
			if ($new_enable_status) {
				$old_enable_status = absint(opcw_get_option('enable_scan_schedule', 0));
				if ($new_enable_status != $old_enable_status) {
					$recurrence = $settings['time_refresh_interval'];
					$next_run = time() + HOUR_IN_SECONDS;

					if (!wp_next_scheduled(OPCW_CRON_HOOK)) {
						wp_schedule_event($next_run, $recurrence, OPCW_CRON_HOOK);
					}
				}
			} 
			else {
				wp_clear_scheduled_hook(OPCW_CRON_HOOK);
			}
			
		}

		$flag = update_option(OPCW_SETTINGS_KEY, wp_json_encode($settings));
		update_option('opcw_flush_permalink', 'yes');

		wp_send_json_success( [
			'message' => esc_html__('Update settings successfully!', 'opal-product-collection-woocommerce')
		] );
		
		die();
	}

	public function opcw_settings_export() {
		check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' );

		$file_data = $this->prepare_settings_export();

		if ( is_wp_error( $file_data ) ) {
			return $file_data;
		}

		opcw_send_file_headers( $file_data['name'], strlen( $file_data['content'] ) );

		// Clear buffering just in case.
		@ob_end_clean();

		flush();

		// Output file contents.

		add_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
		echo esc_html($file_data['content']);
		remove_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);

		die;
	}

	public function opcw_handle_import_settings() {
		check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' );

		if (isset($_FILES['opcw_setting_import']["error"]) && $_FILES['opcw_setting_import']["error"] != 4) {
			if ($_FILES['opcw_setting_import']["error"] == UPLOAD_ERR_INI_SIZE) {
				$error_message = esc_html__('The uploaded file exceeds the maximum upload limit', 'opal-product-collection-woocommerce');
			} else if (in_array($_FILES['opcw_setting_import']["error"], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE))) {
				$error_message = esc_html__('The uploaded file exceeds the maximum upload limit', 'opal-product-collection-woocommerce');
			}
			$ext = pathinfo(wc_clean($_FILES['opcw_setting_import']['name']), PATHINFO_EXTENSION);
			if ($ext != 'json' || $_FILES['opcw_setting_import']['type'] != 'application/json') {
				$error_message = esc_html__('Only allow upload Json(.json) file', 'opal-product-collection-woocommerce');
			}
		}
		else {
			$error_message = esc_html__('Please upload a file to import', 'opal-product-collection-woocommerce');
		}
		
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new \WP_Filesystem_Direct( true );
		$data_upload = $filesystem->get_contents(wc_clean($_FILES['opcw_setting_import']['tmp_name']));
		// $data_upload = json_decode($data_upload, true);
		if (empty($data_upload)) {
			$error_message = esc_html__('File upload is empty', 'opal-product-collection-woocommerce');
		}

		if (isset($error_message)) {
			$error = new \WP_Error( 'file_error', $error_message );
			if ( is_wp_error( $error ) ) {
				_default_wp_die_handler( $error->get_error_message(), 'OPCW' );
			}
		}

		$settings = json_decode($data_upload, true);		
		if (isset($settings['enable_scan_schedule'])) {
			$new_enable_status = absint($settings['enable_scan_schedule']);
			if ($new_enable_status) {
				$old_enable_status = absint(opcw_get_option('enable_scan_schedule', 0));
				if ($new_enable_status != $old_enable_status) {
					$recurrence = $settings['time_refresh_interval'];
					$next_run = time() + HOUR_IN_SECONDS;

					if (!wp_next_scheduled(OPCW_CRON_HOOK)) {
						wp_schedule_event($next_run, $recurrence, OPCW_CRON_HOOK);
					}
				}
			} 
			else {
				wp_clear_scheduled_hook(OPCW_CRON_HOOK);
			}
			
		}
			

		update_option(OPCW_SETTINGS_KEY, $data_upload);
		update_option('opcw_flush_permalink', 'yes');

		$redirect = esc_url(admin_url('admin.php?page=opcw-settings'));
		header("Location: $redirect");
		exit;
	}
}
