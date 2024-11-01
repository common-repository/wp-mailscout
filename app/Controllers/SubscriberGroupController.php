<?php

namespace MS\Controllers;

use MS\Abstracts\Controller;
use MS\Concerns\SerializesMailMessage;
use MS\Repositories\CampaignMessageRepository;
use MS\Repositories\SubscriberGroupRepository;
use MS\Repositories\SubscriberRepository;
use MS\Services\Config;

class SubscriberGroupController extends Controller {
	use SerializesMailMessage;

	/**
	 * List all campaigns
	 *
	 * @return false|mixed|string
	 */
	public function index() {
		$campaigns = SubscriberGroupRepository::Instance()->getAll();

		return $this->response( $campaigns );
	}


	/**
	 * Store a campaign
	 *
	 * @return false|mixed|string
	 */
	public function store() {
		$subscribers = $this->request->get( 'subscribers' );
		$group_name  = $this->request->get( 'group' );

		$group_id = null;

		$group = SubscriberGroupRepository::Instance()->findByName( $group_name );
		if ( ! $group ) {
			$group_id = SubscriberGroupRepository::Instance()->store( $group_name );
		} else {
			$group_id = $group->id;
		}

		SubscriberRepository::Instance()->store( $group_id, $subscribers );

		return $this->response( [ 'message' => 'Subscribers added', 'group_id' => $group_id ] );
	}

	/**
	 * Get campaign information
	 *
	 * @return false|mixed|string
	 */
	public function view() {
		$campaign_id = $_GET['campaign_id'];

		$campaign = SubscriberGroupRepository::Instance()->find( $campaign_id );
		if ( ! $campaign ) {
			return $this->response( [ 'message' => 'Not found' ], 404 );
		}
		$subscribers = SubscriberRepository::Instance()->findByCampaignId( $campaign_id );

		$messages = CampaignMessageRepository::Instance()->getAll( $campaign_id );

		$message = $this->serializeMailMessage( $messages );

		return $this->response( array(
			'campaign'    => $campaign,
			'subscribers' => $subscribers,
			'message'     => $message,
		) );
	}

	/**
	 * Update a campaign
	 *
	 * @return false|mixed|string
	 */
	public function update() {
		$campaign_id = $this->request->get( 'campaign_id' );

		$campaign = SubscriberGroupRepository::Instance()->find( $campaign_id );

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

		if ( count( $fields ) == 0 ) {
			return $this->response( [ 'message' => 'Nothing to update' ] );
		}

		SubscriberGroupRepository::Instance()->update( $campaign_id, $fields );

		return $this->response( [ 'message' => 'Updated' ] );
	}
	/**
	 * update email subscriber list
	 */
	public function updateSubscriberList() {
		$subscribers = $this->request->get( 'subscribers' );
		$group_name  = $this->request->get( 'group' );

		$group_id = null;

		/**
		 * find group id by group name
		 */
		$subs_group_id = SubscriberGroupRepository::Instance()->findByName( $group_name );
		/**
		 * if no id found
		 * against that group name
		 * then add a new record
		 * then add subscriber to that group
		 */
		if( ! $subs_group_id ){
			$group_id = SubscriberGroupRepository::Instance()->store( $group_name );
			if($subscribers){
				SubscriberRepository::Instance()->store( $group_id, $subscribers );
				return $this->response( [ 'message' => 'New Subscribers added', 'group_id' => $group_id ] );
			}else{
				return $this->response( [ 'message' => 'Please upload a csv file to add', 'group_id' => $group_id ] );
			}
		}
		else{
			/**
			 * check if that table has some subscriber
			 * then get mail list by group id
			 */
			$group_id = $subs_group_id->id;
			$subs_group_lists = SubscriberRepository::Instance()->findEmailByGroupId( $group_id );
			/**
			 * if there is uploaded csv data
			 * then check uploaded data with mail lists
			 */
			if($subscribers){
				$my_list = [];
				foreach($subscribers as $s ){
					if(! in_array($s['email'], $subs_group_lists)){
						/**
						 * if no match found then insert
						 * to that existing group
						 */
						array_push($my_list, $s);
					}
				}
				SubscriberRepository::Instance()->store( $group_id, $my_list );
				return $this->response( [ 'message' => 'Subscribers Updated', 'group_id' => $group_id ] );
			}else{
				return $this->response( [ 'message' => 'Please upload a csv file to update', 'group_id' => $group_id ] );
			}
		}
	}

}
