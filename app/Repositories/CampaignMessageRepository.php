<?php

namespace MS\Repositories;

class CampaignMessageRepository {
	/** @var CampaignMessageRepository $instance */
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

		$this->table_name = $this->wpdb->prefix . 'ms_campaign_messages';
	}

	/**
	 * Get campaign repository instance
	 *
	 * @return CampaignMessageRepository
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
		if ( is_numeric( $fields['id'] ) && $fields['id'] > 0 ) {
			$this->update( $fields['id'], $fields );

			return $fields['id'];
		}

		// insert new entries
		$this->wpdb->insert(
			$this->table_name,
			$fields
		);

		return $this->wpdb->insert_id;
	}

	/**
	 * Delete all followups for parent_id where id not in followup_ids
	 *
	 * @param $parent_id
	 * @param array $followup_ids
	 */
	public function deleteFollowupsIfNotIn( $parent_id, array $followup_ids ) {
		$query = "DELETE FROM {$this->table_name} WHERE parent={$parent_id}";
		if ( count( $followup_ids ) > 0 ) {
			$ids   = implode( ',', array_map( 'absint', $followup_ids ) );
			$query .= " AND id NOT IN($ids)";
		}
		$this->wpdb->query( $query );
	}

	private function deleteAll( $campaign_id ) {
		$this->wpdb->delete( $this->table_name, array( 'campaign_id' => $campaign_id ) );
	}

	/**
	 * delete campaign message
	 * through campaign ID
	 */
	public function deleteCampaignMessage( $campaign_id ) {
		return $this->wpdb->delete( $this->table_name, array( 'campaign_id' => $campaign_id ) );
	}

	/**
	 * @param int $id
	 *
	 * @return array|null|object
	 */
	public function find( $id ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE id={$id}" );
	}

	public function findByCampaignId( $campaign_id ) {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE campaign_id={$campaign_id}" );
	}

	public function findSubscriberGroupIdByCampaignId( $campaign_id ) {
		return $this->wpdb->get_results( "SELECT subscriber_group_id FROM {$this->wpdb->prefix}ms_campaigns WHERE id={$campaign_id}" );
	}

	public function getMainMessage( $campaign_id ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE campaign_id={$campaign_id} AND message_type='main'" );
	}

	public function getNextMessage( $campaign_id ) {
		$table_campaigns = $this->wpdb->prefix . 'ms_campaigns';
		$date            = date( 'Y-m-d H:i:00' );

		return $this->wpdb->get_row( "SELECT {$this->table_name}.* FROM {$this->table_name} INNER JOIN {$table_campaigns} ON {$table_campaigns}.id = {$this->table_name}.campaign_id WHERE {$this->table_name}.sent = 0 AND {$table_campaigns}.send_at < '{$date}' - INTERVAL {$this->table_name}.delay DAY AND {$this->table_name}.campaign_id = {$campaign_id}" );
	}

	public function hasMessage( $campaign_id ) {
		return $this->wpdb->get_row( "SELECT id FROM {$this->table_name} WHERE sent=0 AND campaign_id={$campaign_id}" ) !== null;
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

	public function update( $id, $fields ) {
		$this->wpdb->update( $this->table_name, $fields, array( 'id' => $id ) );
	}
}
