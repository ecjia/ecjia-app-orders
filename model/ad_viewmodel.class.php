<?php
/**
 * 站外投放JS数据模型
 */
defined('IN_ECJIA') or exit('No permission resources.');

class ad_viewmodel extends Component_Model_View {
	public $table_name = '';
	public $view = array();
	public function __construct() {
		$this->db_config = RC_Config::load_config('database');
		$this->db_setting = 'default';
		$this->table_name = 'ad';
		$this->table_alias_name = 'a';
		
		$this->view = array(
			'adsense' => array(
				'type' => Component_Model_View::TYPE_LEFT_JOIN,
				'alias'=> 'b',
				'field'=> "",
				'on'   => 'b.from_ad = a.ad_id'
			),	
		);
		parent::__construct();
	}
}

// end