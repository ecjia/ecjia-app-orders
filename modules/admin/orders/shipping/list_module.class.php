<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 配送方式列表
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
		
		$result_view = $ecjia->admin_priv('order_view');
		$result_edit = $ecjia->admin_priv('order_edit');
		if (is_ecjia_error($result_view)) {
			EM_Api::outPut($result_view);
		} elseif (is_ecjia_error($result_edit)) {
			EM_Api::outPut($result_edit);
		}
		$order_id	= _POST('order_id', 0);
		if ($order_id <= 0) {
			EM_Api::outPut(101);
		}
		
		/*验证订单是否属于此入驻商*/
		if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
			$ru_id_group = RC_Model::model('orders/order_goods_model')->where(array('order_id' => $order_id))->group('ru_id')->get_field('ru_id', true);
			if (count($ru_id_group) > 1 || $ru_id_group[0] != $_SESSION['ru_id']) {
				return new ecjia_error('no_authority', '对不起，您没权限对此订单进行操作！');
			}
		}
		
		$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
		/* 获取订单ru_id*/
		$order_info['ru_id'] = RC_Model::model('orders/order_goods_model')->where(array('order_id' => $order_id))->get_field('ru_id');
		/* 取得可用的配送方式列表 */
		$region_id_list = array(
				$order_info['country'], $order_info['province'], $order_info['city'], $order_info['district']
		);
		
		$shipping_method   = RC_Loader::load_app_class('shipping_method', 'shipping');
		$shipping_list     = $shipping_method->available_shipping_list($region_id_list, $order_info['ru_id']);
		
		$consignee = array(
				'country'		=> $order_info['country'],
				'province'		=> $order_info['province'],
				'city'			=> $order_info['city'],
				'district'		=> $order_info['district'],
		);
		
		RC_Loader::load_app_func('order', 'orders');
		$goods_list = order_goods($order_id);
		
		/* 取得配送费用 */
		$total = order_weight_price($order_id);
		$new_shipping_list = array();
		if (!empty($shipping_list)) {
			foreach ($shipping_list AS $key => $shipping) {
	// 			$parent = get_parent_region($shipping['parent_id']);
	// 			$shipping_list[$key]['parent_name'] = $parent['region_name'];
				
				if (strpos($shipping['shipping_code'], 'ship') === false) {
					$shipping['shipping_code'] = 'ship_'.$shipping['shipping_code'];
				}
			
				if (ecjia::config('freight_model') == 0) {
			
					$shipping_fee = $shipping_method->shipping_fee($shipping['shipping_code'],
							unserialize($shipping['configure']), $total['weight'], $total['amount'], $total['number']);
					$shipping_list[$key]['shipping_fee'] = $shipping_fee;
					$shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
					$shipping_list[$key]['free_money'] = price_format($shipping['configure']['free_money']);
			
					$shipping_list[$key]['freight_model'] = 0;
					
					$new_shipping_list[] = array(
							'shipping_id'	=> $shipping_list[$key]['shipping_id'],
							'shipping_code'	=> $shipping_list[$key]['shipping_code'],
							'shipping_name'	=> $shipping_list[$key]['shipping_name'],
							'shipping_fee'	=> $shipping_list[$key]['shipping_fee'],
							'format_shipping_fee'	=> $shipping_list[$key]['format_shipping_fee'],
							'free_money'	=> $shipping_list[$key]['free_money'],
							
					); 
				} elseif (ecjia::config('freight_model') == 1) {
					
					$shippingFee = get_goods_order_shipping_fee($goods_list, $consignee, $shipping['shipping_code'], $shipping['shipping_id']);
				
					$shipping_list[$key]['free_money']          = price_format($shippingFee['free_money'], false);


// 					if ($shipping_fee['ru_list'][$order_info['ru_id']]){
// 						$shipping_list[$key]['ru_list'] = array_values($shipping_fee['ru_list'][$order_info['ru_id']]);
// 						$shipping_list[$key]['ru_count'] = count($shipping_list[$key]['ru_list']);
// 					}
					
// 					$shipping = available_shipping_fee($shipping_list[$key]['ru_list']);
// 					$shipping_list[$key]['format_shipping_fee'] = $shipping['shipping_fee'];
// 					$shipping_list[$key]['freight_model'] = 1;
					$shipping_list[$key]['shipping_fee']		= $shippingFee['shipping_fee'];
					$shipping_list[$key]['format_shipping_fee'] = price_format($shippingFee['shipping_fee'], false);
						
					$new_shipping_list[] = array(
							'shipping_id'	=> $shipping_list[$key]['shipping_id'],
							'shipping_code'	=> $shipping_list[$key]['shipping_code'],
							'shipping_name'	=> $shipping_list[$key]['shipping_name'],
							'shipping_fee'	=> $shipping_list[$key]['shipping_fee'],
							'format_shipping_fee'	=> $shipping_list[$key]['format_shipping_fee'],
							'free_money'	=> $shipping_list[$key]['free_money'],
								
					);
					
				}
			}
			
		}
		
			
		return $new_shipping_list;
	} 
}


// end