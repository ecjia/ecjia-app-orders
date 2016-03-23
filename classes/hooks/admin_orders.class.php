<?php
defined('IN_ECJIA') or exit('No permission resources.');

class orders_admin_plugin {
	
	public static function widget_admin_dashboard_orderslist() {
	
		if (!ecjia_admin::$controller->admin_priv('order_view', ecjia::MSGTYPE_HTML, false)) {
			return false;
		}
		
	    $result = ecjia_app::validate_application('payment');
	    if (is_ecjia_error($result)) {
	        return false;
	    }
	    
	    $title = __('最新订单');

	    $order_query = RC_Loader::load_app_class('order_query','orders');
		$order_list = $order_query->get_order_list(5);
		RC_Lang::load('orders/order');
		
	    ecjia_admin::$controller->assign('title'		, $title);
	    ecjia_admin::$controller->assign('order_count'	, $order_list['filter']['record_count']);
	    ecjia_admin::$controller->assign('order_list'	, $order_list['orders']);
	    
	    ecjia_admin::$controller->assign_lang();
		ecjia_admin::$controller->display(ecjia_app::get_app_template('library/widget_admin_dashboard_orderslist.lbi', 'orders'));
	}
	
	
	public static function widget_admin_dashboard_ordersstat() {
		
		if (!ecjia_admin::$controller->admin_priv('order_view', ecjia::MSGTYPE_HTML, false)) {
			return false;
		}
	
	    $result = ecjia_app::validate_application('payment');
	    if (is_ecjia_error($result)) {
	        return false;
	    }
	    
	    $title = __('订单统计信息');
		$order_query = RC_Loader::load_app_class('order_query','orders');
		
		$db	= RC_Loader::load_app_model('order_info_model','orders');
		$db_good_booking = RC_Loader::load_app_model('goods_booking_model','goods');
		$db_user_account = RC_Loader::load_app_model('user_account_model','user');
		/* 已完成的订单 */
		$order['finished']		= $db->where($order_query->order_finished())->count();
		$status['finished']		= CS_FINISHED;
		/* 待发货的订单： */
		$order['await_ship']	= $db->where($order_query->order_await_ship())->count();
	    $status['await_ship']	= CS_AWAIT_SHIP;
		/* 待付款的订单： */
		$order['await_pay']		= $db->where($order_query->order_await_pay())->count();
		$status['await_pay']	= CS_AWAIT_PAY;
		/* “未确认”的订单 */
		$order['unconfirmed']	= $db->where($order_query->order_unconfirmed())->count();
		$status['unconfirmed']	= OS_UNCONFIRMED;
		/* “部分发货”的订单 */
		$order['shipped_part']	= $db->where(array('shipping_status'=>SS_SHIPPED_PART))->count();
		$status['shipped_part'] = OS_SHIPPED_PART;
		
	    /* 缺货登记 */
		$booking_goods_count = $db_good_booking->where(array('is_dispose' => '0'))->count();
		/* 退款申请 */
		$new_repay_count = $db_user_account->where(array('process_type' => SURPLUS_RETURN ,'is_paid' =>'0'))->count();
		
		ecjia_admin::$controller->assign('title'			, $title);
		ecjia_admin::$controller->assign('order'			, $order);
		ecjia_admin::$controller->assign('status'			, $status);
	    ecjia_admin::$controller->assign('booking_goods'	, $booking_goods_count);
	    ecjia_admin::$controller->assign('new_repay'		, $new_repay_count);
	    
		ecjia_admin::$controller->assign_lang();
		ecjia_admin::$controller->display(ecjia_app::get_app_template('library/widget_admin_dashboard_ordersstat.lbi', 'orders'));
	}
	
	
	static public function orders_stats_admin_menu_api($menus) {
	    $menu = array(
	        ecjia_admin::make_admin_menu('divider', '', '', 50)->add_purview(array('order_stats', 'guest_stats', 'sale_general_stats', 'users_order_stats', 'sale_list_stats', 'sale_order_stats', 'visit_sold_stats', 'adsense_conversion_stats')),
	        ecjia_admin::make_admin_menu('guest_stats', __('客户统计'), RC_Uri::url('orders/admin_guest_stats/init'), 51)->add_purview('guest_stats'),
	        ecjia_admin::make_admin_menu('order_stats', __('订单统计'), RC_Uri::url('orders/admin_order_stats/init'), 52)->add_purview('order_stats'),
	        ecjia_admin::make_admin_menu('sale_general', __('销售概况'), RC_Uri::url('orders/admin_sale_general/init'), 53)->add_purview('sale_general_stats'),
	        ecjia_admin::make_admin_menu('users_order', __('会员排行'), RC_Uri::url('orders/admin_users_order/init'), 54)->add_purview('users_order_stats'),
	        ecjia_admin::make_admin_menu('sale_list', __('销售明细'), RC_Uri::url('orders/admin_sale_list/init'), 55)->add_purview('sale_list_stats'),
	        ecjia_admin::make_admin_menu('sale_order', __('销售排行'), RC_Uri::url('orders/admin_sale_order/init'), 56)->add_purview('sale_order_stats'),
	        ecjia_admin::make_admin_menu('visit_sold', __('访问购买率'), RC_Uri::url('orders/admin_visit_sold/init'), 57)->add_purview('visit_sold_stats'),
	        ecjia_admin::make_admin_menu('adsense', __('广告转化率'), RC_Uri::url('orders/admin_adsense/init'), 58)->add_purview('adsense_conversion_stats')
	    );
	    $menus->add_submenu($menu);
	    return $menus;
	}
	
}

RC_Hook::add_action( 'admin_dashboard_right', array('orders_admin_plugin', 'widget_admin_dashboard_orderslist') );

RC_Hook::add_action( 'admin_dashboard_left', array('orders_admin_plugin', 'widget_admin_dashboard_ordersstat') );

RC_Hook::add_filter( 'stats_admin_menu_api', array('orders_admin_plugin', 'orders_stats_admin_menu_api') );

// end