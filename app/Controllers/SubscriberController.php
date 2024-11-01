<?php

namespace MS\Controllers;

use MS\Abstracts\Controller;
use MS\Repositories\CampaignRepository;
use MS\Repositories\SubscriberRepository;
use MS\Repositories\SubscriberGroupRepository;

class SubscriberController extends Controller {
	/**
	 * Get subscribers list
	 *
	 * @return false|mixed|string
	 */
	public function index() {
		$campaign_id = $this->request->get( 'campaign_id' );
		$subscribers = SubscriberRepository::Instance()->findByCampaignId( $campaign_id );

		return $this->response( $subscribers );
	}

	/**
	 * Store subscribers under a campaign
	 *
	 * @return false|mixed|string
	 */
	public function store() {
		$subscribers = $this->request->get( 'subscribers' );
		$group       = $this->request->get( 'group' );
		$msg         = ! $group['id'] ? 'New Subscriber Added' : 'Updated Subscriber List';

		/**
		 * need to
		 * check if there is any
		 * subscriber group with same name
		 */

		if ( ! $group['id'] ) {
			$existing_group = SubscriberGroupRepository::Instance()->findByName( $group['name'] );
			if ( $existing_group ) {
				$group['id'] = $existing_group->id;
			} else {
				$group['id'] = SubscriberGroupRepository::Instance()->store( $group['name'] );
			}
		}

		/**
		 * insert or update
		 * the data to database
		 * return only error.
		 * if everything is ok then
		 * nothing return.
		 */
		$error = SubscriberRepository::Instance()->store( $group['id'], $subscribers );

		/**
		 * get new subscriber lists
		 * or updated subscriber lists
		 */
		$new_subscribers = SubscriberRepository::Instance()->findByGroupId( $group['id'] );

		if ( $error ) {
			return $this->response( [ 'message' => $error ], 501 );
		}

		return $this->response( [ 'message' => $msg, 'group' => $group, 'new_subscribers' => $new_subscribers ] );
	}

	/**
	 * get all subscribers
	 */
	public function getSubscriberGroup() {
		$subscriberGroup = SubscriberGroupRepository::Instance()->getAll();

		return $this->response( $subscriberGroup );
	}

	/**
	 * get the subscriber lists
	 * by subscriber group id
	 */
	public function getSubscriberGroupLists() {
		$groupId         = $this->request->get( 'groupId' );
		$subscriberGroup = SubscriberRepository::Instance()->findByGroupId( $groupId );

		return $this->response( $subscriberGroup );
	}

	public function pullAllSubscribers() {
		$allSubscribers = SubscriberRepository::Instance()->getAll();

		return $this->response( $allSubscribers );
	}
	public function deleteGroup(){
		$deleted_grp_id = $this->request->get('id');
		/**
		 * delete all single subscriber
		 * that belongs to this group
		 */
		$subs_delete_status = SubscriberRepository::Instance()->deleteSubByGrpId( $deleted_grp_id );
		/**
		 * delete subscriber group
		 */
		$subs_grp_delete_status = SubscriberGroupRepository::Instance()->deleteSubGroup( $deleted_grp_id );
		/**
		 * return the deleted status
		 */
		return $this->response( [
			'subs_delete_status' => $subs_delete_status,
			'subs_grp_delete_statue' => $subs_grp_delete_status
		] );
	}

	public function deleteSingleSub(){
		$deleted_subs_id = $this->request->get('id');
		$subs_delete_status = SubscriberRepository::Instance()->deleteSingleSubscriber( $deleted_subs_id );
		return $this->response( [ 'subs_delete_status' => $subs_delete_status ] );
	}
	public function paginatedSubscribers(){
		$offsetNumber = $this->request->get('offsetNumber');
		$posts_per_page = $this->request->get('postsPerPage');
		$paged_subscriber = SubscriberRepository::Instance()->pagedSubscriber( $posts_per_page, $offsetNumber );
		return $this->response( $paged_subscriber );
	}

	public function allSubscribersCount(){
		$count = SubscriberRepository::Instance()->getTotalCount();
		return $this->response( $count );
	}

}
