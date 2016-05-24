<?php
/**
 * 营销顾问日志数据模型
 */
defined('IN_ECJIA') or exit('No permission resources.');

class adviser_log_viewmodel extends Component_Model_View {
	public $table_name = '';
	public $view = array();
	public function __construct() {
		$this->db_config = RC_Config::load_config('database');
		$this->db_setting = 'default';
		$this->table_name = 'adviser_log';
		$this->table_alias_name = 'al';
		
		$this->view = array(
			'order_info' => array(
					'type' => Component_Model_View::TYPE_LEFT_JOIN,
					'alias'=> 'oi',
					'on'   => 'oi.order_id = al.order_id'
			),
			'order_goods' => array(
					'type' => Component_Model_View::TYPE_LEFT_JOIN,
					'alias'=> 'og',
					'on'   => 'oi.order_id = og.order_id'
			),
			'adviser' => array(
					'type' => Component_Model_View::TYPE_LEFT_JOIN,
					'alias'=> 'ad',
					'on'   => 'ad.id = al.adviser_id'
			),
		);
		parent::__construct();
	}
}

// end