<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 修改订单金额
 * @author will
 *
 */
class money_module implements ecjia_interface {
	
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
		$goods_amount	= _POST('goods_amount');
		$shipping_fee	= _POST('shipping_fee');
		if ($order_id <= 0) {
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
		
		$order= array();
		if (!empty($goods_amount)) {
			$order['goods_amount'] = $goods_amount;
		}
		if (!empty($shipping_fee)) {
			$order['shipping_fee'] = $shipping_fee;
		}

		/* 计算待付款金额 */
		$order['order_amount']  = $goods_amount - $order_info['discount']
								+ $order_info['tax']
								+ $shipping_fee
								+ $order_info['insure_fee']
								+ $order_info['pay_fee']
								+ $order_info['pack_fee']
								+ $order_info['card_fee']
								- $order_info['money_paid']
								- $order_info['integral_money']
								- $order_info['bonus'];
		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('function', 'orders');
		
		/* 暂不支持产生退款费用*/
		if ($order['order_amount'] < 0) {
			return new ecjia_error('amount_error', '订单金额过小，将产生退款费用！');
		}
		
		update_order($order_id, $order);
		/* 更新 pay_log */
		update_pay_log($order_id);
		
		$sn = '编辑费用信息，';
		$new_order = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
		if ($order_info['total_fee'] != $new_order['total_fee']) {
			$sn .= sprintf(RC_Lang::lang('order_amount_change'), $order_info['total_fee'], $new_order['total_fee']).'，';
		}
		$sn .= '订单号是 '.$order_info['order_sn'];
		ecjia_admin::admin_log($sn, 'edit', 'order');
			
		return array();
	} 
}


// end