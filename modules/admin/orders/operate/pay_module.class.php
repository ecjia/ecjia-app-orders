<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 设定订单支付
 * @author will
 *
 */
class pay_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		$result_view = $ecjia->admin_priv('order_view');
		$result_edit = $ecjia->admin_priv('order_ps_edit');
		if (is_ecjia_error($result_view)) {
			EM_Api::outPut($result_view);
		} elseif (is_ecjia_error($result_edit)) {
			EM_Api::outPut($result_edit);
		}
		$order_id		= _POST('order_id', 0);
		$action_note	= _POST('action_note');
		
		if (empty($order_id) || empty($action_note)) {
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
		/* 标记订单为已确认、已付款，更新付款时间和已支付金额，如果是货到付款，同时修改订单为“收货确认” */
		if ($order_info['order_status'] != OS_CONFIRMED) {
			$arr['order_status']	= OS_CONFIRMED;
			$arr['confirm_time']	= RC_Time::gmtime();
		}
		$arr['pay_status']		= PS_PAYED;
		$arr['pay_time']		= RC_Time::gmtime();
		$arr['money_paid']		= $order_info['money_paid'] + $order_info['order_amount'];
		$arr['order_amount']	= 0;
		$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
		$payment = $payment_method->payment_info($order_info['pay_id']);
		if ($payment['is_cod']) {
			$arr['shipping_status']		= SS_RECEIVED;
			$order_info['shipping_status']	= SS_RECEIVED;
		}
		update_order($order_id, $arr);
		/* 记录日志 */
		ecjia_admin::admin_log('已付款，订单号是 '.$order_info['order_sn'], 'edit', 'order_status');
		/* 记录log */
		order_action($order_info['order_sn'], OS_CONFIRMED, $order_info['shipping_status'], PS_PAYED, $action_note);
		
		return array();
	} 
}


// end