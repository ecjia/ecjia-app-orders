<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 去发货显示页面
 * @author will
 *
 */
class shipping_detail_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		$result = $ecjia->admin_priv('order_view');
		
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		$order_id	= _POST('order_id', 0);
		if ($order_id <= 0) {
			EM_Api::outPut(101);
		}
		
		/*验证订单是否属于此入驻商*/
		if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
			$ru_id_group = RC_Model::model('orders/order_goods_model')->group('ru_id')->where(array('order_id' => $order_id))->get_field('ru_id', true);
			if (count($ru_id_group) > 1 || $ru_id_group[0] != $_SESSION['ru_id']) {
				return new ecjia_error('no_authority', '对不起，您没权限对此订单进行操作！');
			}
		}
		
		/* 获取订单信息*/
		$order_dbview = RC_Model::model('orders/order_order_infogoods_viewmodel');
		$order_dbview->view =  array(
				'order_goods' => array(
						'type'  =>	Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=>	'og',
						'on'    =>	'oi.order_id = og.order_id ',
				),
				'goods'	=> array(
						'type'  =>	Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=>	'g',
						'on'    =>	'og.goods_id = g.goods_id ',
				),
		);
		$field = 'oi.order_id, order_sn, consignee, country, province, city, district, address, mobile, shipping_id, shipping_name, oi.add_time, pay_time, og.rec_id, og.goods_id, og.product_id, og.goods_name, og.goods_price, og.goods_number, goods_thumb, goods_img, original_img';
		$order_list = $order_dbview->join(array('order_goods', 'goods'))->field($field)->where(array('oi.order_id' => $order_id))->select();
		if (empty($order_list)) {
			return ecjia_error('orders_empty', '订单信息不存在！');
		}
		
		/* 获取发货单信息*/
		$delivery_order_dbview = RC_Model::model('orders/delivery_order_viewmodel');
		$delivery_list = $delivery_order_dbview->join(array('delivery_goods'))->where(array('do.order_id' => $order_id))->select();
		
		$delivery_info = array();
		foreach ($order_list as $key => $val) {
			/* 首次设置订单信息*/
			if ($key == 0) {
				//收货人地址
				$db_region = RC_Model::model('shipping/region_model');
				$region_name = $db_region->where(array('region_id' => array('in'=>$val['country'],$val['province'],$val['city'],$val['district'])))->order('region_type')->select();
				$order['country_id']	= $order['country'];
				$order['province_id']	= $order['province'];
				$order['city_id']		= $order['city'];
				$order['district_id']	= $order['district'];
				$order['country']	= $region_name[0]['region_name'];
				$order['province']	= $region_name[1]['region_name'];
				$order['city']		= $region_name[2]['region_name'];
				$order['district']	= $region_name[3]['region_name'];
				$delivery_info = array(
						'order_id'	=> $val['order_id'],
						'order_sn'	=> $val['order_sn'],
						'consignee'	=> $val['consignee'],
						'country_id'	=> $val['country'],
						'province_id'	=> $val['province'],
						'city_id'		=> $val['city'],
						'district_id'	=> $val['district'],
						'country'		=> $region_name[0]['region_name'],
						'province'		=> $region_name[1]['region_name'],
						'city'			=> $region_name[2]['region_name'],
						'district'		=> $region_name[3]['region_name'],
						'address'		=> $val['address'],
						'mobile'		=> $val['mobile'],
						'shipping_id'	=> $val['shipping_id'],
						'shipping_name'	=> $val['shipping_name'],
						'add_time'		=> RC_Time::local_date(ecjia::config('time_format'), $val['add_time']),
						'pay_time'		=> empty($val['pay_time']) ? null : RC_Time::local_date(ecjia::config('time_format'), $val['pay_time']),
						'deliveryed_number'	=> 0,
				);
				/* 判断订单商品的发货情况*/
				if (!empty($delivery_list)) {
					foreach ($delivery_list as $v) {
						$delivery_info['deliveryed_number'] += $v['send_number'];
					}
				}
			}
			/* 设置订单商品信息*/
			$delivery_info['order_goods'][$key] = array(
					'rec_id'		=> $val['rec_id'],
					'goods_id'		=> $val['goods_id'], 
					'goods_name'	=> $val['goods_name'], 
					'product_id'	=> $val['product_id'],
					'goods_price'	=> $val['goods_price'],
					'goods_number'	=> $val['goods_number'],
					'img'			=> array(
							'small'	=> !empty($val['goods_thumb']) ? RC_Upload::upload_url($val['goods_thumb']) : '',
							'thumb'	=> !empty($val['goods_img']) ? RC_Upload::upload_url($val['goods_img']) : '',
							'url'	=> !empty($val['original_img']) ? RC_Upload::upload_url($val['original_img']) : '',
					)
			);
			
			/* 判断订单商品的发货情况*/
			if (!empty($delivery_list)) {
				//发货数量
				$send_number = 0;
				foreach ($delivery_list as $v) {
					/* 判断是否是同一件货品*/
					if ($v['goods_id'] == $val['goods_id'] && $v['product_id'] == $val['product_id']) {
						$send_number += $v['send_number'];
						/* 如果发货数量等于订单数量，去除已发货的商品*/
						if ($val['goods_number'] == $send_number) {
							unset($delivery_info['order_goods'][$key]);
						} else {
							$delivery_info['order_goods'][$key]['goods_number'] = $delivery_info['order_goods'][$key]['goods_number'] - $v['send_number']; 
						}
					}
				}
			}
			
		}
		$delivery_info['order_goods'] = array_merge($delivery_info['order_goods']);
		
		return $delivery_info;
	} 
}


// end