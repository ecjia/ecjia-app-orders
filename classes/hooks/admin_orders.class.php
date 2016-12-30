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

		$order_list = RC_Cache::app_cache_get('admin_dashboard_order_list', 'orders');
	    if (!$order_list) {
	        $order_query = RC_Loader::load_app_class('order_query','orders');
			$order_list = $order_query->get_order_list(5);
	        RC_Cache::app_cache_set('admin_dashboard_order_list', $order_list, 'orders', 120);
	    }
		
	    ecjia_admin::$controller->assign('title', $title);
	    ecjia_admin::$controller->assign('order_count', $order_list['filter']['record_count']);
	    ecjia_admin::$controller->assign('order_list', $order_list['orders']);
	    
	    ecjia_admin::$controller->assign('lang_os', RC_Lang::get('orders::order.os'));
	    ecjia_admin::$controller->assign('lang_ps', RC_Lang::get('orders::order.ps'));
	    ecjia_admin::$controller->assign('lang_ss', RC_Lang::get('orders::order.ss'));
	    
	    ecjia_admin::$controller->assign_lang();
		ecjia_admin::$controller->display(ecjia_app::get_app_template('library/widget_admin_dashboard_orderslist.lbi', 'orders'));
	}
	
	public static function widget_admin_dashboard_shopchart() {
		$order_query = RC_Loader::load_app_class('order_query', 'orders');
		$db	= RC_Loader::load_app_model('order_info_viewmodel', 'orders');
		$db->view = array(
			'order_goods' => array(
				'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
				'alias' => 'g',
				'on' 	=> 'oi.order_id = g.order_id '
			)
		);
		
		$month_order = RC_DB::table('order_info as oi')
			->leftJoin('order_goods as g', RC_DB::raw('oi.order_id'), '=', RC_DB::raw('g.order_id'))
			->where(RC_DB::raw('oi.add_time'), '>=', RC_Time::gmtime() - 2592000)
			->count(RC_DB::raw('distinct oi.order_id'));
			
		$new = RC_Time::gmtime();
		$order_money = RC_DB::table('order_info as oi')
			->leftJoin('pay_log as pl', RC_DB::raw('oi.order_id'), '=', RC_DB::raw('pl.order_id'))
			->selectRaw('pl.order_amount')
			->where(RC_DB::raw('oi.add_time'), '>=', $new-3600*24*30)
			->where(RC_DB::raw('oi.add_time'), '<=', $new)
			->where(RC_DB::raw('pl.is_paid'), 1)
			->groupBy(RC_DB::raw('oi.order_id'))
			->get();
			
		$num = 0;
		if (!empty($order_money)) {
			foreach($order_money as $val){
				$num += intval($val['order_amount']);
			}
		}

		$order_unconfirmed = $db->field('oi.order_id')->where(array('oi.order_status' => 0, 'oi.add_time' => array('gt'=> $new-3600*60*24, 'lt' => $new)))->group('oi.order_id')->select();
		$order_unconfirmed = count($order_unconfirmed);
	
		$order_await_ship = $db->field('oi.order_id')->where(array_merge($order_query->order_await_ship('oi.'), array('oi.add_time' => array('gt' => $new-3600*60*24, 'lt' => $new))))->group('oi.order_id')->select();
		$order_await_ship = count($order_await_ship);
	
		ecjia_admin::$controller->assign('month_order', 		$month_order);
		ecjia_admin::$controller->assign('order_money', 		intval($num));
		ecjia_admin::$controller->assign('order_unconfirmed', 	$order_unconfirmed);
		ecjia_admin::$controller->assign('order_await_ship', 	$order_await_ship);
	
		ecjia_admin::$controller->display(ecjia_app::get_app_template('library/widget_admin_dashboard_shopchart.lbi', 'orders'));
	}
	
	public static function widget_admin_dashboard_ordersstat() {
		if (!ecjia_admin::$controller->admin_priv('order_view', ecjia::MSGTYPE_HTML, false)) {
			return false;
		}
	    $result = ecjia_app::validate_application('payment');
	    if (is_ecjia_error($result)) {
	        return false;
	    }
	    
	    $title = RC_Lang::get('orders::order.order_stats_info');

	    $order = RC_Cache::app_cache_get('admin_dashboard_order_stats', 'orders');
	    if (!$order) {
	        $order_query = RC_Loader::load_app_class('order_query','orders');
			$db	= RC_Model::model('orders/order_info_model');
			
			/* 已完成的订单 */
			$order['finished']		= $db->where($order_query->order_finished())->count();
			/* 待发货的订单： */
			$order['await_ship']	= $db->where($order_query->order_await_ship())->count();
		    /* 待付款的订单： */
			$order['await_pay']		= $db->where($order_query->order_await_pay())->count();
			/* “未确认”的订单 */
			$order['unconfirmed']	= $db->where($order_query->order_unconfirmed())->count();
			/* “部分发货”的订单 */
			$order['shipped_part']	= $db->where(array('shipping_status'=>SS_SHIPPED_PART))->count();
			/* 缺货登记 */
// 			$order['booking_goods_count'] = $db_good_booking->where(array('is_dispose' => '0'))->count();
			/* 退款申请 */
			$order['new_repay_count'] = RC_DB::table('user_account')->where('process_type', SURPLUS_RETURN)->where('is_paid', 0)->count();
	    	
	        RC_Cache::app_cache_set('admin_dashboard_order_stats', $order, 'orders', 120);
	    }
		
		$status['await_ship']	= CS_AWAIT_SHIP;
		$status['await_pay']	= CS_AWAIT_PAY;
		$status['shipped_part'] = OS_SHIPPED_PART;
		$status['unconfirmed']	= OS_UNCONFIRMED;
		$status['finished']		= CS_FINISHED;
		
		ecjia_admin::$controller->assign('title', $title);
		ecjia_admin::$controller->assign('order', $order);
		ecjia_admin::$controller->assign('status', $status);
	    ecjia_admin::$controller->assign('booking_goods', $order['booking_goods_count']);
	    ecjia_admin::$controller->assign('new_repay', $order['new_repay_count']);
	    
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
	    );
	    $menus->add_submenu($menu);
	    return $menus;
	}
	
	public static function admin_remind_order() {
		if (isset($_SESSION['action_list']) && ecjia_admin::$controller->admin_priv('order_view', ecjia::MSGTYPE_HTML, false)) {
			$cache_key = 'admin_remind_order_'.md5($_SESSION['admin_id']);
	        $remind_order = RC_Cache::app_cache_get($cache_key, 'order');
	        if (empty($remind_order) || $remind_order['time'] + 5*60 < RC_Time::gmtime()) {
	        	$remind_order = RC_Api::api('orders', 'remind_order');
	        	RC_Cache::app_cache_set($cache_key, array('time' => RC_Time::gmtime(), 'new_orders' => $remind_order['new_orders'], 'new_paid' => $remind_order['new_paid']), 'order', 5);
	        	if ($remind_order['new_orders'] > 0 || $remind_order['new_paid'] > 0 ) {
	        		$url = RC_Uri::url('orders/admin/init');
	        		$html = '新订单通知：您有 <strong style="color:#ff0000">'.$remind_order['new_orders'].
						'</strong> 个新订单以及  <strong style="color:#ff0000">'.$remind_order['new_paid'].'</strong> 个新付款的订单。<a href="'.$url.'"><span style="color:#ff0000">点击查看</span></a>';
		        	RC_Cache::app_cache_set($cache_key, array('time' => RC_Time::gmtime()), 'order', 5);
					ecjia_notification::make()->register('remind_order', 
						admin_notification::make($html)
					  	->setAutoclose(10000)
					  	->setType(admin_notification::TYPE_INFO)
					);
	        	}        	
	        }
		}
	}
}
RC_Hook::add_action( 'admin_dashboard_top', array('orders_admin_plugin', 'widget_admin_dashboard_shopchart'));
RC_Hook::add_action( 'admin_dashboard_left', array('orders_admin_plugin', 'widget_admin_dashboard_ordersstat') );
RC_Hook::add_action( 'admin_dashboard_left', array('orders_admin_plugin', 'widget_admin_dashboard_orderslist') );
RC_Hook::add_filter( 'stats_admin_menu_api', array('orders_admin_plugin', 'orders_stats_admin_menu_api') );

// end