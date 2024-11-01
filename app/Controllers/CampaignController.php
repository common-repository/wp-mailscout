<?php

namespace MS\Controllers;

use MS\Abstracts\Controller;
use MS\Concerns\SerializesMailMessage;
use MS\Repositories\CampaignMessageRepository;
use MS\Repositories\CampaignRepository;
use MS\Repositories\SubscriberRepository;
use MS\Services\Config;
use MS\Services\URLService;

class CampaignController extends Controller {
	use SerializesMailMessage;

	/**
	 * List all campaigns
	 *
	 * @return false|mixed|string
	 */
	public function index() {
		$campaigns = CampaignRepository::Instance()->getAll( true );

		return $this->response( [ 'campaigns' => $campaigns ] );
	}

	/**
	 * Store a campaign
	 *
	 * @return false|mixed|string
	 */
	public function store() {
		if ( $this->request->has( 'campaign_id' ) ) {
			return $this->update();
		}
		$title           = $this->request->get( 'title' );
		$mail_account_id = $this->request->get( 'mail_account_id' );
		if ( CampaignRepository::Instance()->hasSameTitle( $title ) ) {
			return $this->response( [ 'message' => 'Another campaign has exact same name as you\'ve entered.' ], 422 );
		}
		$id = CampaignRepository::Instance()->store( $title, $mail_account_id );

		return $this->response( [ 'goto' => URLService::GetURL( URLService::PAGE_CAMPAIGN_FORM ) . '&campaign_id=' . $id ] );
	}

	/**
	 * Get campaign information
	 *
	 * @return false|mixed|string
	 */
	public function view() {
		$campaign_id = $_GET['campaign_id'];

		$campaign = CampaignRepository::Instance()->find( $campaign_id );
		if ( ! $campaign ) {
			return $this->response( [ 'message' => 'Not found' ], 404 );
		}
		$campaign->subscribers = SubscriberRepository::Instance()->findByCampaignId( $campaign_id );

		$messages = CampaignMessageRepository::Instance()->getAll( $campaign_id );

		$campaign->message = $this->serializeMailMessage( $messages );

		return $this->response( $campaign );
	}

	/**
	 * Update a campaign
	 *
	 * @return false|mixed|string
	 */
	public function update() {
		$campaign_id = $this->request->get( 'campaign_id' );
		$group_id    = $this->request->get( 'group_id' );

		$campaign = CampaignRepository::Instance()->find( $campaign_id );

		if ( $campaign == null ) {
			return $this->response( [ 'message' => 'Not found' ], 404 );
		}

		$fields = [];

		if ( $this->request->has( 'mail_account_id' ) && $this->request->get( 'mail_account_id' ) != $campaign->mail_account_id ) {
			$fields['mail_account_id'] = $this->request->get( 'mail_account_id' );
		}

		if ( $this->request->has( 'title' ) && $this->request->get( 'title' ) != $campaign->title ) {
			$fields['title'] = $this->request->get( 'title' );
		}

		if ( $this->request->has( 'state' ) ) {
			$fields['state'] = json_encode( explode( ",", $this->request->get( 'state' ) ) );
		}
		/**
		 * if user change the existing campaign
		 * subscriber group
		 * then update campaign -> subscriber group id
		 */
		if ( $this->request->has( 'group_id' ) && $this->request->get( 'group_id' ) != $campaign->subscriber_group_id ) {
			$fields['subscriber_group_id'] = $this->request->get( 'group_id' );
		}

		if ( count( $fields ) == 0 ) {
			return $this->response( [ 'message' => 'Nothing to update' ] );
		}

		CampaignRepository::Instance()->update( $campaign_id, $fields );

		return $this->response( [
			'message' => 'Updated'
		] );
	}

	public function updatedSubscriberLists() {
		$updated_lists = SubscriberRepository::Instance()->findByGroupId( $this->request->get( 'updated_group_id' ) );

		return $this->response( [ $updated_lists ] );
	}

	public function deleteCampaign() {
		$campaign_id = $this->request->get('id');
		/**
		 * delete campaign
		 */
		$delete_status = CampaignRepository::Instance()->deleteCampaign( $campaign_id );
		/**
		 * delete campaing
		 * related message
		 */
		$msg_delete_status = CampaignMessageRepository::Instance()->deleteCampaignMessage( $campaign_id );

		return $this->response( [
			'cam_delete_status' => $delete_status,
			'msg_delete_status' => $msg_delete_status
		] );
	}

}
