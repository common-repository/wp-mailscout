<?php

namespace MS\Repositories;

class SubscriberGroupRepository {
	/** @var SubscriberGroupRepository $instance */
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

		$this->table_name = $this->wpdb->prefix . 'ms_subscriber_groups';

	}

	/**
	 * Get campaign repository instance
	 *
	 * @return SubscriberGroupRepository
	 */
	public static function Instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @param $name
	 *
	 * @return int
	 */
	public function store( $name ) {

		$this->wpdb->insert(
			$this->table_name,
			array(
				'name' => $name,
			),
			array(
				'%s',
			)
		);

		return $this->wpdb->insert_id;
	}

	/**
	 * @param int $id
	 *
	 * @return array|null|object
	 */
	public function find( $id ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE id={$id}" );
	}

	public function findByName( $name ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE name='{$name}'" );
	}

	/**
	 * Get all records
	 *
	 * @return array|null|object
	 */
	public function getAll() {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name}" );
	}

	public function update( $id, $fields ) {
		$this->wpdb->update( $this->table_name, $fields, array( 'id' => $id ) );
	}

	public function getTotalCount(){
		$all_row = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		return $all_row;
	}

	public function deleteSubGroup($id){
		$status = $this->wpdb->delete($this->table_name, array('id' => $id));
		return $status ? true : $this->wpdb->last_error;
	}
}
