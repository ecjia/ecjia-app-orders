<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单可设为抢单的门店列表
 * @author will
 *
 */
class list_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		
		$order_id = _POST('order_id', 0);
		if ($order_id <= 0) {
			EM_Api::outPut(101);
		}
		
		$list = array();
		$store_list = get_store_list($order_id);
		$store_order_info = get_store_order_info($order_id, $_SESSION['ru_id']);
		if (!empty($store_list)) {
			$grab_store_arr = explode(',', $store_order_info['grab_store_list']);
			foreach ($store_list as $key => $val) {
				$list[] = array(
						'store_id'		=> $val['id'],
						'store_name'	=> $val['stores_name'],
						'mobile'		=> $val['stores_tel'],
						'store_address'	=> $val['store_address'],
						'is_checked'	=> in_array($val['id'], $grab_store_arr) ? 1 : 0
				);
			}
		}
			
		return $list;
	} 
}

/* 获取商家门店列表*/
function get_store_list($order_id = 0)
{
	$ru_id = get_ru_id($order_id);
	
	$store_list = RC_Model::model('orders/offline_store_model')->where(array('ru_id' => $ru_id))->select();
	if (!empty($store_list)) {
		$db_region = RC_Loader::load_app_model('region_model', 'shipping');
		foreach ($store_list as $key => $val) {
			//收货人地址
			$region_name = $db_region->where(array('region_id' => array('in'=>$val['country'], $val['province'], $val['city'], $val['district'])))->order('region_type')->get_field('region_name', true);
			$region_address = implode(' ', $region_name);
			$store_list[$key]['store_address'] = $region_address . ' ' . $val['stores_address'];
		}
	}
	
	return $store_list;
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
function get_store_order_info($order_id = 0)
{
	$store_order_info = RC_Model::model('orders/store_order_model')->where(array('order_id' => $order_id))->find();
	return $store_order_info;
}


// end