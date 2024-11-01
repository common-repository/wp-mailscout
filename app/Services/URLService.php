<?php

namespace MS\Services;

class URLService {
	const MAIN_MENU_SLUG = 'mailscout_plugin';
	const PAGE_INSTALLATION = 'ms_installation';
	const PAGE_CAMPAIGNS = 'ms_campaigns';
	const PAGE_CAMPAIGN_FORM = 'ms_campaign_form';
	const PAGE_SUBSCRIBERS = 'ms_subscribers';
	const PAGE_MAIL_ACCOUNTS = 'ms_mail_accounts';
	const PAGE_SETTINGS = 'ms_settings';
	const PAGE_HELP = 'ms_help';
	const PAGE_ABOUT = 'ms_about';

	/**
	 * Get page URL relative to the system
	 *
	 * @param $page
	 *
	 * @return string
	 */
	public static function GetURL( $page ) {
		return get_admin_url() . 'admin.php?page=' . $page;
	}

	/**
	 * Get page name
	 *
	 * @param $page
	 *
	 * @return string
	 */
	public static function GetPageID( $page ) {
		if ( $page === self::MAIN_MENU_SLUG ) {
			return 'toplevel_page_' . self::MAIN_MENU_SLUG;
		}

		return 'mailscout_page_' . $page;
	}

	/**
	 * determine if the requested page is $page
	 *
	 * @param $page
	 *
	 * @return bool
	 */
	public static function isPage( $page ) {
		return self::GetPageID( $page ) === get_current_screen()->id;
	}

	public static function AllMenuPages() {
		return [
			'dashboard'     => self::MAIN_MENU_SLUG,
			'installation'  => self::PAGE_INSTALLATION,
			'campaigns'     => self::PAGE_CAMPAIGNS,
			'campaign_form' => self::PAGE_CAMPAIGN_FORM,
			'mail_accounts' => self::PAGE_MAIL_ACCOUNTS,
			'subscribers'   => self::PAGE_SUBSCRIBERS,
			'settings'      => self::PAGE_SETTINGS,
			'help'      		=> self::PAGE_HELP,
			'about'         => self::PAGE_ABOUT,
		];
	}

	/**
	 * Get All menu page URL
	 *
	 * @return array
	 */
	public static function AllMenuPageURL() {
		return array_map( function ( $value ) {
			return self::GetURL( $value );
		}, self::AllMenuPages() );
	}

	/**
	 * Get all the menu pages
	 *
	 * @return array
	 */
	public static function AllMenuPageID() {
		return array_map( function ( $value ) {
			return self::GetPageID( $value );
		}, self::AllMenuPages() );
	}

	/**
	 * check if teh currently requested page is MS menu page
	 *
	 * @return bool
	 */
	public static function isMSMenuPage() {
		return in_array( get_current_screen()->id, self::AllMenuPageID() );
	}

	/**
	 * @return bool
	 */
	public static function isDashboard() {
		return get_current_screen()->id === 'toplevel_page_' . self::MAIN_MENU_SLUG;
	}
}
