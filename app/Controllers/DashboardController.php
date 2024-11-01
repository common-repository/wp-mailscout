<?php

namespace MS\Controllers;

use MS\Services\GMailService;
use MS\Abstracts\Controller;
use MS\Repositories\SubscriberRepository;
use MS\Repositories\MailAccountRepository;
use MS\Repositories\CampaignRepository;
use MS\Repositories\SubscriberGroupRepository;

class DashboardController extends Controller {

	/**
	 * Check if there is any credential
	 * json uploaded or not
	 */
	public function getGoogleCredentials() {
		if(file_exists(GMailService::AuthFile())){
			$read_file = json_decode( file_get_contents( GMailService::AuthFile() ), true );
			if ( $read_file ) {

				$client_secret          = array_key_exists( 'client_secret', $read_file['web'] );
				$client_redirect_uris   = array_key_exists( 'redirect_uris', $read_file['web'] );
				$site_main_redirect_url = GMailService::getRedirectUri();
				if($client_redirect_uris){
					$redirectUriMatched     = in_array( $site_main_redirect_url, $read_file['web']['redirect_uris'] );
				}else{
					$redirectUriMatched = false;
				}

				return $this->response( [
					'file_status'        => true,
					'client_secret'      => $client_secret,
					'redirectUriMatched' => $redirectUriMatched
				] );
			}
		}else{
			return $this->response( [
				'file_status'        => false,
				'message'        => 'Google Authentication credentials file is not uploaded. Please upload it.',
			] );
		}
	}

	public function checkCronStatus() {
		/**
		 * useful functions for cron check
		 * print_r( _get_cron_array() )
		 * wp_next_scheduled( 'ms_send_mail' )
		 */
		$next_run_time = wp_get_schedule( 'ms_send_mail' );
		$cron_doing    = wp_doing_cron();

		return $this->response( [ "cron_details" => $next_run_time, "cron_doing" => $cron_doing ] );
	}

	public function getAllInfo() {
		$total_subscriber = SubscriberRepository::Instance()->getTotalCount();
		$total_mail_account = MailAccountRepository::Instance()->getTotalCount();
		$total_campaign = CampaignRepository::Instance()->getTotalCount();
		$total_subscriber_group = SubscriberGroupRepository::Instance()->getTotalCount();

		return $this->response( [
			'campaigns' => $total_campaign ? $total_campaign : 0,
			'mail_accounts' => $total_mail_account ? $total_mail_account : 0,
			'subscribers' => $total_subscriber ? $total_subscriber : 0,
			'subscriber_groups' => $total_subscriber_group ? $total_subscriber_group : 0,
		] );
	}

}
