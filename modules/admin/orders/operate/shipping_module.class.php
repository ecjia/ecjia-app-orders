<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 修改订单配送方式
 * @author will
 *
 */
class shipping_module implements ecjia_interface {
	
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

		$order_id		= _POST('order_id', 0);
		$shipping_id	= _POST('shipping_id');
		$action_note	= _POST('action_note');
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}
		
		/*验证订单是否属于此入驻商*/
		if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
			$ru_id_group = RC_Model::model('orders/order_goods_model')->group('ru_id')->where(array('order_id' => $order_id))->get_field('ru_id', true);
			if (count($ru_id_group) > 1 || $ru_id_group[0] != $_SESSION['ru_id']) {
				return new ecjia_error('no_authority', '对不起，您没权限对此订单进行操作！');
			}
		}
		
		$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
		if (empty($order_info)) {
			EM_Api::outPut(101);
		}
		
		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('function', 'orders');
		/* 保存配送信息 */
		/* 取得订单信息 */
		$region_id_list = array($order_info['country'], $order_info['province'], $order_info['city'], $order_info['district']);
		/* 保存订单 */
		$shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
		$shipping		= $shipping_method->shipping_area_info($shipping_id, $region_id_list);
		if (strpos($shipping['shipping_code'], 'ship') === false) {
			$shipping['shipping_code'] = 'ship_'.$shipping['shipping_code'];
		}
		$weight_amount	= order_weight_price($order_id);
		$shipping_fee	= $shipping_method->shipping_fee($shipping['shipping_code'], $shipping['configure'], $weight_amount['weight'], $weight_amount['amount'], $weight_amount['number']);
		$order = array(
			'shipping_id'	=> $shipping_id,
			'shipping_name'	=> addslashes($shipping['shipping_name']),
// 			'shipping_fee'	=> $shipping_fee
		);
		
// 		if (isset($_POST['insure'])) {
// 			/* 计算保价费 */
// 			$order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], order_amount($order_id), $shipping['insure']);
// 		} else {
// 			$order['insure_fee'] = 0;
// 		}
		update_order($order_id, $order);
// 		update_order_amount($order_id);
		
		/* 更新 pay_log */
		update_pay_log($order_id);
		
		/* todo 记录日志 */
		$sn = '编辑配送方式，';
// 		$new_order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
// 		if ($order_info['total_fee'] != $new_order_info['total_fee']) {
// 			$sn .= sprintf('订单总金额由 %s 变为 %s', $order_info['total_fee'], $new_order_info['total_fee']).'，';
// 		}
		$sn .= '订单号是 '.$order_info['order_sn'];
		ecjia_admin::admin_log($sn, 'edit', 'order');
		
		return array();
	} 
}




// end