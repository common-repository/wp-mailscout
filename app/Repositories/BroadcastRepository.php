<?php

namespace MS\Repositories;

class BroadcastRepository {
	/** @var BroadcastRepository $instance */
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

		$this->table_name = $this->wpdb->prefix . 'ms_broadcasts';
	}

	/**
	 * Get campaign repository instance
	 *
	 * @return BroadcastRepository
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
		// insert new entries
		$this->wpdb->insert(
			$this->table_name,
			$fields
		);

		return $this->wpdb->insert_id;
	}

	/**
	 * @return bool|string
	 */
	public function getUniqueHash() {
		$hash = static::quickRandom( 20 );

		while ( $this->findByHash( $hash ) ) {
			$hash = static::quickRandom( 20 );
		}

		return $hash;
	}

	/**
	 * Get broadcast by hash
	 *
	 * @param $hash
	 *
	 * @return array|null|object
	 */
	public function findByHash( $hash ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE hash='{$hash}'" );
	}

	public function replied( $campaign_id, $subscriber_id ) {
		return $this->wpdb->get_row( "SELECT id FROM {$this->table_name} WHERE campaign_id={$campaign_id} AND subscriber_id={$subscriber_id} AND replied=1" ) !== null;
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


	public function getAllCampaign() {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name}" );
	}

	public function getTodayCampaign() {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE `send_at` = CURRENT_DATE AND `time` > CURRENT_TIME" );
	}

	public function update( $id, $fields ) {
		$this->wpdb->update( $this->table_name, $fields, array( 'id' => $id ) );
	}

	public function updateByHash( $hash, $fields ) {
		$this->wpdb->update( $this->table_name, $fields, array( 'hash' => $hash ) );
	}

	public function findSubscriberBroadcastsForCampaign( $campaign_id, $subscriber_id ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE campaign_id={$campaign_id} AND subscriber_id={$subscriber_id}" );
	}

	public static function quickRandom( $length = 16 ) {
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return substr( str_shuffle( str_repeat( $pool, 5 ) ), 0, $length );
	}
}
