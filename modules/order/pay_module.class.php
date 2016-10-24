<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单支付
 * @author royalwang
 *
 */
class pay_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {	
    	$this->authSession();
    	$user_id = $_SESSION['user_id'];
    	if ($user_id < 1 ) {
    	    return new ecjia_error(100, 'Invalid session');
    	}
    	
		RC_Loader::load_app_func('order','orders');
		
		$order_id = $this->requestData('order_id', 0);
		
		if (!$order_id) {
			return new ecjia_error('invalid_parameter', RC_Lang::get('orders::order.invalid_parameter'));
		}
		
		/* 订单详情 */
		$order = get_order_detail($order_id, $user_id, 'front');
		if (is_ecjia_error($order)) {
			return $order;
		}
		//判断是否是管理员登录
		if ($_SESSION['admin_id'] > 0) {
			$_SESSION['user_id'] = $order['user_id'];
		}
		
		//支付方式信息
		$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
		$payment_info = $payment_method->payment_info_by_id($order['pay_id']);
		// 取得支付信息，生成支付代码
		$payment_config = $payment_method->unserialize_config($payment_info['pay_config']);

		$handler = $payment_method->get_payment_instance($payment_info['pay_code'], $payment_config);
		$handler->set_orderinfo($order);
		$handler->set_mobile(true);
		
		$result = $handler->get_code(payment_abstract::PAYCODE_PARAM);
        if (is_ecjia_error($result)) {
            return $result;
        } else {
            $order['payment'] = $result;
        }
		
        return array('payment' => $order['payment']);
	}
}


// end