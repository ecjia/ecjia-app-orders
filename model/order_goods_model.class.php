<?php
defined('IN_ECJIA') or exit('No permission resources.');

class order_goods_model extends Component_Model_Model {
	public $table_name = '';
	public function __construct() {
		$this->db_config 	= RC_Config::load_config('database');
		$this->db_setting 	= 'default';
		$this->table_name 	= 'order_goods';
		parent::__construct();
	}
	
	public function get_exchange_goods_num ($where, $field) {
		$res = $this->where($where)->field($field)->select();
		return $res;
	}
}

// end