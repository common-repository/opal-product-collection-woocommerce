<?php

// Exit if accessed directly.

use ParagonIE\Sodium\Core\Curve25519\Ge\P2;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class OPCW_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		OPCW
 * @subpackage	Classes/OPCW_Run
 * @author		WPOPAL
 * @since		1.0.0
 */
class OPCW_Run{

	/**
	 * Our OPCW_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		$this->add_hooks();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function add_hooks(){
	
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts_and_styles' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts_and_styles' ), 20 );		
	
	}

	/**
	 * ######################
	 * ###
	 * #### WP Global Data
	 * ###
	 * ######################
	 */
	private function opcw_print_global_data()
    {
		$opcw_global_vars = [];

        if (opcw_check_woocommerce_active()) {
            $opcw_global_vars['attribute'] = opcw_get_all_variants();
            $opcw_global_vars['product_type'] = wc_get_product_types();
            $opcw_global_vars['stock_status'] = wc_get_product_stock_status_options();
        }

		return $opcw_global_vars; 
    }

	/**
	 * ######################
	 * ###
	 * #### WP Translations Data
	 * ###
	 * ######################
	 */
	private function opcw_print_translations_data() {
        require OPCW_PLUGIN_DIR.'includes/helpers/translation.php';
        wp_localize_script('form-builder-lib', 'opcw_trans_lib', $translations_lib);
        wp_localize_script('form-render-lib', 'opcw_trans_lib', $translations_lib);

        wp_localize_script('opcw-backend-scripts', 'opcw_trans', $translations);
        wp_localize_script('opalwoocu-frontend-scripts', 'opcw_trans', $translations);
    }

	/**
	 * Enqueue the backend related scripts and styles for this plugin.
	 * All of the added scripts andstyles will be available on every page within the backend.
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles() {
		global $post_type_object, $typenow, $pagenow, $current_screen;

		
		if (isset($_GET['page']) && $_GET['page'] === 'opcw-settings') {
			wp_register_script( 'opcw-setting-scripts', OPCW_PLUGIN_URL . 'assets/js/backend/setting-scripts.js', array( 'jquery' ), OPCW_VERSION, true );
			wp_localize_script( 'opcw-setting-scripts', 'opcw_script', array(
				'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
				'security_nonce'	=> wp_create_nonce( "opcw-nonce-ajax" ),
				'global_data' => $this->opcw_print_global_data()
			));
			wp_enqueue_script( 'opcw-setting-scripts' );
		}
		else {
			wp_register_script( 'opcw-form-repeater-lib', OPCW_PLUGIN_URL . 'assets/js/libs/form-repeater.js', array( 'jquery' ), OPCW_VERSION, true );
			wp_register_script( 'opcw-backend-scripts', OPCW_PLUGIN_URL . 'assets/js/backend/backend-scripts.js', array( 'jquery' ), OPCW_VERSION, true );
			
			$datas_localize = [
				'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
				'security_nonce'	=> wp_create_nonce( "opcw-nonce-ajax" ),
				'global_data' => $this->opcw_print_global_data()
			];

			wp_localize_script( 'opcw-backend-scripts', 'opcw_script', apply_filters('opcw_backend_data_localize', $datas_localize));

			wp_enqueue_script( 'opcw-form-repeater-lib' );
			wp_enqueue_script( 'opcw-backend-scripts' );
		}
		
		wp_register_script( 'opcw-toast-notice-script', OPCW_PLUGIN_URL . 'assets/js/libs/jquery.toast.min.js', array( 'jquery' ), OPCW_VERSION, true );
		wp_enqueue_script( 'opcw-toast-notice-script' );
		
		wp_register_style( 'opcw-backend-styles', OPCW_PLUGIN_URL . 'assets/css/backend-styles.css', array(), OPCW_VERSION, 'all' );
		wp_register_style( 'opcw-toast-notice-style', OPCW_PLUGIN_URL . 'assets/css/libs/jquery.toast.min.css', array(), OPCW_VERSION, 'all' );
		
		wp_enqueue_style( 'opcw-backend-styles' );
		wp_enqueue_style( 'opcw-toast-notice-style' );
		
		wp_enqueue_script( 'select2' );
		// wp_enqueue_style( 'select2' );
		wp_enqueue_style( 'woocommerce_admin_styles' );

		$this->opcw_print_translations_data();
	}

	
	/**
	 * Enqueue the frontend related scripts and styles for this plugin.
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function enqueue_frontend_scripts_and_styles() {
		$this->opcw_print_translations_data();
	}


}
