<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单通知
 * @author will
 *
 */
class orders_remind_order_api extends Component_Event_Api {
	
	public function call(&$options) {
		
// 		$db_orders = RC_Loader::load_app_model('order_info_model', 'orders');
		if (empty($_SESSION['last_check'])) {
			RC_Session::set('last_check', RC_Time::gmtime());
			return array('new_orders' => 0, 'new_paid' => 0);
		}
		/* 新订单 */
// 		$arr['new_orders'] = $db_orders->where(array('add_time' => array('egt' => $_SESSION['last_check'])))->count();
		/* 新付款的订单 */
// 		$arr['new_paid'] = $db_orders->where(array('pay_time' => array('egt' => $_SESSION['last_check'])))->count();
		
		$arr['new_orders'] = RC_DB::table('order_info')->where('add_time', '<=', $_SESSION['last_check'])->count();
		$arr['new_paid'] = RC_DB::table('order_info')->where('pay_time', '<=', $_SESSION['last_check'])->count();
		
		RC_Session::set('last_check', RC_Time::gmtime());
		if (!(is_numeric($arr['new_orders']) && is_numeric($arr['new_paid']))) {
			return array('new_orders' => 0, 'new_paid' => 0);
		} else {
			return array('new_orders' => $arr['new_orders'], 'new_paid' => $arr['new_paid']);
		}
	}
}


// end