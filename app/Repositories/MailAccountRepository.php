<?php

namespace MS\Repositories;

class MailAccountRepository
{
	/** @var MailAccountRepository $instance */
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
    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->table_name = $this->wpdb->prefix . 'ms_mail_accounts';
    }

	/**
	 * Get campaign repository instance
	 *
	 * @return MailAccountRepository
	 */
	public static function Instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
     * @param string $name
     * @param string $email
     * @param array $accessToken
     *
     * @return int
     */
    public function store($name, $email, $avatar, $accessToken)
    {
        $this->wpdb->insert(
            $this->table_name,
            array(
                'name'         => $name,
                'email'        => $email,
                'avatar'       => get_avatar_url($email),
                'access_token' => json_encode($accessToken),
            ),
            array(
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
    public function find($id)
    {
        return $this->wpdb->get_row("SELECT * FROM {$this->table_name} WHERE id={$id}");
    }

    /**
     * Get all records
     *
     * @return array|null|object
     */
    public function getAll()
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name}");
    }
    public function getTotalCount(){
        $all_row = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		return $all_row;
    }
    /**
     * delete mail accounts
     * by mail account ID
     */
    public function deleteMailAccount($id){
		$status = $this->wpdb->delete($this->table_name, array('id' => $id));
        return $status ? true : $this->wpdb->last_error;
	}
}
