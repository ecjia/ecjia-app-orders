<?php
defined('IN_ECJIA') or exit('No permission resources.');

class bonus_type_user_viewmodel extends Component_Model_View {
	public $table_name = '';
	public $view = array();
	public function __construct() {
		$this->db_config 		= RC_Config::load_config('database');
		$this->db_setting 		= 'default';
		$this->table_name 		= 'bonus_type';
		$this->table_alias_name	= 'bt';
		
		 $this->view = array(
    		'user_bonus' => array(
    			'type'  => Component_Model_View::TYPE_LEFT_JOIN,
    			'alias' => 'ub',
    			'on'    => 'bt.type_id = ub.bonus_type_id ',
    		)
    );	
		parent::__construct();
	}
}

// end