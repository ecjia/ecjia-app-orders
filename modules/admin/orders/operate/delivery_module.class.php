<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单进行发货
 * @author will
 *
 */
class delivery_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		$result_view = $ecjia->admin_priv('order_view'); 
		$result_edit = $ecjia->admin_priv('order_ss_edit');
		if (is_ecjia_error($result_view)) {
			EM_Api::outPut($result_view);
		} elseif (is_ecjia_error($result_edit)) {
			EM_Api::outPut($result_edit);
		}

		$order_id		= _POST('order_id', 0);
		$invoice_no		= _POST('invoice_no');
		/* 发货数量*/
		$send_number	= _POST('send_number');//array('123' => 1);

		$action_note	= _POST('action_note');
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}
		/*验证订单是否属于此入驻商*/
		if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
			$ru_id_group = RC_Model::model('orders/order_goods_model')->group('ru_id')->where(array('order_id' => $order_id))->get_field('ru_id', true);
			if (count($ru_id_group) > 1 || $ru_id_group[0] != $_SESSION['ru_id']) {
				return new ecjia_error('no_authority', '对不起，您没权限对此订单进行操作！');
			}
		}
		
		$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
		if (empty($order_info)) {
			EM_Api::outPut(101);
		}
		
		/* 订单是否已全部分单检查 */
		if ($order_info['order_status'] == OS_SPLITED) {
			return new ecjia_error('already_splited', '订单已全部发货！');
		}
		
		RC_Loader::load_app_func('function', 'orders');
		RC_Loader::load_app_func('order', 'orders');
		/* 取得订单商品 */
		$_goods = get_order_goods(array('order_id' => $order_id));
		$goods_list = $_goods['goods_list'];
		
		
		/* 检查此单发货数量填写是否正确 合并计算相同商品和货品 */
		if (!empty($send_number) && !empty($goods_list)) {
			$goods_no_package = array();
			foreach ($goods_list as $key => $value) {
				/* 去除 此单发货数量 等于 0 的商品 */
				if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list'])) {
					// 如果是货品则键值为商品ID与货品ID的组合
					$_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);
		
					// 统计此单商品总发货数 合并计算相同ID商品或货品的发货数
					if (empty($goods_no_package[$_key])) {
						$goods_no_package[$_key] = $send_number[$value['rec_id']];
					} else {
						$goods_no_package[$_key] += $send_number[$value['rec_id']];
					}
		
					//去除
					if ($send_number[$value['rec_id']] <= 0) {
						unset($send_number[$value['rec_id']], $goods_list[$key]);
						continue;
					}
				} else {
					/* 组合超值礼包信息 */
					$goods_list[$key]['package_goods_list'] = package_goods($value['package_goods_list'], $value['goods_number'], $value['order_id'], $value['extension_code'], $value['goods_id']);
		
					/* 超值礼包 */
					foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
						// 如果是货品则键值为商品ID与货品ID的组合
						$_key = empty($pg_value['product_id']) ? $pg_value['goods_id'] : ($pg_value['goods_id'] . '_' . $pg_value['product_id']);
		
						//统计此单商品总发货数 合并计算相同ID产品的发货数
						if (empty($goods_no_package[$_key])) {
							$goods_no_package[$_key] = $send_number[$value['rec_id']][$pg_value['g_p']];
						} else {
							//否则已经存在此键值
							$goods_no_package[$_key] += $send_number[$value['rec_id']][$pg_value['g_p']];
						}
		
						//去除
						if ($send_number[$value['rec_id']][$pg_value['g_p']] <= 0) {
							unset($send_number[$value['rec_id']][$pg_value['g_p']], $goods_list[$key]['package_goods_list'][$pg_key]);
						}
					}
		
					if (count($goods_list[$key]['package_goods_list']) <= 0) {
						unset($send_number[$value['rec_id']], $goods_list[$key]);
						continue;
					}
				}
		
				/* 发货数量与总量不符 */
				if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list'])) {
					$sended = order_delivery_num($order_id, $value['goods_id'], $value['product_id']);
					if (($value['goods_number'] - $sended - $send_number[$value['rec_id']]) < 0) {
						return new ecjia_error('act_ship_num', '此单发货数量不能超出订单商品数量！');
					}
				} else {
					/* 超值礼包 */
					foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value) {
						if (($pg_value['order_send_number'] - $pg_value['sended'] - $send_number[$value['rec_id']][$pg_value['g_p']]) < 0) {
							return new ecjia_error('act_ship_num', '此单发货数量不能超出订单商品数量！');
						}
					}
				}
			}
		}
		
		/* 对上一步处理结果进行判断 兼容 上一步判断为假情况的处理 */
		if (empty($send_number) || empty($goods_list)) {
			return new ecjia_error('shipping_empty', '没有可发货的商品！');
		}
		
		/* 检查此单发货商品库存缺货情况 */
		/* $goods_list已经过处理 超值礼包中商品库存已取得 */
		$virtual_goods = array();
		$package_virtual_goods = array();
		foreach ($goods_list as $key => $value) {
			// 商品（超值礼包）
			if ($value['extension_code'] == 'package_buy') {
				foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
					if ($pg_value['goods_number'] < $goods_no_package[$pg_value['g_p']] &&
					((ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) ||
							(ecjia::config('use_storage') == '0' && $pg_value['is_real'] == 0))) {
						return new ecjia_error('act_good_vacancy', '商品已缺货！');
					}
		
					/* 商品（超值礼包） 虚拟商品列表 package_virtual_goods*/
					if ($pg_value['is_real'] == 0) {
						$package_virtual_goods[] = array(
								'goods_id'		=> $pg_value['goods_id'],
								'goods_name'	=> $pg_value['goods_name'],
								'num'			=> $send_number[$value['rec_id']][$pg_value['g_p']]
						);
					}
				}
			} elseif ($value['extension_code'] == 'virtual_card' || $value['is_real'] == 0) {
				// 商品（虚货）
				$num = RC_Model::model('goods/virtual_card_model')->where(array('goods_id' => $value['goods_id'], 'is_saled' => 0))->count();
					
				if (($num < $goods_no_package[$value['goods_id']]) && !(ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE)) {
					return new ecjia_error('virtual_card_oos', '虚拟卡已缺货！');
				}
		
				/* 虚拟商品列表 virtual_card*/
				if ($value['extension_code'] == 'virtual_card') {
					$virtual_goods[$value['extension_code']][] = array('goods_id' => $value['goods_id'], 'goods_name' => $value['goods_name'], 'num' => $send_number[$value['rec_id']]);
				}
			} else {
				// 商品（实货）、（货品）
				//如果是货品则键值为商品ID与货品ID的组合
				$_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);
		
				/* （实货） */
				if (empty($value['product_id'])) {
					$num = RC_Model::model('goods/goods_model')->where(array('goods_id' => $value['goods_id']))->get_field('goods_number');
				} else {
					/* （货品） */
					$num = RC_Model::model('goods/products_model')->where(array('goods_id' => $value['goods_id'], 'product_id' => $value['product_id']))->get_field('product_number');
				}
		
				if (($num < $goods_no_package[$_key]) && ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) {
					return new ecjia_error('act_good_vacancy', '商品已缺货！');
				}
			}
		}
		
		
		/* 过滤字段项 */
		$filter_fileds = array(
				'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',
				'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',
				'email', 'zipcode', 'tel', 'mobile', 'best_time', 'postscript', 'insure_fee',
				'agency_id', 'delivery_sn', 'action_user', 'update_time',
				'suppliers_id', 'status', 'order_id', 'shipping_name'
		);
		$_delivery = array();
		foreach ($filter_fileds as $value) {
			$_delivery[$value] = $order_info[$value];
		}
		
		/* 生成发货单 */
		/* 获取发货单号和流水号 */
		$_delivery['delivery_sn']	= get_delivery_sn();
		$delivery_sn				= $_delivery['delivery_sn'];
		/* 获取当前操作员 */
		$_delivery['action_user']	= $_SESSION['admin_name'];
		/* 获取发货单生成时间 */
		$_delivery['update_time']	= RC_Time::gmtime();
		$delivery_time				= $_delivery['update_time'];
		
		$_delivery['add_time']		= RC_Model::model('orders/order_info_model')->where(array('order_id' => $order_id))->get_field('add_time');
			
		/* 获取发货单所属供应商 */
		// 		$delivery['suppliers_id']	= $suppliers_id;
		/* 设置默认值 */
		$_delivery['status']		= 2; // 正常
		$_delivery['order_id']		= $order_id;
		
		
		/* 发货单入库 */
		$delivery_id = RC_Model::model('orders/delivery_order_model')->insert($_delivery);
		/* 记录日志 */
		ecjia_admin::admin_log('订单号是 '.$order_info['order_sn'], 'produce', 'delivery_order');
		if ($delivery_id) {
			$delivery_goods = array();
			//发货单商品入库
			if (!empty($goods_list)) {
				foreach ($goods_list as $value) {
					// 商品（实货）（虚货）
					if (empty($value['extension_code']) || $value['extension_code'] == 'virtual_card') {
						$delivery_goods = array(
								'delivery_id'	=> $delivery_id,
								'goods_id'		=> $value['goods_id'],
								'product_id'	=> $value['product_id'],
								'product_sn'	=> $value['product_sn'],
								'goods_id'		=> $value['goods_id'],
								'goods_name'	=> addslashes($value['goods_name']),
								'brand_name'	=> addslashes($value['brand_name']),
								'goods_sn'		=> $value['goods_sn'],
								'send_number'	=> $send_number[$value['rec_id']],
								'parent_id'		=> 0,
								'is_real'		=> $value['is_real'],
								'goods_attr'	=> addslashes($value['goods_attr'])
						);
		
						/* 如果是货品 */
						if (!empty($value['product_id'])) {
							$delivery_goods['product_id'] = $value['product_id'];
						}
						$query = RC_Model::model('orders/delivery_goods_model')->insert($delivery_goods);
					} elseif ($value['extension_code'] == 'package_buy') {
						// 商品（超值礼包）
						foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
							$delivery_pg_goods = array(
									'delivery_id'		=> $delivery_id,
									'goods_id'			=> $pg_value['goods_id'],
									'product_id'		=> $pg_value['product_id'],
									'product_sn'		=> $pg_value['product_sn'],
									'goods_name'		=> $pg_value['goods_name'],
									'brand_name'		=> '',
									'goods_sn'			=> $pg_value['goods_sn'],
									'send_number'		=> $send_number[$value['rec_id']][$pg_value['g_p']],
									'parent_id'			=> $value['goods_id'], // 礼包ID
									'extension_code'	=> $value['extension_code'], // 礼包
									'is_real'			=> $pg_value['is_real']
							);
							$query = RC_Model::model('orders/delivery_goods_model')->insert($delivery_pg_goods);
						}
					}
				}
			}
		} else {
			return new ecjia_error('shipping_error', '发货失败！');
		}
		unset($filter_fileds, $delivery, $_delivery, $order_finish);
		
		/* 定单信息更新处理 */
		if (true) {
			/* 定单信息 */
			$_sended = & $send_number;
			foreach ($_goods['goods_list'] as $key => $value) {
				if ($value['extension_code'] != 'package_buy') {
					unset($_goods['goods_list'][$key]);
				}
			}
			foreach ($goods_list as $key => $value) {
				if ($value['extension_code'] == 'package_buy') {
					unset($goods_list[$key]);
				}
			}
			$_goods['goods_list'] = $goods_list + $_goods['goods_list'];
			unset($goods_list);
		
			/* 更新订单的虚拟卡 商品（虚货） */
			$_virtual_goods = isset($virtual_goods['virtual_card']) ? $virtual_goods['virtual_card'] : '';
			update_order_virtual_goods($order_id, $_sended, $_virtual_goods);
		
			/* 更新订单的非虚拟商品信息 即：商品（实货）（货品）、商品（超值礼包）*/
			update_order_goods($order_id, $_sended, $_goods['goods_list']);
		
			/* 标记订单为已确认 “发货中” */
			/* 更新发货时间 */
			$order_finish = get_order_finish($order_id);
			$shipping_status = SS_SHIPPED_ING;
			if ($order_info['order_status'] != OS_CONFIRMED && $order_info['order_status'] != OS_SPLITED && $order_info['order_status'] != OS_SPLITING_PART) {
				$arr['order_status']	= OS_CONFIRMED;
				$arr['confirm_time']	= GMTIME_UTC;
			}
		
			$arr['order_status']		= $order_finish ? OS_SPLITED : OS_SPLITING_PART; // 全部分单、部分分单
			$arr['shipping_status']		= $shipping_status;
			update_order($order_id, $arr);
		}
		/* 记录log */
		order_action($order_info['order_sn'], $arr['order_status'], $shipping_status, $order_info['pay_status'], $action_note);
		
		$order_info['invoice_no'] = $invoice_no;
		$delivery_result = delivery_order($delivery_id, $order_info);
		
		return $delivery_result;
	} 
}

function delivery_order($delivery_id, $order) {
	
	/* 发货处理*/
	$delivery_order = delivery_order_info($delivery_id);
	/* 检查此单发货商品库存缺货情况 */
	$virtual_goods			= array();
	$delivery_stock_result	= RC_Model::model('orders/delivery_viewmodel')->join(array('goods', 'products'))->where(array('dg.delivery_id' => $delivery_id))->group(array('dg.product_id', 'dg.goods_id'))->select();
	
	/* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
	if(!empty($delivery_stock_result)) {
		foreach ($delivery_stock_result as $value) {
			if (($value['sums'] > $value['storage'] || $value['storage'] <= 0) &&
			((ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) ||
					(ecjia::config('use_storage') == '0' && $value['is_real'] == 0))) {
				return new ecjia_error('act_good_vacancy', '['.$value['goods_name'].']'.'商品已缺货');
			}
	
			/* 虚拟商品列表 virtual_card */
			if ($value['is_real'] == 0) {
				$virtual_goods[] = array(
						'goods_id'		=> $value['goods_id'],
						'goods_name'	=> $value['goods_name'],
						'num'			=> $value['send_number']
				);
			}
		}
	} else {
		$db_delivery = RC_Model::model('orders/delivery_viewmodel');
		$db_delivery->view = array(
				'goods' => array(
						'type'		=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'		=> 'g',
						'field'		=> 'dg.goods_id, dg.is_real, SUM(dg.send_number) AS sums, g.goods_number, g.goods_name, dg.send_number',
						'on'		=> 'dg.goods_id = g.goods_id ',
				)
		);
	
		$delivery_stock_result = $db_delivery->where(array('dg.delivery_id' => $delivery_id))->group('dg.goods_id')->select();
	
		foreach ($delivery_stock_result as $value) {
			if (($value['sums'] > $value['goods_number'] || $value['goods_number'] <= 0) &&
			((ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) ||
					(ecjia::config('use_storage') == '0' && $value['is_real'] == 0))) {
				
				return new ecjia_error('act_good_vacancy', '['.$value['goods_name'].']'.'商品已缺货');
				
			}
	
			/* 虚拟商品列表 virtual_card*/
			if ($value['is_real'] == 0) {
				$virtual_goods[] = array(
						'goods_id'		=> $value['goods_id'],
						'goods_name'	=> $value['goods_name'],
						'num'			=> $value['send_number']
				);
			}
		}
	}
	
	/* 发货 */
	/* 处理虚拟卡 商品（虚货） */
	if (is_array($virtual_goods) && count($virtual_goods) > 0) {
		foreach ($virtual_goods as $virtual_value) {
			virtual_card_shipping($virtual_value, $order['order_sn'], $msg, 'split');
		}
	}
	
	/* 如果使用库存，且发货时减库存，则修改库存 */
	if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_SHIP) {
		foreach ($delivery_stock_result as $value) {
			/* 商品（实货）、超级礼包（实货） */
			if ($value['is_real'] != 0) {
				/* （货品） */
				if (!empty($value['product_id'])) {
					$data = array(
							'product_number' => $value['storage'] - $value['sums'],
					);
					RC_Model::model('goods/products_model')->where(array('product_id' => $value['product_id']))->update($data);
				} else {
					$data = array(
							'goods_number' => $value['storage'] - $value['sums'],
					);
					RC_Model::model('goods/goods_model')->where(array('goods_id' => $value['goods_id']))->update($data);
				}
			}
		}
	}
	
	/* 修改发货单信息 */
	$invoice_no = str_replace(',', '<br>', $order['invoice_no']);
	$invoice_no = trim($invoice_no, '<br>');
	$_delivery['invoice_no']	= $invoice_no;
	$_delivery['status']		= 0;	/* 0，为已发货 */
	$result = RC_Model::model('orders/delivery_order_model')->where(array('delivery_id' => $delivery_id))-> update($_delivery);
	
	if (!$result) {
		return new ecjia_error('act_false', '发货失败！');
	}
	
	/* 标记订单为已确认 “已发货” */
	/* 更新发货时间 */
	$order_finish				= get_all_delivery_finish($order['order_id']);
	$shipping_status			= ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
	$arr['shipping_status']		= $shipping_status;
	$arr['shipping_time']		= RC_Time::gmtime(); // 发货时间
	$arr['invoice_no']			= trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');
	update_order($order['order_id'], $arr);
	
	/* 发货单发货记录log */
	order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], '', null, 1);
	ecjia_admin::admin_log('发货，订单号是'.$order['order_sn'], 'setup', 'order');
	
	/* 如果当前订单已经全部发货 */
	if ($order_finish) {
		/* 如果订单用户不为空，计算积分，并发给用户；发红包 */
		if ($order['user_id'] > 0) {
			/* 取得用户信息 */
			$user = user_info($order['user_id']);
			/* 计算并发放积分 */
			$integral = integral_to_give($order);
			$options = array(
					'user_id'		=> $order['user_id'],
					'rank_points'	=> intval($integral['rank_points']),
					'pay_points'	=> intval($integral['custom_points']),
					'change_desc'	=> sprintf('订单 %s 赠送的积分', $order['order_sn'])
			);
			RC_Api::api('user', 'account_change_log',$options);
			/* 发放红包 */
			send_order_bonus($order['order_id']);
		}
	
		/* 发送邮件 */
		$cfg = ecjia::config('send_ship_email');
		if ($cfg == '1') {
			$order['invoice_no'] = $invoice_no;
			$tpl_name = 'deliver_notice';
			$tpl   = RC_Api::api('mail', 'mail_template', $tpl_name);
			if (empty($tpl)) {
				ecjia::$controller->assign('order'			, $order);
				ecjia::$controller->assign('send_time'		, RC_Time::local_date(ecjia::config('time_format')));
				ecjia::$controller->assign('shop_name'		, ecjia::config('shop_name'));
				ecjia::$controller->assign('send_date'		, RC_Time::local_date(ecjia::config('date_format')));
				ecjia::$controller->assign('confirm_url'		, SITE_URL . 'receive.php?id=' . $order['order_id'] . '&con=' . rawurlencode($order['consignee']));
				ecjia::$controller->assign('send_msg_url'	, SITE_URL . RC_Uri::url('user/admin/message_list','order_id=' . $order['order_id']));
				
				$content = ecjia::$controller->fetch_string($tpl['template_content']);
				
				RC_Mail::send_mail($order['consignee'], $order['email'] , $tpl['template_subject'], $content, $tpl['is_html']);
			}
		}
		$result = ecjia_app::validate_application('sms');
		if (!is_ecjia_error($result)) {
			/* 如果需要，发短信 */
			if (ecjia::config('sms_order_shipped') == '1' && $order['mobile'] != '') {
				$order['invoice_no'] = $invoice_no;
				//发送短信
				$tpl_name = 'order_shipped_sms';
				$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
				if (!empty($tpl)) {
					ecjia::$controller->assign('order_sn', $order['order_sn']);
					ecjia::$controller->assign('shipped_time', RC_Time::local_date(ecjia::config('time_format'), $arr['shipping_time']));
					ecjia::$controller->assign('mobile', $order['mobile']);
					ecjia::$controller->assign('order', $order);
	
					$content = ecjia::$controller->fetch_string($tpl['template_content']);
	
					$options = array(
							'mobile' 		=> $order['mobile'],
							'msg'			=> $content,
							'template_id' 	=> $tpl['template_id'],
					);
					$response = RC_Api::api('sms', 'sms_send', $options);
				}
			}
		}
	}
	return array();
}


// end