<?php
/**
 * Plugin Name: TMS Materials
 * Plugin URI: https://github.com/devgeniem/tms-plugin-materials
 * Description: TMS Materials
 * Version: 0.0.1
 * Requires PHP: 7.4
 * Author: Geniem Oy
 * Author URI: https://geniem.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tms-plugin-materials
 * Domain Path: /languages
 */

use TMS\Plugin\Materials\Plugin;

// Check if Composer has been initialized in this directory.
// Otherwise we just use global composer autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Get the plugin version.
$plugin_data    = get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' );
$plugin_version = $plugin_data['Version'];

$plugin_path = __DIR__;

// Initialize the plugin.
Plugin::init( $plugin_version, $plugin_path );

if ( ! function_exists( 'tms_plugin_materials' ) ) {
    /**
     * Get the plugin instance.
     *
     * @return Plugin
     */
    function tms_plugin_materials() : Plugin {
        return Plugin::plugin();
    }
}
