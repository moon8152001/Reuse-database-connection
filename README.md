# Reuse-database-connection
On the basis of CI3 framework, when multiple MySQL databases need to be supported, each API call will only use one database connection instead of repeatedly establishing multiple

1. Create databses db_defalut & db_second
2. Create table users in two databases respectively
```SQL
	   CREATE TABLE `users` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(50) NULL DEFAULT NULL COMMENT 'user\' name' COLLATE 'armscii8_bin',
			`created` DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (`id`) USING BTREE
   
		)
		COLLATE='armscii8_bin'
		ENGINE=InnoDB
		AUTO_INCREMENT=2;
```
3. Write test data
```SQL
   INSERT INTO db_default.`users` (`id`, `name`, `created`) VALUES (1, 'red', '2025-08-14 13:56:02');
   INSERT INTO db_second.`users` (`id`, `name`, `created`) VALUES (1, 'green', '2025-08-14 13:56:17');
```
4. Create Controller File  (/application/controllers/Auth.php)
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Auth extends CI_Controller {

	function __construct()
    {
         parent::__construct();
    }

	public function index()
	{
         $this->load->view('welcome_message');
	}

	public function info()
	{
        $default_recv = $this->default_query();
        echo $default_recv . '<br>';

	    $other_recv = $this->other_query();
        echo $other_recv . '<br>';

        $default_recv = $this->default_query();
        echo $default_recv . '<br>';

        $specify_default_recv = $this->specify_default_query();
        echo $specify_default_recv . '<br>';

        $other_recv = $this->other_query();
        echo $other_recv . '<br>';
	}

	public function default_query()
	{
		$query = $this->db->query("SELECT * from users where 1");
		$user_info = $query->row_array();
		$now_conn = $this->get_connection_info($this->db);
		echo json_encode($user_info) . '--'. json_encode($now_conn);
	}

	public function specify_default_query()
	{
		$this->specify_db = $this->load->database('default', true);
		$query = $this->specify_db->query("SELECT * from users where 1");
		$user_info = $query->row_array();
		$now_conn = $this->get_connection_info($this->specify_db);
		echo json_encode($user_info) . '--'. json_encode($now_conn);
	}

	public function other_query()
	{
		$this->vc_db = $this->load->database('second', true);
		$query = $this->vc_db->query("SELECT * from users where 1");
		$response = $query->row_array();
		$other_conn = $this->get_connection_info($this->vc_db);
        return json_encode($response) . '--' . json_encode($other_conn);
	}

	public function get_connection_info($curren_db_conn) 
    {
        $query = $curren_db_conn->query("SELECT CONNECTION_ID() AS conn_id, USER() AS user, DATABASE() AS db");
        return $query->row_array();
    }
}
```
5. Operation results
```php
{"id":"1","name":"red","created":"2025-08-14 13:56:02"}--{"conn_id":"206","user":"root@localhost","db":"db_default"}
{"id":"1","name":"green","created":"2025-08-14 13:56:17"}--{"conn_id":"207","user":"root@localhost","db":"db_second"}
{"id":"1","name":"red","created":"2025-08-14 13:56:02"}--{"conn_id":"206","user":"root@localhost","db":"db_default"}
{"id":"1","name":"red","created":"2025-08-14 13:56:02"}--{"conn_id":"206","user":"root@localhost","db":"db_default"}
{"id":"1","name":"green","created":"2025-08-14 13:56:17"}--{"conn_id":"207","user":"root@localhost","db":"db_second"}
```
