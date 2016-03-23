<?php
defined('IN_ECJIA') or exit('No permission resources.');

class back_goods_model extends Component_Model_View {
	public $table_name = '';
	public function __construct() {
		$this->db_config 	= RC_Config::load_config('database');
		$this->db_setting 	= 'default';
		$this->table_name 	= 'back_goods';
		parent::__construct();
	}

}

// end