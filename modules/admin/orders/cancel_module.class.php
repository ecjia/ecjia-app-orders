<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 取消订单
 * @author will
 *
 */
class cancel_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		$result = $ecjia->admin_priv('order_edit');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		$order_id = _POST('id');
		
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}
		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('function', 'orders');
		/* 查询订单信息 */
		$order = order_info($order_id);
		
		/* 取消 */
		/* 标记订单为“取消”，记录取消原因 */
		$cancel_note = isset($_POST['cancel_note']) ? trim($_POST['cancel_note']) : '';
		$action_note = isset($_POST['action_note']) ? trim($_POST['action_note']) : '';
		$arr = array(
				'order_status'	=> OS_CANCELED,
				'to_buyer'		=> $cancel_note,
				'pay_status'	=> PS_UNPAYED,
				'pay_time'		=> 0,
				'money_paid'	=> 0,
// 				'order_amount'	=> $order['money_paid']
		);
		update_order($order_id, $arr);
		
		/* todo 处理退款 */
// 		if ($order['money_paid'] > 0) {
// 			$refund_type = $_POST['refund'];
// 			$refund_note = $_POST['refund_note'];
// 			order_refund($order, $refund_type, $refund_note);
// 		}
		
		/* 记录log */
		order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $action_note);
		
		/* 如果使用库存，且下订单时减库存，则增加库存 */
		if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE) {
			change_order_goods_storage($order_id, false, SDT_PLACE);
		}
		
		/* 退还用户余额、积分、红包 */
		return_user_surplus_integral_bonus($order);
		
		/* 发送邮件 */
		$cfg = ecjia::config('send_cancel_email');
		if ($cfg == '1') {
			$tpl_name = 'order_cancel';
			$tpl = RC_Api::api('mail', 'mail_template', $tpl_name);
		
			ecjia::$view_object->assign('order'		, $order);
			ecjia::$view_object->assign('shop_name'	, ecjia::config('shop_name'));
			ecjia::$view_object->assign('send_date'	, RC_Time::local_date(ecjia::config('date_format')));
			$content = $api->fetch_string($tpl['template_content']);
		
			if (!RC_Mail::send_mail($order['consignee'], $order['email'] , $tpl['template_subject'], $content, $tpl['is_html'])) {
				
			}
		}
		
		
		return array();
	}
	
	
}


// end