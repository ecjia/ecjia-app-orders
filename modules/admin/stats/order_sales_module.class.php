<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * //订单统计汇总
 * @author will.chen
 *
 */
class order_sales_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
		$this->authadminSession();
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$result = $ecjia->admin_priv('order_stats');
		if (is_ecjia_error($result)) {
			return $result;
		}
		//传入参数
		$start_date = $this->requestData('start_date');
		$end_date = $this->requestData('end_date');
		if (empty($start_date) || empty($end_date)) {
			return new ecjia_error(101, '参数错误');
		}
		$cache_key = 'admin_stats_order_sales_'.md5($start_date.$end_date);
		$data = RC_Cache::app_cache_get($cache_key, 'api');
		if (empty($data)) {
			$response = orders_module($start_date, $end_date);
			RC_Cache::app_cache_set($cache_key, $response, 'api', 60);
			//流程逻辑结束
		} else {
			$response = $data;
		}
		return $response;
	}
	 
}
function orders_module($start_date, $end_date)
{
	$db_orderinfo_view = RC_Model::model('orders/order_info_viewmodel');
	$result = ecjia_app::validate_application('seller');
	if (!is_ecjia_error($result)) {
		$db_orderinfo_view->view = array(
				'order_info' => array(
						'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=> 'oii',
						'on'	=> 'oi.order_id = oii.main_order_id'
				),
				'order_goods' => array(
						'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=> 'og',
						'on'	=> 'oi.order_id = og.order_id'
				)
		);
	} else {
		$db_orderinfo_view->view = array(
				'order_goods' => array(
						'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=> 'og',
						'on'	=> 'oi.order_id = og.order_id'
				)
		);
	}

	$type = $start_date == $end_date ? 'time' : 'day';
	$start_date = RC_Time::local_strtotime($start_date. ' 00:00:00');
	$end_date	= RC_Time::local_strtotime($end_date. ' 23:59:59');

	
	$where = array();
	if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
		/*入驻商*/
		$where['ru_id'] = $_SESSION['ru_id'];
		$where[] = 'oii.order_id is null';
	} else {
		if (!is_ecjia_error($result)) {
			/*自营*/
			$where['oi.main_order_id'] = 0;
		}
	}
	$where['oi.pay_status'] = 2;
	$member_orders = 0;//会员数量
	$anonymity_orders = 0;//非会员数量

	$field = 'count(oi.order_id) as count, SUM(IF(oi.user_id > 0, 1, 0)) as member_orders, SUM(oi.discount) as discount, SUM(oi.bonus) as bonus, SUM(oi.integral_money) as integral_money, 
			SUM(oi.goods_amount - oi.discount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) AS total_fee';
	
	
	/* 判断是否是入驻商*/
	if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0 ) {
		$join = array('order_info', 'order_goods');
	} else {
		$join = null;
	}

	$result = $db_orderinfo_view->join($join)->field($field)->where($where)->find();


	$data = array(
			'orders'				=> $result['count'],
			'total_sales_volume'	=> $result['total_fee'],
			'member_orders'			=> $result['member_orders'],
			'anonymity_orders'		=> $result['count'] - $result['member_orders'],
			'discount_money'		=> $result['discount'],
			'bonus_money'			=> $result['bonus'],
			'integral_money'		=> $result['integral_money'],
			'formatted_total_sales_volume'	=> price_format($result['total_fee'], false),
			'formatted_discount_money'		=> price_format($result['discount'], false),
			'formatted_bouns_money'			=> price_format($result['bouns'], false),
			'formatted_integral_money'		=> price_format($result['integral_money'], false),
	);
	

// 	orders : '6000', //订单数
// 	total_sales_volume : '10000.00',  //总收益
// 	member_orders :		'5000',		  //会员订单
// 	anonymity_orders :  '1000',		  //匿名订单
// 	discount_money    : '500.00',     //折扣费用
// 	bouns_money : '600.00',	          //红包费用
// 	integral_money : '500.00',        //积分费用
	
// 	formatted_total_sales_volume :　'￥10000.00元',
// 	formatted_discount_money : '￥500.00元',
// 	formatted_bouns_money : '￥600.00元',
// 	formatted_integral_money : '￥500.00元',
	
	return $data;
}
