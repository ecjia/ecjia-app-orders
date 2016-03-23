<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 退款
 * @author will
 *
 */
class refundConfirm_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		$result = $ecjia->admin_priv('order_stats');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}

		$order_id = _POST('order_id');
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}
		
		/* 查询订单信息 */
		$order = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
		if (empty($order)) {
			EM_Api::outPut(13);
		}
		
		$payment_method	= RC_Loader::load_app_class('payment_method', 'payment');
		$pay_info = $payment_method->payment_info_by_id($order['pay_id']);
		$payment = $payment_method->get_payment_instance($pay_info['pay_code']);
		
		/* 判断是否有支付方式以及是否为现金支付和酷银*/
		if (!$payment) {
			EM_Api::outPut(8);
		}
		$payment->set_orderinfo($order);
		
		if ($pay_info['pay_code'] == 'pay_cash') {
			$pay_priv = $ecjia->admin_priv('order_ps_edit');
			if (is_ecjia_error($pay_priv)) {
				EM_Api::outPut($pay_priv);
			}
			$result = $payment->refund();
			if (is_ecjia_error($result)) {
				return $result;
			} else {
				$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
				$data = array(
						'order_id' 		=> $order_info['order_id'],
						'money_paid'	=> $order_info['money_paid'],
						'formatted_money_paid'	=> $order_info['formated_money_paid'],
						'order_amount'	=> $order_info['order_amount'],
						'formatted_order_amount' => $order_info['formated_order_amount'],
						'pay_code'		=> $pay_info['pay_code'],
						'pay_name'		=> $pay_info['pay_name'],
						'pay_status'	=> 'success',
						'desc'			=> '订单支付撤销成功！'
				);
				return array('refund' => $data);
			}
		}
		
		if ($pay_info['pay_code'] == 'pay_koolyun') {
			$result = $payment->refund();
			if (is_ecjia_error($result)) {
				return $result;
			} else {
				$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
				$data = array(
						'order_id' 		=> $order_info['order_id'],
						'money_paid'	=> $order_info['money_paid'],
						'formatted_money_paid'	=> $order_info['formated_money_paid'],
						'order_amount'	=> $order_info['order_amount'],
						'formatted_order_amount' => $order_info['formated_order_amount'],
						'pay_code'		=> $pay_info['pay_code'],
						'pay_name'		=> $pay_info['pay_name'],
						'pay_status'	=> 'success',
						'desc'			=> '订单支付撤销成功！'
				);
				return array('refund' => $data);
			}
		}
		
		if ($pay_info['pay_code'] == 'pay_balance') {
			RC_Loader::load_app_func('order', 'orders');
			/* 标记订单为未付款，更新付款时间和已付款金额 */
			$arr = array(
					'pay_status'	=> PS_UNPAYED,
					'pay_time'		=> 0,
					'money_paid'	=> 0,
					'order_amount'	=> $order['money_paid']
			);
			update_order($order_id, $arr);
			
			/* todo 处理退款 */
// 			$refund_type = @$_POST['refund'];
// 			$refund_note = @$_POST['refund_note'];
// 			order_refund($order, $refund_type, $refund_note);
			order_refund($order, 1, '收银台付款撤销');
			/* 记录日志 */
			ecjia_admin::admin_log($order_id, 'edit', 'not_payment');
			/* 记录log */
			order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, '');
			
			if (is_ecjia_error($result)) {
				return $result;
			} else {
				$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
				$data = array(
						'order_id' 		=> $order_info['order_id'],
						'money_paid'	=> $order_info['money_paid'],
						'formatted_money_paid'	=> $order_info['formated_money_paid'],
						'order_amount'	=> $order_info['order_amount'],
						'formatted_order_amount' => $order_info['formated_order_amount'],
						'pay_code'		=> $pay_info['pay_code'],
						'pay_name'		=> $pay_info['pay_name'],
						'pay_status'	=> 'success',
						'desc'			=> '订单支付撤销成功！'
				);
				return array('refund' => $data);
			}
		}
		
		return array ();
		
		
	}
	
	
}


// end