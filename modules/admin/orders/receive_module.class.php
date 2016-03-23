<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 确认订单
 * @author will
 *
 */
class receive_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		$result = $ecjia->admin_priv('order_os_edit');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		
		$order_id = _POST('order_id', 0);
		$action_note = _POST('note');
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}	
		
		$result = RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'receive', 'note' => array('action_note' => $action_note)));
		if (is_ecjia_error($result)) {
			return $result;
		}
		return array();
		
	}
	
	
}


// end