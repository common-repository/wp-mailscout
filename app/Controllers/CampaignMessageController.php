<?php

namespace MS\Controllers;

use DateTime;
use MS\Abstracts\Controller;
use MS\Concerns\SerializesMailMessage;
use MS\Repositories\CampaignMessageRepository;
use MS\Repositories\CampaignRepository;

class CampaignMessageController extends Controller {

	use SerializesMailMessage;

	private static $campaign_id;

	/**
	 * List all campaign messages
	 *
	 * @return false|mixed|string
	 */
	public function index() {
		$campaign_id = $_POST['campaign_id'];
		if ( ! CampaignRepository::Instance()->find( $campaign_id ) ) {
			return $this->response( [ 'message' => 'Campaign does not exist' ], 404 );
		}
		$messages = CampaignMessageRepository::Instance()->getAll( $campaign_id );

		return $this->response( $messages );
	}

	/**
	 * Store a campaign
	 *
	 * @return false|mixed|string
	 */
	public function store() {
		self::$campaign_id = $this->request->get( 'campaign_id' );
		if ( ! CampaignRepository::Instance()->find( self::$campaign_id ) ) {
			return $this->response( [ 'message' => 'Campaign not found' ], 404 );
		}
		$data              = $this->request->all();
		$parent            = $this->extractMessage( $data );

		$parent_id = CampaignMessageRepository::Instance()->store( $parent );

		$followups = $this->request->get( 'followups' );
		$delay     = 0;
		$followup_ids = array();
		foreach ( $followups as $followup ) {
			$fields                 = $this->extractMessage( $followup );
			$fields['parent']       = $parent_id;
			$fields['message_type'] = 'followup';
			$delay                  += $fields['delay'];
			$fields['delay']        = $delay;
			$followup_ids[] = CampaignMessageRepository::Instance()->store( $fields );
		}

		// delete all the followups where id not in $followup_ids
		CampaignMessageRepository::Instance()->deleteFollowupsIfNotIn($parent_id, $followup_ids);

		$messages = CampaignMessageRepository::Instance()->getAll( self::$campaign_id );
		$message  = $this->serializeMailMessage( $messages );

		return $this->response( $message );
	}

	private function extractMessage( $array ) {
		$fields                 = [];
		$fields['id']           = isset( $array['id'] ) && $array['id'] !== '' ? (int) $array['id'] : null;
		$fields['campaign_id']  = self::$campaign_id;
		$fields['subject']      = $array['subject'] !== '' ? trim( $array['subject'] ) : null;
		$fields['message']      = $array['message'];
		$fields['message_type'] = isset( $array['message_type'] ) ? trim( $array['message_type'] ) : null;
		$fields['reason']       = null;
		$fields['delay']        = 0;
		$fields['link']         = null;

		switch ( $fields['message_type'] ) {
			case 'main':
				break;
			case 'followup':
				$fields['reason'] = $array['reason'];
				$fields['delay']  = $array['delay'];
				break;
			case 'onclick':
				$fields['link']  = $array['link'];
				$fields['delay'] = $array['delay'];
				break;
			case 'drips':
				$fields['delay'] = $array['delay'];
				break;
			default:
				// something unexpected
		}

		return $fields;
	}

	/**
	 * Get campaign information
	 *
	 * @return false|mixed|string
	 */
	public function view() {
		$campaign_id = $this->request->get( 'campaign_id' );

		$messages = CampaignMessageRepository::Instance()->getAll( $campaign_id );
		$message  = $this->serializeMailMessage( $messages );

		return $this->response( [ 'data' => $message, 'message' => 'success' ] );
	}
}
