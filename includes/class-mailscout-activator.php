<?php

/**
 * Fired during plugin activation
 *
 * @link       https://themesgrove.com/
 * @since      1.0.0
 *
 * @package    MailScout
 * @subpackage MailScout/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    MailScout
 * @subpackage MailScout/includes
 * @author     https://themesgrove.com/
 */
class MailScout_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		/* @var wpdb $wpdb */
		global $wpdb;

		$db_version      = '1.0.0';
		$charset_collate = $wpdb->get_charset_collate();

		// mail accounts table
	$table_mails     = $wpdb->prefix . 'ms_mail_accounts';
	$sql_table_mails = "CREATE TABLE IF NOT EXISTS $table_mails (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL,
	name VARCHAR(100),
	signature TEXT,
	access_token TEXT NOT NULL,
	avatar VARCHAR(150)
) $charset_collate;";

	// campaigns listings
	$table_campaigns     = $wpdb->prefix . 'ms_campaigns';
	$sql_table_campaigns = "CREATE TABLE IF NOT EXISTS $table_campaigns (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	mail_account_id INT UNSIGNED NOT NULL,
	title VARCHAR(100) NOT NULL,
	state VARCHAR(255) NOT NULL,
	subscriber_group_id INT UNSIGNED,
	send_at DATETIME NOT NULL,
	enabled TINYINT NOT NULL DEFAULT '0',
	phase VARCHAR(20) NOT NULL DEFAULT 'main',
	step TINYINT NOT NULL DEFAULT 0,
	last_sent DATETIME NOT NULL,
	subscriber_offset INT UNSIGNED NOT NULL DEFAULT '0',
	lead VARCHAR(20),
	done TINYINT NOT NULL DEFAULT '0'
) $charset_collate;";

		// campaign list
	$table_subscriber_groups     = $wpdb->prefix . 'ms_subscriber_groups';
	$sql_table_subscriber_groups = "CREATE TABLE IF NOT EXISTS $table_subscriber_groups (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100)
) $charset_collate;";

		// subscribers

	// campaigns listings
	$table_subscribers     = $wpdb->prefix . 'ms_subscribers';
	$sql_table_subscribers = "CREATE TABLE IF NOT EXISTS $table_subscribers (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100),
	email VARCHAR(100) NOT NULL,
	subscriber_group_id INT UNSIGNED NOT NULL
) $charset_collate;";


	// mail templates
	$table_templates     = $wpdb->prefix . 'ms_templates';
	$sql_table_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	category VARCHAR(100) NOT NULL,
	subject TEXT(300) NOT NULL,
	message TEXT NOT NULL,
	fields TEXT
) $charset_collate;";


	// campaign messages
	$table_campaign_messages     = $wpdb->prefix . 'ms_campaign_messages';
	$sql_table_campaign_messages = "CREATE TABLE IF NOT EXISTS $table_campaign_messages (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	campaign_id INT UNSIGNED NOT NULL,
	subject VARCHAR(255),
	message TEXT NOT NULL,
	message_type VARCHAR(20) NOT NULL,
	reason VARCHAR(20),
	parent INT UNSIGNED,
	delay TINYINT NOT NULL DEFAULT '0',
	link VARCHAR(255),
	sent TINYINT NOT NULL DEFAULT '0'
	) $charset_collate;";

	// broadcasts
	$table_broadcasts     = $wpdb->prefix . 'ms_broadcasts';
	$sql_table_broadcasts = "CREATE TABLE $table_broadcasts (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	hash VARCHAR(20) NOT NULL,
	campaign_id INT UNSIGNED NOT NULL ,
	subscriber_id INT UNSIGNED NOT NULL ,
	subject VARCHAR(255) NOT NULL ,
	message TEXT NOT NULL ,
	sent_at DATETIME NOT NULL ,
	message_id VARCHAR(32) NOT NULL,
	thread_id VARCHAR(32) NOT NULL,
	opened TINYINT NOT NULL DEFAULT '0' ,
	clicked TINYINT NOT NULL DEFAULT '0' ,
	replied TINYINT NOT NULL DEFAULT '0' ,
	message_type VARCHAR(50) NOT NULL ,
	reply TEXT
	) $charset_collate;";

		// install the table
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_table_mails );
		dbDelta( $sql_table_campaigns );
		dbDelta( $sql_table_subscriber_groups );
		dbDelta( $sql_table_subscribers );
		dbDelta( $sql_table_templates );
		dbDelta( $sql_table_campaign_messages );
		dbDelta( $sql_table_broadcasts );

		add_option( 'mailscout_db_version', $db_version );

		// activate the cron job
		if ( ! wp_next_scheduled( 'ms_send_mail' ) ) {
			wp_schedule_event( time(), '1min', 'ms_send_mail' );
		}
	}
}
