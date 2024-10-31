<?php
/**
 * Opal Product Collection for WooCommerce
 *
 * @package       opal-product-collection-woocommerce
 * @author        WPOPAL
 * @version       1.1.5
 *
 * @wordpress-plugin
 * Plugin Name:   Opal Product Collection for WooCommerce
 * Plugin URI:    https://wpopal.com/opal-product-collection-woocommerce
 * Description:   An innovative tool designed to streamline content organization and product management. This plugin empowers users to create and manage collections effortlessly within their WordPress environment.
 * Version:       1.1.5
 * Author:        WPOPAL
 * Author URI:    https://wpopal.com
 * License:       GPLv2 or later
 * License URI:   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:   opal-product-collection-woocommerce
 * Domain Path:   /languages
 * Requires Plugins: woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Plugin name
define( 'OPCW_NAME', 'Opal Product Collection for WooCommerce' );
define( 'OPCW_TEXTDOMAIN', 'opal-product-collection-woocommerce' );
define( 'OPCW_TAXONOMY', 'opcw-collection' );

// Plugin version
define( 'OPCW_VERSION', '1.1.5' );

// Plugin Root File
define( 'OPCW_PLUGIN_FILE', __FILE__ );

// Plugin base
define( 'OPCW_PLUGIN_BASE', plugin_basename( OPCW_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'OPCW_PLUGIN_DIR',	plugin_dir_path( OPCW_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'OPCW_PLUGIN_URL',	plugin_dir_url( OPCW_PLUGIN_FILE ) );

define(	'OPCW_UPLOAD_DIR', 'opcw_uploads' );
define(	'OPCW_CRON_HOOK', 'opcw_schedule_scan' );
define(	'OPCW_SETTINGS_KEY', 'opcw_settings_key' );

/**
 * Load the main class for the core functionality
 */
require_once OPCW_PLUGIN_DIR . 'includes/class-opal-product-collection-woocommerce.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  LexusTeam
 * @since   1.1.5
 * @return  object|OPCW_Start_Instance
 */
function opcw() {
	return OPCW_Start_Instance::instance();
}
opcw();
