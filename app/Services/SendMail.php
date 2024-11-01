<?php

namespace MS\Services;

use ErrorException;
use MS\Concerns\SerializesMailMessage;
use MS\Listeners\URLRewriteListener;
use MS\Repositories\BroadcastRepository;
use MS\Repositories\CampaignRepository;
use MS\Repositories\MailAccountRepository;
use MS\Repositories\SubscriberRepository;
use MS\Repositories\CampaignMessageRepository;

class SendMail {

	use SerializesMailMessage;

	/** @var GMailService */
	private $gMail;
	/** @var \wpdb */
	private $wpdb;

	private $sender;

	/**
	 * Number of subscribers for sending mails in each call
	 */
	const LIMIT = 10;

	const DEBUGGING = true;

	public function rcp() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}

	public function rc() {
		return $this->rcp() . $this->rcp() . $this->rcp();
	}

	public function _p( $t ) {
		if ( self::DEBUGGING ) {
			echo '<p style="color: ', $this->rc(), '">', $t, '</p>';
		}
	}

	/**
	 * Run the cron
	 */
	public static function run() {
		( new static() );
	}

	/**
	 * SendMail constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		/**
		 * Start the cron process
		 */
		$this->runCron();
	}

	/**
	 * Starts the cron processing
	 */
	private function runCron() {
		$this->_p( "Pulling campaign" );
		// pull the next campaign that have mails that can be sent
		$activatedCampaign = CampaignRepository::Instance()->getActivatedCampaign();
		$this->_p( "Pulled campaign" );
		/**
		 * if there is not campaign to send now then
		 * kill the process
		 */
		if ( ! $activatedCampaign ) {
			$this->_p( "No campaign to work on" );

			return;
		}

		/**
		 * We have a campaign that requires mails to be sent
		 */
		$this->_p( "Processing campaign" );
		$this->processCampaign( $activatedCampaign );
	}

	/**
	 * Process campaign
	 *
	 * @param $campaign
	 *
	 * @return void
	 */
	private function processCampaign( $campaign ) {
		// get the subscribers starting from the offset
		$this->_p( "Pulling subscribers" );
		$subscribers = SubscriberRepository::Instance()->getNextSubscribers( $campaign->subscriber_group_id, $campaign->subscriber_offset, static::LIMIT );
		$this->_p( "Pulled subscribers" );
		if ( count( $subscribers ) == 0 ) {
			$this->_p( "No more subscriber to send mail" );
			/**
			 *  no more subscribers left to send mail to
			 */
			if ( $campaign->phase === 'main' ) {
				// main message sent to all the subscribers of the associated group, move to followup
				$this->_p( "Moving to `followup` from `main` message" );
				CampaignRepository::Instance()->update( $campaign->id,
					[
						'phase'             => 'followup',
						'subscriber_offset' => 0
					] );
				// mark main message as sent
				$this->_p( "Marking main message as sent for this campaign" );
				$main_message = CampaignMessageRepository::Instance()->getMainMessage( $campaign->id );
				CampaignMessageRepository::Instance()->update( $main_message->id, [ 'sent' => 1 ] );
			} elseif ( $campaign->phase === 'followup' ) {
				// one round followup has been sent
				$this->_p( "Moving to next followup message" );
				CampaignRepository::Instance()->update( $campaign->id,
					[
						'step'              => $campaign->step + 1,
						'subscriber_offset' => 0
					] );
				// mark next sendable message as sent
				$this->_p( "Marking current followup mail as sent" );
				$followup = CampaignMessageRepository::Instance()->getNextMessage( $campaign->id );
				CampaignMessageRepository::Instance()->update( $followup->id, [ 'sent' => 1 ] );
			} else {
				// no idea what's going on
				$this->_p( "Something happened we didn't expect " . $campaign->phase );
			}

			/**
			 * If there's no more message, mark the campaign as done
			 */
			$this->_p( "Checking if campaign has more message" );
			$hasMessage = CampaignMessageRepository::Instance()->hasMessage( $campaign->id );
			if ( ! $hasMessage ) {
				$this->_p( "Campaign has no more message, marking as done" );
				CampaignRepository::Instance()->update( $campaign->id, [ 'done' => 1 ] );
			}
			$this->_p( "Campaign still has message. Moving to next run." );
			// move to next campaign
			$this->runCron();

			return;
		}

		/**
		 * We still have subscribers for mail to be sent
		 */
		$this->_p( "Updating last sent time for current campaign" );
		CampaignRepository::Instance()->update( $campaign->id, [
			'last_sent' => date( 'Y-m-d H:i:00' )
		] );

		// prepare gmail service
		$this->_p( "Preparing GMail service" );
		$this->prepareGMailService( $campaign );

		// process the subscribers
		$this->_p( "Processing subscribers" );
		$this->processSubscribers( $campaign, $subscribers );
	}

	/**
	 * Prepare GMail service to be used for sending mails
	 *
	 * @param $campaign
	 */
	private function prepareGMailService( $campaign ) {
		$this->_p( "Pulling sender information" );
		$sender = MailAccountRepository::Instance()->find( $campaign->mail_account_id );

		$this->gMail = new GMailService();

		try {
			$this->gMail->init();
		} catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}
		$this->gMail->setAccessToken( $sender->access_token );

		$this->sender = $sender;

		$this->_p( "GMail service has been set up" );
	}

	/**
	 * Process subscribers
	 *
	 * @param $campaign
	 * @param $subscribers
	 */
	public function processSubscribers( $campaign, $subscribers ) {
		$offset = $campaign->subscriber_offset;

		// pull the next message
		$this->_p( "Pulling messages" );
		$message = CampaignMessageRepository::Instance()->getNextMessage( $campaign->id );

		/**
		 * The message may have null subject, which means it's a reply mail for the main message
		 */
		$this->_p( "Updating message subject" );
		if ( $message->subject == null || $message->subject == '' ) {
			$this->_p( "Subject associated with the main message subject" );
			$main             = CampaignMessageRepository::Instance()->getMainMessage( $campaign->id );
			$message->subject = $main->subject;
		}

		// send the message to all the subscribers for this run
		$this->_p( "Preparing to send mails" );
		foreach ( $subscribers as $subscriber ) {
			// make sure this subscriber did not reply
			$this->_p( "Checking for reply from: {$subscriber->email}" );
			$replied = BroadcastRepository::Instance()->replied( $campaign->id, $subscriber->id );
			if ( $replied ) {
				$this->_p( "This subscriber replied. Incrementing the offset and moving to next subscriber." );
				// skip this subscriber
				$this->incrementOffset( $campaign->id, ++ $offset );
				continue;
			}

			$threadId = null;

			// check for reply
			$this->_p( "Checking for any previously sent broadcast" );
			$broadcast_for_this_thread = BroadcastRepository::Instance()->findSubscriberBroadcastsForCampaign( $campaign->id, $subscriber->id );

			if ( $broadcast_for_this_thread ) {
				$this->_p( "Broadcast was sent to this subscriber previously" );
				// we have a thread initiated
				$this->_p( "Extracting the thread ID" );
				$threadId = $broadcast_for_this_thread->thread_id;

				// check for replies in this thread
				$this->_p( "Pulling replies for thread from server" );
				$thread = $this->gMail->pullThread( $threadId );
				$this->_p( "Checking for replies in thread" );
				foreach ( $thread->getMessages() as $msg ) {
					/** @var \Google_Service_Gmail_Message $msg */
					if ( in_array( 'INBOX', $msg->getLabelIds() ) ) {
						// we have a reply
						$this->_p( "The subscriber replied. Incrementing offset and moving to the next subscriber." );
						BroadcastRepository::Instance()->update( $broadcast_for_this_thread->id, [ 'replied' => 1 ] );
						// skip this subscriber
						$this->incrementOffset( $campaign->id, ++ $offset );
						// continue to second level loop
						continue 2;
					}
				}
			}

			$this->_p( "The subscriber didn't reply." );

			// get unique hash for broadcast
			$this->_p( "Getting unique hash for this broadcast" );
			$hash = BroadcastRepository::Instance()->getUniqueHash();
			$this->_p( "Unique hash for this broadcast is: {$hash}" );

			// prepare the replacements for the template parser
			$this->_p( "Preparing the replacements" );
			$replacements = $this->getReplacements( $subscriber, $this->sender );

			$this->_p( "Replacements ready. Parsing subject and message." );
			$subject = MessageParser::parse( $message->subject, $replacements, $hash );
			$msg     = MessageParser::parse( $message->message, $replacements, $hash );

			$this->_p( "Subject and message ready. Attaching pixel tracker." );

			/**
			 * Send the parsed mail
			 */
			$mailMsg = URLRewriteListener::attachPixel( $hash, $msg );

			$mailMsg = "<html><body>{$mailMsg}</body></html>";

			$this->_p( "Pixel tracker attached. Sending mail." );
			$GMail_message = $this->sendMail( $subscriber, $subject, $mailMsg, $threadId );

			/**
			 * Add entry to broadcast table
			 */
			$this->_p( "Mail sent. Adding the broadcast entry in the database." );
			BroadcastRepository::Instance()->store( [
				'campaign_id'   => $campaign->id,
				'subscriber_id' => $subscriber->id,
				'subject'       => $subject,
				'message'       => $msg,
				'message_type'  => 'main',
				'sent_at'       => date( 'Y-m-d H:i:00' ),
				'hash'          => $hash,
				'message_id'    => $GMail_message->getId(),
				'thread_id'     => $GMail_message->getThreadId(),
			] );
			$this->_p( "Incrementing the offset and moving to next subscriber." );
			$this->incrementOffset( $campaign->id, ++ $offset );
		}
		$this->_p( "Subscribers for this run ended." );
	}

	public function incrementOffset( $campaign_id, $offset ) {
		/**
		 * Update offset by one
		 */
		CampaignRepository::Instance()->update( $campaign_id, [
			'subscriber_offset' => $offset,
		] );
	}

	/**
	 * Send mail to subscriber
	 *
	 * @param $subscriber
	 * @param $subject
	 * @param $message
	 *
	 * @param $threadId
	 *
	 * @return \Google_Service_Gmail_Message
	 */
	public function sendMail( $subscriber, $subject, $message, $threadId = null ) {
		$this->_p( "<pre>Sending mail to: {$subscriber->name}\nEmail: {$subscriber->email}\nSubject: {$subject}\nMessage: {$message}</pre>" );

		return $this->gMail->sendMail( $this->sender->name, $this->sender->email, $subject, $message, $subscriber, $threadId );
	}
}
