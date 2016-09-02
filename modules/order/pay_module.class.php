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
    	
		RC_Loader::load_app_func('order','orders');
		
		$order_id = _POST('order_id', 0);
		
		if (!$order_id) {
			EM_Api::outPut(101);
		}
		
		$user_id = $_SESSION['user_id'];
		/* 订单详情 */
		$order = get_order_detail($order_id, $user_id);
		if ($order === false) {
			EM_Api::outPut(8);
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
            EM_Api::outPut($result);
        } else {
            $order['payment'] = $result;
        }
		
        return array('payment' => $order['payment']);
	}
}


// end