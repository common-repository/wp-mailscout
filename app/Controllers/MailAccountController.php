<?php

namespace MS\Controllers;

use MS\Abstracts\Controller;
use MS\Services\GMailService;
use MS\Repositories\MailAccountRepository;

class MailAccountController extends Controller {
	/**
	 * Get all mails
	 */
	public function index() {
		$all = MailAccountRepository::Instance()->getAll();

		return $this->response( $all );
	}

	/**
	 * Add new Mail
	 */
	public function store() {
		$auth_code = $this->request->get( 'auth_code' );

		$gmail = new GMailService();

		try {
			$gmail->init();
		} catch ( \Exception $e ) {
			return $this->response( array( 'message' => $e->getMessage() ), 503 );
		}
		try {
			$accessToken = $gmail->getAccessToken( $auth_code );
		} catch ( \Exception $e ) {
			return $this->response( [ 'message' => $e->getMessage() ], 503 );
		}
		// make sure we got an access token
		if ( ! $accessToken || isset( $accessToken['error'] ) ) {
			return $this->response( [ 'message' => 'Access Token could not be retrieved: error -> ' . $accessToken['error'] . '  message -> ' . $accessToken['error_description'] ], 406 );
		}
		// make sure the access token has refresh token attached
		if ( ! $gmail->hasRefreshToken() ) {
			return $this->response( [ 'message' => 'The access token does not have a refresh token with it' ], 417 ); // 417 = Expectation Failed
		}

		$userInfo = $gmail->getUserInfo();

		// store
		$id = MailAccountRepository::Instance()->store(
			$userInfo->getName(),
			$userInfo->getEmail(),
			$userInfo->getPicture(),
			$accessToken
		);

		return $this->response( array( 'id' => $id ) );
	}

	/**
	 * Update google credentials
	 */
	public function updateGoogleCredentials() {
		if ( empty( $_FILES ) ) {
			return $this->response( [ 'message' => 'No file uploaded' ] );
		}

		$file = $_FILES['file'];

		if ( $file['type'] !== 'application/json' ) {
			return $this->response( [ 'message' => 'Invalid file uploaded' ] );
		}

		if ( ! move_uploaded_file( $file['tmp_name'], GMailService::AuthFile() ) ) {
			return $this->response( [ 'message' => 'Could not move uploaded file' ] );
		}

		$read_file            = json_decode( file_get_contents( GMailService::AuthFile() ), true );
		$client_secret        = array_key_exists( 'client_secret', $read_file['web'] );
		$client_redirect_uris = array_key_exists( 'redirect_uris', $read_file['web'] );

		$site_main_redirect_url = GMailService::getRedirectUri();

		$gmail = new GMailService();

		try {
			$gmail->init();
		} catch ( \Exception $e ) {
			return $this->response( array( 'message' => $e->getMessage() ), 503 );
		}

		$authUrl = $gmail->getAuthUrl();
		if ( $client_redirect_uris ) {
			$redirectUriMatched = in_array( $site_main_redirect_url, $read_file['web']['redirect_uris'] );
		} else {
			$redirectUriMatched = false;
		}

		return $this->response( [
			'message'            => 'File Uploaded Successfully',
			'client_secret'      => $client_secret,
			'redirectUriMatched' => $redirectUriMatched,
			'file_status'        => true,
			'auth_url'           => $authUrl ? $authUrl : undefined
		] );
	}
	/**
	 * delete mail account
	 * by mail ID
	 */
	public function deleteMailAccount(){
		$mail_id = $this->request->get('id');
		$deleteStatus = MailAccountRepository::Instance()->deleteMailAccount($mail_id);
		return $this->response([
			'delete_status' => $deleteStatus
		]);

	}

}
