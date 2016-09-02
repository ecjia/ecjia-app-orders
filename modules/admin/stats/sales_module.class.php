<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * //销售金额
 * @author will.chen
 *
 */
class sales_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
		$this->authadminSession();
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$result = $ecjia->admin_priv('sale_order_stats');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		//传入参数
		$start_date = $this->requestData('start_date');
		$end_date = $this->requestData('end_date');
		if (empty($start_date) || empty($end_date)) {
			EM_Api::outPut(101);
		}
		$cache_key = 'admin_stats_sales_'.md5($start_date.$end_date);
		$data = RC_Cache::app_cache_get($cache_key, 'api');
		if (empty($data)) {
			$response = sales_module($start_date, $end_date);
			RC_Cache::app_cache_set($cache_key, $response, 'api', 60);
			//流程逻辑结束
		} else {
			$response = $data;
		}
		return $response;
	}
	 
}
function sales_module($start_date,$end_date){
	
	$db_orderinfo_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
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
	
	/* 计算时间刻度*/
	$group_scale = ($end_date+1-$start_date)/6;
	$stats_scale = ($end_date+1-$start_date)/30;
	
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
	$where[] = "(oi.pay_status = '" . PS_PAYED . "' OR oi.pay_status = '" . PS_PAYING . "')";

// 	/* 判断请求时间，一天按小时返回*/
// 	if ($type == 'day') {
// 		$field = "CONCAT(FROM_UNIXTIME(oi.pay_time, '%Y-%m-%d'), ' 00:00:00') as new_day, SUM(oi.goods_amount - oi.discount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) AS total_fee";
// 	} else {
// 		$field = "CONCAT(FROM_UNIXTIME(oi.pay_time, '%Y-%m-%d %H'), ':00:00') as new_day, SUM(oi.goods_amount - oi.discount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) AS total_fee";
// 	}

	
// 	$field = "FROM_UNIXTIME(oi.pay_time,'%Y-%c-%d %h:%i:%s') as new_time, oi.pay_time, (oi.goods_amount - oi.discount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) AS total_fee";
	
	$field = "oi.pay_time, (oi.goods_amount - oi.discount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) AS total_fee, oi.discount";
	
	/* 判断是否是入驻商*/
	if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0 ) {
		$join = array('order_info', 'order_goods');
	} else {
		$join = null;
	}
	
// 	$where[] = 'oi.add_time >="' .$start_date. '" and oi.add_time<="' .$end_date. '"';

	$stats = $group = array();
	$total_fee = $value = $max_amount = $discount_fee = 0 ;
// 	$temp_stats = $temp_time = $start_date;
	$temp_start_time = $start_date;
	$now_time = RC_Time::gmtime();
	$j = 1;
	while ($j <= 30) {
		if ($temp_start_time > $now_time) {
			break;
		}
		$temp_end_time = $temp_start_time + $stats_scale;
		if ($j == 30) {
			$temp_end_time = $temp_end_time-1;
		}
		
		$temp_total_fee = 0;
		$result = $db_orderinfo_view->field($field)
									->join($join)
									->where(array_merge($where, array('oi.pay_time >="' .$temp_start_time. '" and oi.pay_time<="' .$temp_end_time. '"')))
									->order(array('oi.pay_time' => 'asc'))
									->select();
		
		if (!empty($result)) {
			foreach ($result as $val) {
				$total_fee += $val['total_fee'];
				$max_amount = $max_amount < $val['total_fee'] ? $val['total_fee'] : $max_amount;
				$temp_total_fee += $val['total_fee'];
				$discount_fee += $val['discount'];
			}
			$stats[] = array(
					'time'				=> $temp_end_time,
					'formatted_time'	=> RC_Time::local_date('Y-m-d H:i:s', $temp_end_time),
					'amount'			=> $temp_total_fee,
					'value'				=> $temp_total_fee,
			);
		} else {
			$stats[] = array(
					'time'				=> $temp_end_time,
					'formatted_time'	=> RC_Time::local_date('Y-m-d H:i:s', $temp_end_time),
					'amount'			=> 0,
					'value'				=> 0,
			);
		}
		$temp_start_time += $stats_scale;
		$j++;
	}
	
	
// 	if (!empty($result)) {
// 		$count = count($result);
// 		foreach ($result as $key => $val) {
// 			$total_fee += $val['total_fee'];
// 			$max_amount = $max_amount < $val['total_fee'] ? $val['total_fee'] : $max_amount;
// 			while (true) {
// 				if ($val['pay_time'] >= ($temp_stats + $stats_scale) || $count == $key+1) {
// 					if ($count == $key+1 && $temp_stats > $end_date) {
// 						break;
// 					}		
// 					$stats[] = array(
// 							'time'				=> $temp_stats,
// 							'formatted_time'	=> RC_Time::local_date('Y-m-d H:i:s', $temp_stats),
// 							'amount'			=> $value,
// 							'value'				=> $value,
// 					);
// 					if ($val['pay_time'] < $temp_stats + $stats_scale || $count == $key+1) {
// 						if ($val['pay_time'] < $temp_stats + $stats_scale && $count != $key+1) {
// 							break;
// 						}
// 					}
// 					/* 增加时间刻度*/
// 					$temp_stats += $stats_scale;
// 					$value = 0;
// 				} else {
// 					$value += $val['total_fee'];
// 					break;
// 				}
// 				$temp_value++;
// 			}
// 		}
// 	} else {
// 		$i = 1;
// 		while ($i <= 30) {
// 			$stats[] = array(
// 							'time'				=> $temp_stats,
// 							'formatted_time'	=> RC_Time::local_date('Y-m-d H:i:s', $temp_stats),
// 							'amount'			=> $value,
// 							'value'				=> $value,
// 					);
// 			$temp_stats += $stats_scale;
// 			$i++;
// 		}
// 	}
	
	$i = 1;
	$temp_group = $start_date;
	while ($i <= 7) {
		if ($i == 7) {
			$group[] = array(
					'time'				=> $end_date,
					'formatted_time'	=> RC_Time::local_date('Y-m-d H:i:s', $end_date),
			);
			break;
		}
		$group[] = array(
				'time'				=> $temp_group,
				'formatted_time'	=> RC_Time::local_date('Y-m-d H:i:s', $temp_group),
		);
		$temp_group += $group_scale;
		$i++;
	}
	
	/* 计算出有多少天*/
	$day = round(($end_date - $start_date)/(24*60*60));
	/* 平均值保留小数位2位*/
	$average_sales_volume = round($total_fee/$day, 2);
	$data = array(
			'stats'					=> $stats,
			'group'					=> $group,
			'maximum_sales_volume'	=> $max_amount,
			'total_sales_volume'	=> $total_fee,
			'average_sales_volume'	=> $average_sales_volume,
			'discount_sales_volume'	=> $discount_fee,
	);
	return $data;
}
