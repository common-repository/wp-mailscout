<?php
/**
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://themesgrove.com/
 * @package           MailScout
 *
 * @wordpress-plugin
 * Plugin Name:       WP MailScout
 * Plugin URI:        https://wordpress.org/plugins/wp-mailscout/
 * Description:       The Best Email Outreach Plugin
 * Version:           0.1.1
 * Author:            Themesgrove
 * Author URI:        https://themesgrove.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpmailscout
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once 'vendor/autoload.php';

/**
 * Currently plugin version.
 */
define( 'MAILSCOUT_APP_NAME', 'WP-MailScout' );
define( 'MAILSCOUT_VERSION', '0.1.1' );
define( 'MAILSCOUT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mailscout-activator.php
 */
function mailscout_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailscout-activator.php';
	MailScout_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mailscout-deactivator.php
 */
function mailscout_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailscout-deactivator.php';
	MailScout_Deactivator::deactivate();
}

/**
 * Get file path for MailScout plugin
 *
 * @param $file
 *
 * @return string
 */
function mailscout_get_file_path( $file ) {
	return MAILSCOUT_PATH . $file;
}

register_activation_hook( __FILE__, 'mailscout_activate' );
register_deactivation_hook( __FILE__, 'mailscout_deactivate' );
register_activation_hook(__FILE__, 'mailscout_plugin_redirect_hook');

/**
 * redirect to the installation page
 * after active the plugin
 */
add_action('admin_init', 'mailscout_plugin_redirect');
function mailscout_plugin_redirect_hook() {
    add_option('ms_activation_redirect', true);
}
function mailscout_plugin_redirect() {
    if (get_option('ms_activation_redirect', false)) {
        delete_option('ms_activation_redirect');
        wp_redirect(admin_url( 'admin.php?page=ms_installation' ));
    }
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mailscout.php';

/**
 * defines a 30 minute cron schedule
 *
 * @param $schedules
 *
 * @return mixed
 */
function mailscout_cron_schedules( $schedules ) {
	if ( ! isset( $schedules["30min"] ) ) {
		$schedules["30min"] = array(
			'interval' => 30 * 60,
			'display'  => __( 'Once every 30 minutes' )
		);
	}
	if ( ! isset( $schedules["10sec"] ) ) {
		$schedules["10sec"] = array(
			'interval' => 10,
			'display'  => __( 'Once every 10 seconds' )
		);
	}
	if ( ! isset( $schedules["1min"] ) ) {
		$schedules["1min"] = array(
			'interval' => 60,
			'display'  => __( 'Once every 1 min' )
		);
	}

	return $schedules;
}

add_filter( 'cron_schedules', 'mailscout_cron_schedules' );
add_action( 'ms_send_mail', function () {
	\MS\Services\SendMail::run();
} );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function mailscout_run() {

	/**
	 * error reporting off
	 * if you need to error reporting on
	 * then you need to comment out
	 * below two line of code
	 * ini_set( 'display_errors', 1 );
	 * error_reporting( E_ALL );
	 */

	$plugin = new MailScout();
	$plugin->run();

	/**
	 * if you need to set permalink
	 * with postname structure then
	 * add below line
	 * $wp_rewrite->set_permalink_structure('/%postname%/');
	 */
	add_action( 'init', function() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	} );

}

mailscout_run();

