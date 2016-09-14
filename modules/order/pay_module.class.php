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
		
		$order_id = $this->requestData('order_id', 0);
		
		if (!$order_id) {
			return new ecjia_error(101, '参数错误');
		}
		
		$user_id = $_SESSION['user_id'];
		/* 订单详情 */
		$order = get_order_detail($order_id, $user_id);
		if ($order === false) {
			return new ecjia_error(8, 'fail');
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