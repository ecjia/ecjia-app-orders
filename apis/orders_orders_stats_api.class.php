<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单统计
 * @author will
 *
 */
class orders_orders_stats_api extends Component_Event_Api {
	
	public function call(&$options) {
		$cache_key = 'api_order_stats_'.md5($_SESSION['admin_id']);
        $stats = RC_Cache::app_cache_get($cache_key, 'order');
        if (!$stats) {
			$db_orders = RC_Loader::load_app_model('order_info_model', 'orders');
			$where = array();
			/* 判断是否为多商户*/
			$shop_type = RC_Config::get('site.shop_type');
			if ($shop_type == 'b2b2c') {
				$where['main_order_id'] = 0;
			}
			/* 获取订单总数*/
			$stats['total'] = $db_orders->where($where)->count();
			/* 获取订单金额*/
			$stats['amount'] = $db_orders->where($where)->sum('order_amount');
			
			RC_Cache::app_cache_set($cache_key, $stats, 'order', 120);//2小时缓存
        }
		return $stats;
	}
}


// end