<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://themesgrove.com/
 * @since      1.0.0
 *
 * @package    MailScout
 * @subpackage MailScout/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    MailScout
 * @subpackage MailScout/includes
 * @author     https://themesgrove.com/
 */
class MailScout_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		if( false !== ( $time = wp_next_scheduled( 'ms_send_mail' ) ) ) {
			wp_unschedule_event( $time, 'ms_send_mail' );
		}
	}

}
