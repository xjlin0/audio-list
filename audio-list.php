<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://xjlin0.github.io
 * @since             1.0.0
 * @package           Audio_List
 *
 * @wordpress-plugin
 * Plugin Name:       Audio list
 * Plugin URI:        https://github.com/xjlin0/audio-list
 * Description:       A Wordpress plug-in for showing players for audio files in the remote folders, based on Dennis's code. (Audio files meta info stored in local database)
 * Version:           1.0.0
 * Author:            Jack Lin
 * Author URI:        https://xjlin0.github.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       audio-list
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AUDIO_LIST_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-audio-list-activator.php
 */
function activate_audio_list() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-audio-list-activator.php';
	Audio_List_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-audio-list-deactivator.php
 */
function deactivate_audio_list() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-audio-list-deactivator.php';
	Audio_List_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_audio_list' );
register_deactivation_hook( __FILE__, 'deactivate_audio_list' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-audio-list.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_audio_list() {

	$plugin = new Audio_List();
	$plugin->run();

}
run_audio_list();
