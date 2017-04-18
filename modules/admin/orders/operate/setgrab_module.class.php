<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单设为抢单派发至门店
 * @author will
 *
 */
class setgrab_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		
		$order_id = _POST('order_id', 0);
		$store_id = _POST('store_id');
		
		if ($order_id <= 0 || empty($store_id)) {
			EM_Api::outPut(101);
		}
		
		
		//$ru_id = get_ru_id($order_id);
		
		$store_order_info = get_store_order_info($order_id, $_SESSION['ru_id']);
		
		if (empty($store_order_info)) {
			RC_Model::model('orders/store_order_model')->insert(array(
																'order_id'	=> $order_id,
																'store_id'	=> '0',
																'ru_id'		=> $_SESSION['ru_id'],
																'is_grab_order'		=> 1,
																'grab_store_list'	=> $store_id,
			));
		} else {
			RC_Model::model('orders/store_order_model')->where(array('order_id' => $order_id, 'ru_id' => $_SESSION['ru_id']))->update(array('grab_store_list' => $store_id));
		}
		
		return array();
	} 
}

/* 通过订单商品返回ru_id*/
function get_ru_id($order_id = 0)
{
	$ru_id = RC_Model::model('orders/order_goods_model')->where(array('order_id' => $order_id))->get_field('ru_id');

	// 	if (!$ru_id) {
	// 		$adminru = get_admin_ru_id();
	// 		$ru_id = $adminru['ru_id'];
	// 	}
	return $ru_id;
}

/* 获取记录信息*/
function get_store_order_info($order_id = 0, $ru_id=0)
{
	$store_order_info = RC_Model::model('orders/store_order_model')->where(array('order_id' => $order_id, 'ru_id' => $ru_id))->find();
	return $store_order_info;
}



// end