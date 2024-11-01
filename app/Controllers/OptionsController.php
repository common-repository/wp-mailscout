<?php

namespace MS\Controllers;

use DateTime;
use MS\Abstracts\Controller;
use MS\Repositories\CampaignRepository;
use MS\Repositories\MessageQueueRepository;

class OptionsController extends Controller {
	public function update() {
		$campaignRepository = CampaignRepository::Instance();
		$campaign           = $campaignRepository->find( $this->request->get( 'campaign_id' ) );
		$send_at            = date( 'Y-m-d H:i:s', strtotime( $this->request->get( 'send_at' ) ) );
		$lead               = $this->request->get( 'lead' );
		$enable_status 			= (int)$campaign->enabled;
		$campaign_enable_status = $enable_status === 0 ? 1 : $campaign->enabled;
		/**
		 * check below things
		 * if user update or add
		 * =====================
		 * subscriber
		 * subject and message and followup
		 * send time
		 * enable status
		 * lead status
		 * if ( $campaign->send_at != $send_at || $campaign->enabled === 0 || $campaign->lead !== $lead ) {
			$status = $campaignRepository->update( $campaign->id, [ 'send_at' => $send_at, 'enabled' => 1, 'lead' => $lead ] );
		}
		 */
		$status = $campaignRepository->update(
			$campaign->id,
			[ 'send_at' => $send_at, 'enabled' => $campaign_enable_status, 'lead' => $lead ]
		);
		return $this->response( [
			'status' => $status ? true : false,
			'message' => 'Updated'
		] );
	}
}
