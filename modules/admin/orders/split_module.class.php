<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单分单确认
 * @author will
 *
 */
class split_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
		$this->authadminSession();
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$result = $ecjia->admin_priv('order_os_edit');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		$result = $ecjia->admin_priv('order_ss_edit');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		$order_id = $this->requestData('order_id', 0);
		$action_note = $this->requestData('note');
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}	
		
		$result = RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'split', 'note' => array('action_note' => $action_note)));
		if (is_ecjia_error($result)) {
			return $result;
		}
		return array();
		
	}
	
	
}


// end