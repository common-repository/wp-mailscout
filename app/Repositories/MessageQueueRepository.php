<?php

namespace MS\Repositories;

use MS\Services\MessageParser;

class MessageQueueRepository {
	/** @var MessageQueueRepository $instance */
	private static $instance;
	/**
	 * @var string $table_name
	 */
	private $table_name;

	/* @var \wpdb $wpdb */
	private $wpdb;

	/**
	 * MailAccountRepository constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->table_name = $this->wpdb->prefix . 'ms_message_queue';
	}

	/**
	 * Get campaign repository instance
	 *
	 * @return MessageQueueRepository
	 */
	public static function Instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @param $fields
	 *
	 * @return int
	 */
	public function store( $fields ) {
		// remove all entries from before
		$this->deleteUpdatables( $fields['campaign_id'] );
		$subscribers = SubscriberRepository::Instance()->findByCampaignId( $fields['campaign_id'] );
		$replacables = [
			'signature' => 'Themesgrove',
		];
		$subject     = $fields['subject'];
		$message     = $fields['message'];
		foreach ( $subscribers as $subscriber ) {
			$replacables['name']  = $subscriber->name;
			$replacables['email'] = $subscriber->email;

			$fields['subject'] = MessageParser::parse( $subject, $replacables );
			$fields['message'] = MessageParser::parse( $message, $replacables );
			// insert new entries
			$this->wpdb->insert(
				$this->table_name,
				array_merge( $fields, array( 'subscriber_id' => $subscriber->id ) )
			);
		}
		if ( $this->wpdb->last_error === '' ) {
			return true;
		}

		return false;
	}

	private function deleteUpdatables( $campaign_id ) {
		$this->wpdb->delete( $this->table_name, array( 'campaign_id' => $campaign_id, 'sent' => 0 ) );
	}

	/**
	 * @param int $id
	 *
	 * @return array|null|object
	 */
	public function find( $id ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE id={$id}" );
	}

	/**
	 * Get all mails for a subscriber under a campaign
	 *
	 * @param $campaign_id
	 * @param $subscriber_id
	 *
	 * @return array|null|object
	 */
	public function findSubscriberMessage( $campaign_id, $subscriber_id ) {
		return $this->wpdb->get_results( " SELECT * FROM {$this->table_name} WHERE `campaign_id` = {$campaign_id} AND `subscriber_id` = {$subscriber_id}" );
	}

	/**
	 * Get all records
	 *
	 * @param $campaign_id
	 *
	 * @return array|null|object
	 */
	public function getAll( $campaign_id ) {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE `campaign_id`={$campaign_id}" );
	}

	public function update( $id, $fields ) {
		$this->wpdb->update( $this->table_name, $fields, array( 'id' => $id ) );
	}

	public function getShouldSend($limit = 10) {
		// return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE sent=0 AND `send_at` >= CURRENT_DATE AND `time` > CURRENT_TIME LIMIT {$limit}" );
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE sent=0 LIMIT {$limit}" );
	}
	public function getShouldSendNow($limit = 10, $campaign_id='') {
		$message_to_send = $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE sent=0 AND `campaign_id` = {$campaign_id}  LIMIT {$limit}" );
		return $message_to_send;
	}
}
