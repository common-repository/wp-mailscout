<?php

namespace MS\Controllers;

use MS\Abstracts\Controller;
use MS\Concerns\SerializesMailMessage;
use MS\Repositories\CampaignMessageRepository;
use MS\Repositories\CampaignRepository;
use MS\Repositories\MailAccountRepository;
use MS\Repositories\SubscriberRepository;

class MessageQueueController extends Controller {

	use SerializesMailMessage;

	/**
	 * Get campaign information
	 *
	 * @return false|mixed|string
	 */
	public function view() {
		$messages   = CampaignMessageRepository::Instance()->findByCampaignId( $this->request->get( 'campaign_id' ) );
		$subscriber = SubscriberRepository::Instance()->find( $this->request->get( 'subscriber_id' ) );
		$sender     = CampaignRepository::Instance()->getMailAccount( $this->request->get( 'campaign_id' ) );

		$replacements = $this->getReplacements( $subscriber, $sender );

		$message = $this->serializeMailMessage( $messages, $replacements );

		return $this->response( $message );
		// return $this->response($subscriber);
	}

	/**
	 * get selected subscriber group id
	 */
	public function getSubsGrpId() {
		$subs_grp_id = CampaignMessageRepository::Instance()->findSubscriberGroupIdByCampaignId( $this->request->get( 'campaign_id' ) );
		$lists       = SubscriberRepository::Instance()->findByGroupId( $subs_grp_id[0]->subscriber_group_id );

		return $this->response( [ $lists ] );
	}
}
