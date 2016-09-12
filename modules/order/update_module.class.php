<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单支付
 * @author royalwang
 *
 */
class update_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {	
    	
    	$this->authSession();
 		$user_id	= $_SESSION['user_id'];
 		$order_id	= $this->requestdata('order_id', 0);
		$pay_id		= $this->requestdata('pay_id',0);
		if (!$order_id || !$pay_id) {
			return new ecjia_error(101, '参数错误');
		}
		$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
		$payment_info = $payment_method->payment_info($pay_id);
		
		if (empty($payment_info) || $payment_info['is_online'] == 0) {
			return new ecjia_error(8, 'fail');
		} else {
			RC_Loader::load_app_func('order','orders');
			$order_info = get_order_detail($order_id,$user_id);
			/*重新处理订单的配送费用*/
			$payfee_change = $payment_info['pay_fee'] - $order_info['pay_fee'];
			$order_amount = $order_info['order_amount'] + $payfee_change > 0 ? $order_info['order_amount'] + $payfee_change : 0;
			$data = array(
				'pay_id'	=> $payment_info['pay_id'],
				'pay_name'	=> $payment_info['pay_name'],
				'pay_fee'	=> $payment_info['pay_fee'],
				'order_amount' => $order_amount,
			);
			$where = array(
				'order_id'			=> $order_id,
				'user_id'			=> $user_id,
				'pay_status'		=> 0,
				'shipping_status'	=> 0,
			);
			$db_order = RC_Model::model('orders/order_info_model');
			$result = $db_order->where($where)->update($data);
			if ($result) {
				return array();
			} else {
				return new ecjia_error(8, 'fail');
			}
		}
	}
}

// end