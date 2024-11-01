<?php

namespace MS\Repositories;

class SubscriberRepository {
	/** @var SubscriberRepository $instance */
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

		$this->table_name = $this->wpdb->prefix . 'ms_subscribers';
	}


	/**
	 * Get campaign repository instance
	 *
	 * @return SubscriberRepository
	 */
	public static function Instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @param $group_id
	 * @param $subscribers
	 *
	 * @return string
	 */
	public function store( $group_id, $subscribers ) {
		// don't go any further if there's no subscriber to save
		if ( count( $subscribers ) === 0 ) {
			return false;
		}

		$values        = array();
		$place_holders = array();
		$query         = "INSERT INTO {$this->table_name} (name, email, subscriber_group_id) VALUES ";
		foreach ( $subscribers as $subscriber ) {
			/**
			 * check the each mail
			 * if validate then add it to the placeholder
			 * otherwise dump that mail.
			 */
			if(filter_var($subscriber['email'], FILTER_VALIDATE_EMAIL)){
				array_push( $values, $subscriber['name'], $subscriber['email'], $group_id );
				$place_holders[] = "('%s', '%s', '%d')";
			}else{
				var_dump('invalid mail id is', $subscriber['email']);
			}
		}
		/**
		 * if subscriber number and validated mail number
		 * are same then prepare the query for the database
		 * otherwise return error.
		 */
		if(count($subscribers) === count($place_holders)){
			$query .= implode( ', ', $place_holders );
			$this->wpdb->query( $this->wpdb->prepare( "$query ", $values ) );
			return $this->wpdb->last_error;
		}else{
			return $this->wpdb->last_error;
		}


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
	 * @param $id
	 *
	 * @return array|null|object
	 */
	public function findByGroupId( $id ) {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE subscriber_group_id={$id}" );
	}

	public function getNextSubscribers( $group, $offset = 0, $limit = 10 ) {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE `subscriber_group_id`={$group} LIMIT {$offset}, {$limit}" );
	}

	/**
	 * find mail by group id
	 */
	public function findEmailByGroupId( $id ) {
		$data = $this->wpdb->get_col( "SELECT email FROM {$this->table_name} WHERE subscriber_group_id={$id}" );

		return $data;
	}

	/**
	 * Get all records
	 *
	 * @return array|null|object
	 */
	public function getAll() {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name}" );
	}

	public function getTotalCount() {
		$all_row = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		return $all_row;
	}

	public function findByCampaignId( $campaign_id ) {
		$table_campaign = $this->wpdb->prefix . 'ms_campaigns';

		return $this->wpdb->get_results( "SELECT {$this->table_name}.id, {$this->table_name}.name, {$this->table_name}.email FROM {$this->table_name} INNER JOIN {$table_campaign} ON {$table_campaign}.subscriber_group_id={$this->table_name}.subscriber_group_id WHERE {$table_campaign}.id={$campaign_id}" );
	}

	public function deleteSubByGrpId($id){
		$status = $this->wpdb->delete($this->table_name, array('subscriber_group_id' => $id));
		return $status ? true : $this->wpdb->last_error;
	}
	public function deleteSingleSubscriber($id){
		$status = $this->wpdb->delete($this->table_name, array('id' => $id));
		return $status ? true : $this->wpdb->last_error;
	}
	public function pagedSubscriber($limit, $offset){
		$data = $this->wpdb->get_results( "SELECT * FROM {$this->table_name} LIMIT {$limit} OFFSET {$offset}" );
		return $data;
	}

}
