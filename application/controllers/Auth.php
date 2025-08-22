<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */

	function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }

	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function info()
	{
		$default_recv = $this->default_query();
		echo $default_recv . '<br>';
		$this->default_write_query();
        $default_recv = $this->default_query();
        echo $default_recv . '<br>';
    }
    
	public function default_query()
	{
		$query = $this->db->query("SELECT * from users where id = 12618");
		$user_info = $query->row_array();
		return json_encode($user_info);
		//$now_conn = $this->get_connection_info($this->db);
		//echo json_encode($user_info) . '--'. json_encode($now_conn);
	}

	public function default_write_query()
	{
		$sql = "update users set modified ='" . date("Y-m-d H:i:s") . "' where id = 12618";
		echo $sql . '<br>';
		$query = $this->db->query($sql);
	}


	public function get_connection_info($curren_db_conn) {
        $query = $curren_db_conn->query("SELECT CONNECTION_ID() AS conn_id, USER() AS user, DATABASE() AS db");
        return $query->row_array();
    }

}
