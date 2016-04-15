<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单列表
 * @author royalwang
 *
 */
class list_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		
		EM_Api::authSession();
		$user_id = $_SESSION['user_id'];

		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('main', 'api');
		$page_parm = EM_Api::$pagination;
		$page = $page_parm['page'];
		$type = _POST('type', 'all');
		/**
		 * all			所有订单
		 * await_pay	待付款
		 * await_ship	待发货
		 * shipped		待收货
		 * finished		历史订单
		 */
		if (!in_array($type, array('all', 'await_pay', 'await_ship', 'shipped', 'finished', 'unconfirmed'))) {
			EM_Api::outPut(101);
		}
		
		$db_order_info = RC_Loader::load_app_model('order_info_model', 'orders');
		$db_orderinfo_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
		
		$db_orderinfo_view->view = array(
				'order_info' => array(
						'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=> 'oii',
						'on'	=> 'oi.order_id = oii.main_order_id'
				)
		);
		
		$where = array('oi.user_id' => $user_id, 'oii.order_id is null');
		if ($type != 'all') {
			$order_where[] = EM_order_query_sql($type, 'oi.');
			$where = array_merge($where, $order_where);
		}
		
		$record_count = $db_orderinfo_view->join(array('order_info'))->where($where)->count('*');
		
// 		$record_count = $db_order_info->where(array('user_id' => $user_id, EM_order_query_sql($type)))->count();

		//加载分页类
		RC_Loader::load_sys_class('ecjia_page', false);
		//实例化分页
		$page_row = new ecjia_page($record_count, $page_parm['count'], 6, '', $page_parm['page']);
		
		$orders = EM_get_user_orders($user_id, $page_parm['count'], $page_parm['page'], $type);
		
		foreach ($orders as $key => $value) {
			$orders[$key]['order_time'] = formatTime($value['order_time']);
			$goods_list = EM_order_goods($value['order_id'],1,10);//只获取一个商品
			
			$goods_list_t = array();

			foreach ($goods_list as $v) {
				$attr = array();
				if (!empty($v['goods_attr'])) {
					$goods_attr = explode("\n", $v['goods_attr']);
					$goods_attr = array_filter($goods_attr);
					foreach ($goods_attr as  $val) {
						$a = explode(':',$val);
						if (!empty($a[0]) && !empty($a[1])) {
							$attr[] = array('name'=>$a[0], 'value'=>$a[1]);
						}
					}
				}
				
				$goods_list_t[] = array(
					"goods_id" => $v['goods_id'],
					"name" => $v['goods_name'],
					"goods_attr"   => empty($attr) ? '' : $attr,
					"goods_number" => $v['goods_number'],
					"subtotal" => price_format($v['subtotal'], false),
					"formated_shop_price" => price_format($v['goods_price'], false),
					"img" => array(
						'small'=>API_DATA('PHOTO', $v['goods_thumb']),
						'thumb'=>API_DATA('PHOTO', $v['goods_img']),
						'url' => API_DATA('PHOTO', $v['original_img'])
					)
				);
			}
		
			$orders[$key]['goods_list'] = $goods_list_t;
			$order_detail = get_order_detail($value['order_id'], $user_id);
		
			$orders[$key]['formated_total_fee'] 		= price_format($value['total_fee'], false); // 订单总价
			$orders[$key]['formated_integral_money']   	= $order_detail['formated_integral_money'];//积分 钱
			$orders[$key]['formated_bonus']   			= $order_detail['formated_bonus'];//红包 钱
			$orders[$key]['formated_shipping_fee']   	= $order_detail['formated_shipping_fee'];//运送费
			$orders[$key]['formated_discount'] 			= price_format($value['discount'], false); //折扣
		
			if ($order_detail['pay_id'] > 0) {
				$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
				$payment = $payment_method->payment_info_by_id($order_detail['pay_id']);
			}
		
			$subject = $orders[$key]['goods_list'][0]['name'].'等'.count($orders[$key]['goods_list']).'种商品';
		
			$orders[$key]['order_info'] = array(
					'pay_code' => $payment['pay_code'],
					'order_amount' => $order_detail['order_amount'],
					'order_id' => $order_detail['order_id'],
					'subject' => $subject,
					'desc' => $subject,
					'order_sn' => $order_detail['order_sn']
			);
		}

		$pager = array(
				"total" => $page_row->total_records,
				"count" => $page_row->total_records,
				"more" => $page_row->total_pages <= $page ? 0 : 1,
		);
		EM_Api::outPut($orders, $pager);
	}

}


/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function EM_get_user_orders($user_id, $num = 10, $start = 0, $type = 'await_pay')
{
//     $db_order_info = RC_Loader::load_app_model('order_info_mobile_model');
//     $db_ordergoods = RC_Loader::load_app_model('order_goods_model','orders');
    $db_ordergoodsview = RC_Loader::load_app_model('order_order_infogoods_viewmodel','orders');
    /* 取得订单列表 */
    $arr    = array();
	
    $db_orderinfo_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
    
    $db_orderinfo_view->view = array(
    		'order_info' => array(
    				'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
    				'alias'	=> 'oii',
    				'on'	=> 'oi.order_id = oii.main_order_id'
    		),
    		'order_goods' => array(
    				'type'  =>	Component_Model_View::TYPE_LEFT_JOIN,
    				'alias'	=>	'og',
    				'on'    =>	'oi.order_id = og.order_id ',
    		)
    );
    
    $field = 'oi.*, (oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee + oi.tax - oi.integral_money - oi.bonus - oi.discount) AS total_fee, oi.discount , SUM(goods_number) as goods_number';
//     $res = $db_order_info->field($field)->where("user_id = '$user_id' " . GZ_order_query_sql($type))->order(array('add_time' => 'desc'))->limit($start,$num)->select();
    $start = ($start-1)*$num ;
    $where = array('oi.user_id' => $user_id, 'oii.order_id is null');
    if ($type != 'all') {
    	$order_where[] = EM_order_query_sql($type, 'oi.');
    	$where = array_merge($where, $order_where);
    }
    
    $res = $db_orderinfo_view->join(array('order_info', 'order_goods'))->field($field)->where($where)->group('order_id')->order(array('oi.add_time' => 'desc'))->limit($start,$num)->select();
    RC_Lang::load('orders/order');
    if (!empty($res)) {
        foreach ($res as $row) {
            //$row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
            //$row['order_status'] = RC_Lang::lang("os/$row[order_status]") . ',' . RC_Lang::lang("ps/$row[pay_status]") . ',' . RC_Lang::lang("ss/$row[shipping_status]");
            $row['label_order_status'] 		= RC_Lang::lang("os/$row[order_status]");
            $row['label_shipping_status']	= RC_Lang::lang("ss/$row[shipping_status]");
            $row['label_pay_status']		= RC_Lang::lang("ps/$row[pay_status]");
            
            $arr[] = array(
                'order_id'       		=> $row['order_id'],
                'order_sn'       		=> $row['order_sn'],
                'order_time'     		=> RC_Time::local_date(ecjia::config('time_format'), $row['add_time']),
                'order_status'   		=> $row['order_status'],
            	'shipping_status'		=> ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'],
            	'pay_status'     		=> $row['pay_status'],
            	'label_order_status'	=> $row['label_order_status'],
            	'label_shipping_status' => $row['label_shipping_status'],
            	'label_pay_status'		=> $row['label_pay_status'],
                'total_fee'      		=> $row['total_fee'],
                'discount'		 		=> $row['discount'],
            	'goods_number'	 		=> $row['goods_number']
            );
        }
    }
    return $arr;
}




// end