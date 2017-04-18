<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 关闭订单
 * @author will
 *
 */
class cancel_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		$result_view = $ecjia->admin_priv('order_view');
		$result_edit = $ecjia->admin_priv('order_os_edit');
		if (is_ecjia_error($result_view)) {
			EM_Api::outPut($result_view);
		} elseif (is_ecjia_error($result_edit)) {
			EM_Api::outPut($result_edit);
		}
		$order_id		= _POST('order_id', 0);
		$cancel_note	= _POST('cancel_note');
		
		if (empty($order_id) || empty($cancel_note)) {
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
		if (empty($order_info)) {
			EM_Api::outPut(101);
		}
		
		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('function', 'orders');
		
		/* 取消 */
		/* 标记订单为“取消”，记录取消原因 */
		$arr = array(
				'order_status'	=> OS_CANCELED,
				'to_buyer'		=> $cancel_note,
				'pay_status'	=> PS_UNPAYED,
				'pay_time'		=> 0,
				'money_paid'	=> 0,
				'order_amount'	=> $order_info['money_paid']
		);
		update_order($order_id, $arr);
		
		/* todo 处理退款 */
		if ($order_info['money_paid'] > 0) {
			$refund_type = 1;
// 			$refund_type = $_POST['refund'];
// 			$refund_note = $_POST['refund_note'];
			order_refund($order_info, $refund_type, $cancel_note);
		}
		
		/* 记录log */
		order_action($order_info['order_sn'], OS_CANCELED, $order_info['shipping_status'], PS_UNPAYED, $cancel_note);
		
		/* 如果使用库存，且下订单时减库存，则增加库存 */
		if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE) {
			change_order_goods_storage($order_id, false, SDT_PLACE);
		}
		
		/* 退还用户余额、积分、红包 */
		return_user_surplus_integral_bonus($order_info);
		
		/* 发送邮件 */
		$cfg = ecjia::config('send_cancel_email');
		if ($cfg == '1') {
			$tpl_name = 'order_cancel';
			$tpl = RC_Api::api('mail', 'mail_template', $tpl_name);
			if (!empty($tpl)) {
				ecjia_api::$controller->assign('order', $order_info);
				ecjia_api::$controller->assign('shop_name', ecjia::config('shop_name'));
				ecjia_api::$controller->assign('send_date', RC_Time::local_date(ecjia::config('date_format')));
				$content = ecjia_api::$controller->fetch_string($tpl['template_content']);
				
				RC_Mail::send_mail($order_info['consignee'], $order_info['email'], $tpl['template_subject'], $content, $tpl['is_html']);
			}			
		}
		
		return array();
	} 
}


// end