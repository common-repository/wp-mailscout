<?php

namespace MS\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Oauth2;
use InvalidArgumentException;
use MS\Listeners\URLRewriteListener;

class GMailService {
	/**
	 * @var Google_Client
	 */
	private $client;

	/**
	 * @return null|string
	 * @throws \Exception
	 */
	public function init() {
		if ( ! file_exists( GMailService::AuthFile() ) ) {
			wp_die( "You need to upload google oauth credentials first" );
		}
		$this->client = new Google_Client();
		$this->client->setApplicationName( 'MailScout' );
		$this->client->setScopes( [
			Google_Service_Gmail::GMAIL_SEND,
			Google_Service_Gmail::GMAIL_READONLY,
			Google_Service_Oauth2::USERINFO_PROFILE,
			Google_Service_Oauth2::USERINFO_EMAIL,
		] );
		try {
			$this->client->setAuthConfig( static::AuthFile() );
		} catch ( \Exception $e ) {
			throw $e;
		}
		$this->client->setAccessType( 'offline' );
		$this->client->setRedirectUri( static::getRedirectUri() );

		return null;
	}

	/**
	 * Get the redirect URI
	 *
	 * @return string
	 */
	public static function getRedirectUri() {
		return site_url() . '/' . URLRewriteListener::REWRITE_URL . '/' . URLRewriteListener::OAUTH_PATH;
	}

	/**
	 * @return string
	 */
	public function getAuthUrl() {
		$auth_url = $this->client->createAuthUrl();

		return $auth_url;
	}

	/**
	 * Redirect user to google oauth
	 */
	public function redirectToAuth() {
		header( 'location: ' . $this->getAuthUrl() );
		die( $this->getAuthUrl() );
		//wp_die($this->getAuthUrl());
	}

	/**
	 * @param $authCode
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getAccessToken( $authCode ) {
		try {
			// Exchange authorization code for an access token.
			$accessToken = $this->client->fetchAccessTokenWithAuthCode( $authCode );

			// Check to see if there was an error.
			if ( array_key_exists( 'error', $accessToken ) ) {
				return $accessToken;
			}
			$this->client->setAccessToken( $accessToken );

			return $accessToken;
		} catch ( \Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Check if the access token has refresh token
	 *
	 * @return bool
	 */
	public function hasRefreshToken() {
		return $this->client->getRefreshToken() !== null;
	}

	/**
	 * @param $accessToken
	 *
	 * @return GMailService
	 */
	public function setAccessToken( $accessToken ) {
		if ( is_string( $accessToken ) ) {
			$accessToken = json_decode( $accessToken, 1 );
		}

		$this->client->setAccessToken( $accessToken );
		$this->refreshToken();

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function refreshToken() {
		if ( $this->client->isAccessTokenExpired() ) {
			$this->client->fetchAccessTokenWithRefreshToken( $this->client->getRefreshToken() );

			return $this->client->getAccessToken();
		}

		return null;
	}

	/**
	 * Get self email address
	 *
	 * @return \Google_Service_Oauth2_Userinfoplus
	 */
	public function getUserInfo() {
		$service = new Google_Service_Oauth2( $this->client );

		return $service->userinfo->get();
	}

	/**
	 * Pulls replies from GMail server
	 *
	 * @param $threadId
	 *
	 * @return \Google_Service_Gmail_Thread
	 */
	public function pullThread( $threadId ) {
		$gmail_service = new Google_Service_Gmail( $this->client );

		return $gmail_service->users_threads->get( 'me', $threadId );
	}

	/**
	 * Send email to recipients
	 *
	 * @param $from_name
	 * @param $from_email
	 * @param $subject
	 * @param $body
	 * @param $recipient
	 *
	 * @param null $threadId
	 *
	 * @return Google_Service_Gmail_Message
	 */
	public function sendMail( $from_name, $from_email, $subject, $body, $recipient, $threadId = null ) {
		$strRawMessage = "From: {$from_name}<{$from_email}>\r\n";
		$strRawMessage .= "To: {$recipient->name}<{$recipient->email}>\r\n";
		$strRawMessage .= "Reply-To: {$from_name} <{$from_email}>\r\n";
		$strRawMessage .= 'Subject: =?utf-8?B?' . base64_encode( $subject ) . "?=\r\n";
		$strRawMessage .= "MIME-Version: 1.0\r\n";
		$strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
		$strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";

		$message = new Google_Service_Gmail_Message();
		$message->setRaw( rtrim( strtr( base64_encode( $strRawMessage . $body ), '+/', '-_' ), '=' ) );

		if ( $threadId ) {
			$message->setThreadId( $threadId );
		}

		$service = new Google_Service_Gmail( $this->client );

		return $service->users_messages->send( 'me', $message );
	}


	public static function AuthFile() {
		return MAILSCOUT_PATH . 'data/credentials.json';
	}

}