<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单列表
 * @author will
 *
 */
class list_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		$result = $ecjia->admin_priv('order_view');
		
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		$type = _POST('type', 'await_pay');
		$keywords = _POST('keywords');
		$size = EM_Api::$pagination['count'];
		$page = EM_Api::$pagination['page'];
		
		$where = array();
		$order_query = RC_Loader::load_app_class('order_query', 'orders');
		
// 		await_pay/待付款，await_ship/待发货，shipped/已发货，finished/已完成，refund/退款(退货)，closed(invalid)/已关闭
		switch ($type) {
			case 'await_pay':
				$where = $order_query->order_await_pay('oi.');
				break;
			case 'await_ship':
				$where = $order_query->order_await_ship('oi.');
				break;
			case 'shipped':
				$where = $order_query->order_shipped('oi.');
				break;
			case 'finished':
				$where = $order_query->order_finished('oi.');
				break;
			case 'refund':
				$where = $order_query->order_refund('oi.');
				break;
			case 'closed' :
				$where = array_merge($order_query->order_invalid('oi.'),$order_query->order_canceled('oi.'));
				break;
		}
	
		if ( !empty($keywords)) {
			$where[] = "( oi.order_sn like '%".$keywords."%' or oi.consignee like '%".$keywords."%' )";
		}
		$db = RC_Loader::load_app_model('order_info_model', 'orders');
		$db_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
		
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
		
		
		if ($_SESSION['ru_id'] > 0) {
			$where['ru_id'] = $_SESSION['ru_id'];
			$where[] = 'oii.order_id is null';
		}
		/* 获取记录条数 */
		$record_count = $db_orderinfo_view->where($where)->count('oi.order_id');
		
		//加载分页类
		RC_Loader::load_sys_class('ecjia_page', false);
		//实例化分页
		$page_row = new ecjia_page($record_count, $size, 6, '', $page);
		
		$total_fee = "(oi.goods_amount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) as total_fee";
		
		$field = 'oi.order_id, oi.order_sn, oi.consignee, oi.mobile, oi.tel, oi.order_status, oi.pay_status, oi.shipping_status, oi.pay_name, '.$total_fee.', oi.integral_money, oi.bonus, oi.shipping_fee, oi.discount, oi.add_time, sum(og.goods_number) as goods_num, og.goods_id';
		if ($_SESSION['ru_id'] > 0) {
			$data = $db_orderinfo_view->field($field)->where($where)->group(array('oi.order_id'))->order(array('oi.order_id' => 'desc'))->limit($page_row->limit())->select();
		} else {
			$data = $db_orderinfo_view->join(array('order_goods'))->field($field)->where($where)->group(array('oi.order_id'))->order(array('oi.order_id' => 'desc'))->limit($page_row->limit())->select();
		}
// 		_dump($db_orderinfo_view->last_sql(),1);
		RC_Lang::load('orders/order');
// 		_dump($data,1);
		$order_list = array();
		if (!empty($data)) {
			$goods_db = RC_Loader::load_app_model('goods_model', 'goods');
			foreach ($data as $val) {
				$goods_lists = array();
				$goods_list = $goods_db->find(array('goods_id' => $val['goods_id']));
				$order_status = ($val['order_status'] != '2' || $val['order_status'] != '3') ? RC_Lang::lang('os/'.$val['order_status']) : '';
				$order_status = $val['order_status'] == '2' ? __('已取消') : $order_status;
				$order_status = $val['order_status'] == '3' ? __('无效') : $order_status;
				$goods_lists[] = array(
						'goods_id'	=> $goods_list['goods_id'],
						'name'		=> $goods_list['goods_name'],
						'img'		=> array(
								'thumb'	=> API_DATA('PHOTO', $goods_list['goods_img']),
								'url'	=> API_DATA('PHOTO', $goods_list['original_img']),
								'small'	=> API_DATA('PHOTO', $goods_list['goods_thumb'])
						
						)
				);
				$order_list[] = array(
					'order_id'	=> $val['order_id'],
					'order_sn'	=> $val['order_sn'],
					'total_fee' => $val['total_fee'],
					'pay_name'	=> $val['pay_name'],
					'consignee' => $val['consignee'],
					'mobile'	=> empty($val['mobile']) ? $val['tel'] : $val['mobile'],
					'formated_total_fee' 		=> price_format($val['total_fee'], false),
					'formated_integral_money'	=> price_format($val['integral_money'], false),
					'formated_bonus'			=> price_format($val['bonus'], false),
					'formated_shipping_fee'		=> price_format($val['shipping_fee'], false),
					'formated_discount'			=> price_format($val['discount'], false),
					'status'					=> $order_status.','.RC_Lang::lang('ps/'.$val['pay_status']).','.RC_Lang::lang('ss/'.$val['shipping_status']),
					'goods_number'				=> $val['goods_num'],
					'create_time' 				=> RC_Time::local_date(ecjia::config('date_format'), $val['add_time']),
					'goods_items' 				=> $goods_lists
				);
			}
		} 

		$pager = array(
				"total" => $page_row->total_records,
				"count" => $page_row->total_records,
				"more" => $page_row->total_pages <= $page ? 0 : 1,
		);
		
		
		
		EM_Api::outPut($order_list, $pager);
		
		
// 		{
// 			"order_id": "4125",
// 			"order_sn": "2015041496313",
// 			"order_time": "2015/04/14 18:32:12 +0800",
// 			"total_fee": "1680.00",
// 			"discount": "0.00",
// 			"goods_number": "2",
// 			"goods_list": [
// 			{
// 				"goods_id": "108",
// 				"name": "模板堂精仿也买酒整站模板",
// 				"goods_number": "2",
// 				"subtotal": "￥1680.00元",
// 				"formated_shop_price": "￥840.00元",
// 				"img": {
// 				"small": "http://192.168.1.55/ecmoban-admin/content/uploads/images/201203/thumb_img/108_thumb_G_1331074419824.gif",
// 				"thumb": "http://192.168.1.55/ecmoban-admin/content/uploads/images/201203/goods_img/108_G_1331074419284.jpg",
// 				"url": "http://192.168.1.55/ecmoban-admin/content/uploads/images/201203/source_img/108_G_1331074419130.jpg"
// 				}
// 			}
// 			],
// 			"formated_total_fee": "￥1680.00元",
// 			"formated_integral_money": "￥0.00元",
// 			"formated_bonus": "￥0.00元",
// 			"formated_shipping_fee": "￥0.00元",
// 			"formated_discount": "￥0.00元",
// 			"order_info": {
	// 			"pay_code": "pay_alipay",
	// 			"order_amount": "1680.00",
	// 			"order_id": "4125",
	// 			"subject": "模板堂精仿也买酒整站模板等1种商品",
	// 			"desc": "模板堂精仿也买酒整站模板等1种商品",
	// 			"order_sn": "2015041496313"
// 			}
// 		},
	} 
}


// end