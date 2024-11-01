<?php

namespace MS\Repositories;

class CampaignRepository {
	/** @var CampaignRepository $instance */
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

		$this->table_name = $this->wpdb->prefix . 'ms_campaigns';
	}

	/**
	 * Get campaign repository instance
	 *
	 * @return CampaignRepository
	 */
	public static function Instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @param $title
	 * @param $mail_account_id
	 *
	 * @return int
	 */
	public function store( $title, $mail_account_id ) {
		$this->wpdb->insert(
			$this->table_name,
			array(
				'title'           => $title,
				'mail_account_id' => $mail_account_id,
				'state'           => json_encode( [] ),
				'send_at'         => date( 'Y-m-d H:i:00', time() + 24 * 60 * 60 ),
				'last_sent'       => date( 'Y-m-d H:i:00' ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
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

	public function hasSameTitle( $title ) {
		return $this->wpdb->get_row( "SELECT * FROM {$this->table_name} WHERE title='{$title}'" ) != null;
	}

	public function getActivatedCampaign() {
		$table_campaign_messages = $this->wpdb->prefix . 'ms_campaign_messages';
		$date = date('Y-m-d H:i:00');
		return $this->wpdb->get_row( "SELECT DISTINCT {$this->table_name}.* FROM {$this->table_name} INNER JOIN {$table_campaign_messages} ON {$table_campaign_messages}.campaign_id = {$this->table_name}.id WHERE (({$this->table_name}.send_at < '{$date}' AND {$this->table_name}.phase = 'main') OR ({$this->table_name}.phase='followup' AND {$table_campaign_messages}.sent = 0 AND {$this->table_name}.send_at < '{$date}' - INTERVAL {$table_campaign_messages}.delay DAY)) AND {$this->table_name}.enabled = 1 AND {$this->table_name}.done=0 ORDER BY {$this->table_name}.last_sent ASC" );
	}

	public function getMailAccount( $campaign_id ) {
		$table_mail_account = $this->wpdb->prefix . 'ms_mail_accounts';

		return $this->wpdb->get_row( "SELECT {$table_mail_account}.* FROM {$table_mail_account} INNER JOIN {$this->table_name} ON {$this->table_name}.mail_account_id={$table_mail_account}.id WHERE {$this->table_name}.id={$campaign_id}" );
	}

	/**
	 * Get all records
	 *
	 * @param $withCounts
	 *
	 * @return array|null|object
	 */
	public function getAll( $withCounts = false ) {
		if ( $withCounts ) {

			//with counts
			$table_campaigns = $this->table_name;
			$table_broadcast = $this->wpdb->prefix . 'ms_broadcasts';

			$sql = <<<SQL
SELECT
	{$table_campaigns}.*,
  	SUM({$table_broadcast}.opened) AS opens,
  	SUM({$table_broadcast}.clicked) AS clicks,
  	SUM($table_broadcast.replied) AS replies
FROM {$this->wpdb->prefix}ms_campaigns
LEFT OUTER JOIN {$this->wpdb->prefix}ms_broadcasts
    ON {$this->wpdb->prefix}ms_broadcasts.campaign_id = {$this->wpdb->prefix}ms_campaigns.id
GROUP BY {$this->wpdb->prefix}ms_campaigns.id
SQL;

			return $this->wpdb->get_results( $sql );
		}

		// without counts
		return $this->wpdb->get_results( "SELECT * FROM {$this->table_name}" );
	}

	public function update( $id, $fields ) {
		$status = $this->wpdb->update( $this->table_name, $fields, array( 'id' => $id ) );
		return $status;
	}

	public function getTotalCount() {
		$all_row = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

		return $all_row;
	}
	public function deleteCampaign($id){
		$status = $this->wpdb->delete($this->table_name, array('id' => $id));
		return $status ? true : $this->wpdb->last_error;
	}
}
