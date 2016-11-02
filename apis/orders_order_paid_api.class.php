<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单支付后处理订单的接口
 * @author royalwang
 *
 */
class orders_order_paid_api extends Component_Event_Api {
	
    /**
     * @param  $options['log_id'] 支付日志ID
     *         $options['money'] 支付金额
     *         $options['pay_status'] 支付状态
     *         $options['note'] 支付备注（非必须）
     *
     * @return array
     */
	public function call(&$options) {	
	    if (!is_array($options) 
	        || !isset($options['log_id']) 
	        || !isset($options['money']) 
	        || !isset($options['pay_status'])) {
	        return new ecjia_error('invalid_parameter', RC_Lang::get('orders::order.invalid_parameter'));
	    }

	    /* 检查支付的金额是否相符 */
	    if (!$this->check_money($options['log_id'], $options['money'])) {
	        return new ecjia_error('check_money_fail', RC_Lang::get('orders::order.check_money_fail'));
	    }
	    
	    if (in_array($options['pay_status'], array(PS_UNPAYED, PS_PAYING, PS_PAYED)) && $options['pay_status'] == PS_PAYED) {
	        /* 改变订单状态 */
	        $this->order_paid($options['log_id'], PS_PAYED, $options['note']);
	        return true;
	    }
		
		return false;
	}
	
	
	/**
	 * 检查支付的金额是否与订单相符
	 *
	 * @access  public
	 * @param   string   $log_id      支付编号
	 * @param   float    $money       支付接口返回的金额
	 * @return  true
	 */
	private function check_money($log_id, $money)
	{
// 	    $db_pay = RC_Loader::load_app_model('pay_log_model', 'orders');
	    if (is_numeric($log_id)) {
// 	        $amount = $db_pay->where(array('log_id' => $log_id))->get_field('order_amount');
	        
	        $amount = RC_DB::table('pay_log')->where('log_id', $log_id)->pluck('order_amount');
	    } else {
	        return false;
	    }
	     
	    if ($money == $amount) {
	        return true;
	    } else {
	        return false;
	    }
	}
	
	
	/**
	 * 修改订单的支付状态
	 *
	 * @access  public
	 * @param   string  $log_id     支付编号
	 * @param   integer $pay_status 状态
	 * @param   string  $note       备注
	 * @return  void
	 */
	private function order_paid($log_id, $pay_status = PS_PAYED, $note = '')
	{
// 	    $db_pay = RC_Loader::load_app_model('pay_log_model', 'orders');
// 	    $db_order = RC_Loader::load_app_model('order_info_model', 'orders');
// 	    $db_user = RC_Loader::load_app_model('user_account_model', 'user');
	    RC_Loader::load_app_func('order', 'orders');
	    /* 取得支付编号 */
	    $log_id = intval($log_id);
	    if ($log_id > 0) {
	        /* 取得要修改的支付记录信息 */
// 	        $pay_log = $db_pay->find(array('log_id' => $log_id));
	        $pay_log = RC_DB::table('pay_log')->where('log_id', $log_id)->first();
	        
	        if ($pay_log && $pay_log['is_paid'] == 0) {
	            /* 修改此次支付操作的状态为已付款 */
// 	            $db_pay->where(array('log_id' => $log_id))->update(array('is_paid' => 1));
	            RC_DB::table('pay_log')->where('log_id', $log_id)->update(array('is_paid' => 1));
	            
	            /* 根据记录类型做相应处理 */
	            if ($pay_log['order_type'] == PAY_ORDER) {
	                /* 取得订单信息 */
// 	                $order = $db_order->field('order_id, main_order_id, user_id, order_sn, consignee, address, tel, mobile, shipping_id, extension_code, extension_id, goods_amount, order_amount')
// 	                	->find(array('order_id' => $pay_log['order_id']));
					$order = RC_DB::table('order_info')->selectRaw('order_id, user_id, order_sn, consignee, address, tel, mobile, shipping_id, extension_code, extension_id, goods_amount, order_amount')
						->where('order_id', $pay_log['log_id'])->first();
	                
	                $order_id = $order['order_id'];
	                $order_sn = $order['order_sn'];
	                
	                /* 修改订单状态为已付款 */
	                $data = array(
	                    'order_status' => OS_CONFIRMED,
	                    'confirm_time' => RC_Time::gmtime(),
	                    'pay_status'   => $pay_status,
	                    'pay_time'     => RC_Time::gmtime(),
	                    'money_paid'   => $order['order_amount'],
	                    'order_amount' => 0,
	                );
// 	                $db_order->where(array('order_id' => $order_id))->update($data);
	                RC_DB::table('order_info')->where('order_id', $order_id)->update($data);
	                
	                /* 记录订单操作记录 */
	                order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, RC_Lang::get('orders::order.buyers'));
	
// 	                if ($main_order_id <= 0) {
// 	                	$data = array(
//                 			'order_status' => OS_CONFIRMED,
//                 			'confirm_time' => RC_Time::gmtime(),
//                 			'pay_status'   => $pay_status,
//                 			'pay_time'     => RC_Time::gmtime(),
// 	                	);
// 	                	$db_order->where(array('main_order_id' => $order_id))->update($data);
// 	                	$db_order->inc('money_paid', "main_order_id=$order_id", '0, money_paid=order_amount, order_amount=0');
	                	
// 	                	$order_res = $db_order->field('order_sn')->where(array('main_order_id' => $order_id))->select();
// 	                	foreach ($order_res AS $row) {
// 	                		/* 记录订单操作记录 */
// 	                		order_action($row['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, RC_Lang::get('orders::order.buyers'));
// 	                	}
// 	                }
	                
// 	                RC_Model::model('orders/order_status_log_model')->insert(array(
// 		                'order_status'	=> RC_Lang::get('orders::order.ps.'.PS_PAYED),
// 		                'order_id'		=> $order_id,
// 		                'message'		=> RC_Lang::get('orders::order.notice_merchant_message'),
// 		                'add_time'		=> RC_Time::gmtime(),
// 	                ));
	                RC_DB::table('order_status_log')->insert(array(
		                'order_status'	=> RC_Lang::get('orders::order.ps.'.PS_PAYED),
		                'order_id'		=> $order_id,
		                'message'		=> RC_Lang::get('orders::order.notice_merchant_message'),
		                'add_time'		=> RC_Time::gmtime(),
	                ));
	                
	                $result = ecjia_app::validate_application('sms');
	                if (!is_ecjia_error($result)) {
		                /* 收货验证短信  */
		                if (ecjia::config('sms_receipt_verification') == '1' && ecjia::config('sms_shop_mobile') != '') {
// 		                	$db_term_meta = RC_Loader::load_model('term_meta_model');
// 		                	$meta_where = array(
// 								'object_type'	=> 'ecjia.order',
// 								'object_group'	=> 'order',
// 								'meta_key'		=> 'receipt_verification',
// 							);
// 							$max_code = $db_term_meta->where($meta_where)->max('meta_value');
							
							$max_code = RC_DB::table('term_meta')->where('object_type', 'ecjia.order')->where('object_group', 'order')->where('meta_key', 'receipt_verification')->max('meta_value');
							
							$max_code = $max_code ? ceil($max_code/10000) : 1000000;
							$code = $max_code . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
// 			                $code = rand(100000, 999999);
			                $tpl_name = 'sms_receipt_verification';
			                $tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
			                if (!empty($tpl)) {
			                	ecjia_front::$controller->assign('order_sn', $order['order_sn']);
			                	ecjia_front::$controller->assign('user_name', $order['consignee']);
			                	ecjia_front::$controller->assign('code', $code);
			                	
			                	$content = ecjia_front::$controller->fetch_string($tpl['template_content']);
			                	 
			                	$options = array(
		                			'mobile' 		=> $order['mobile'],
		                			'msg'			=> $content,
		                			'template_id' 	=> $tpl['template_id'],
			                	);
			                	$response = RC_Api::api('sms', 'sms_send', $options);
			                	 
// 			                	$db_term_meta = RC_Loader::load_model('term_meta_model');
			                	$meta_data = array(
		                			'object_type'	=> 'ecjia.order',
		                			'object_group'	=> 'order',
		                			'object_id'		=> $order_id,
		                			'meta_key'		=> 'receipt_verification',
		                			'meta_value'	=> $code,
			                	);
// 			                	$db_term_meta->insert($meta_data);
			                	RC_DB::table('term_meta')->insert($data);
			                	
			                }
// 			                if ($main_order_id <= 0) {
// 			                	$order_res = $db_order->field('order_id')->where(array('main_order_id' => $order_id))->select();
// 			                	foreach ($order_res AS $row) {
// 			                		$meta_data = array(
// 		                				'object_type'	=> 'ecjia.order',
// 		                				'object_group'	=> 'order',
// 		                				'object_id'		=> $row['order_id'],
// 		                				'meta_key'		=> 'receipt_verification',
// 		                				'meta_value'	=> $code,
// 			                		);
// 			                		$db_term_meta->insert($meta_data);
// 			                	}
// 			                }
		                }
		                
						
		                /* 客户付款短信提醒 */
		                if (ecjia::config('sms_order_payed') == '1' && ecjia::config('sms_shop_mobile') != '') {
		                	//发送短信
		                	$tpl_name = 'order_payed_sms';
		                	$tpl = RC_Api::api('sms', 'sms_template', $tpl_name);
		                	if (!empty($tpl)) {
			                	ecjia_front::$controller->assign('order_sn', $order['order_sn']);
			                	ecjia_front::$controller->assign('consignee', $order['consignee']);
			                	ecjia_front::$controller->assign('mobile', $order['mobile']);
			                	ecjia_front::$controller->assign('order_amount', $order['order_amount']);
			                	$content = ecjia_front::$controller->fetch_string($tpl['template_content']);
			                	 
			                	$options = array(
		                			'mobile' 		=> ecjia::config('sms_shop_mobile'),
		                			'msg'			=> $content,
		                			'template_id' 	=> $tpl['template_id'],
			                	);
			                	$response = RC_Api::api('sms', 'sms_send', $options);
		                	}
		                }
	                }

                    /* 对虚拟商品的支持 */
                    $virtual_goods = get_virtual_goods($order_id);
                    if (!empty($virtual_goods)) {
                        $msg = '';
                        if (!virtual_goods_ship($virtual_goods, $msg, $order_sn, true)) {
                            $GLOBALS['_LANG']['pay_success'] .= '<div style="color:red;">'.$msg.'</div>'.$GLOBALS['_LANG']['virtual_goods_ship_fail'];
                        }

                        /* 如果订单没有配送方式，自动完成发货操作 */
                        if ($order['shipping_id'] == -1) {
                            /* 将订单标识为已发货状态，并记录发货记录 */
                            $data = array(
                                'shipping_status' => SS_SHIPPED,
                                'shipping_time' => RC_Time::gmtime(),
                            );
//                             $db_order->where(array('order_id' => $order_id))->update($data);
                            RC_DB::table('order_info')->where('order_id', $order_id)->update($data);

                            /* 记录订单操作记录 */
                            order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, RC_Lang::lang('buyer'));
                            $integral = integral_to_give($order);
                            $options = array(
                            	'user_id'		=> $order['user_id'],
                            	'rank_points'	=> intval($integral['rank_points']),
                            	'pay_points'	=> intval($integral['custom_points']),
                            	'change_desc'	=> sprintf(RC_Lang::lang('order_gift_integral'), $order['order_sn'])
                            );
                            RC_Api::api('user', 'account_change_log', $options);
                        }
                    }

                } elseif ($pay_log['order_type'] == PAY_SURPLUS) {
//                     $res_id = $db_user->field('id')->find(array('id' => $pay_log['order_id'], 'is_paid' => 1));
                    $res_id = RC_DB::table('users')->select('id')->where('id', $pay_log['order_id'])->where('is_paid', 1)->first();
                    
                    if (empty($res_id)) {
                        /* 更新会员预付款的到款状态 */
                        $data = array(
                            'paid_time' => RC_Time::gmtime(),
                            'is_paid'   => 1
                        );
//                         $db_user->where(array('id' => $pay_log['order_id']))->update($data);
                        RC_DB::table('users')->where('id', $pay_log['order_id'])->update($data);
                        
                        /* 取得添加预付款的用户以及金额 */
//                         $arr = $db_user->field('user_id, amount')->find(array('id' => $pay_log['order_id']));
                        $arr = RC_DB::table('users')->select('user_id', 'amount')->where('id', $pay_log['order_id'])->first();
                        
                        /* 修改会员帐户金额 */
                        $options = array(
                        	'user_id'		=> $arr['user_id'],
                        	'user_money'	=> $arr['amount'],
                        	'change_desc'	=> RC_Lang::lang('surplus_type_0'),
                        	'change_type'	=> ACT_SAVING
                        );
                        RC_Api::api('user', 'account_change_log', $options);
                    }
                }
            } else {
	                /* 取得已发货的虚拟商品信息 */
// 	                $post_virtual_goods = get_virtual_goods($pay_log['order_id'], true);
	
	                /* 有已发货的虚拟商品 */
// 	                if (!empty($post_virtual_goods)) {
// 	                    $msg = '';
// 	                    /* 检查两次刷新时间有无超过12小时 */
// 	                    $row = $db_order->field('pay_time, order_sn')->find(array('order_id' => $pay_log['order_id']));
// 	                    $intval_time = RC_Time::gmtime() - $row['pay_time'];
// 	                    if ($intval_time >= 0 && $intval_time < 3600 * 12) {
// 	                        $virtual_card = array();
// 	                        foreach ($post_virtual_goods as $code => $goods_list) {
                            /* 只处理虚拟卡 */
                            // 	                        if ($code == 'virtual_card') {
                            // 	                            foreach ($goods_list as $goods) {
                            // 	                                $info = virtual_card_result($row['order_sn'], $goods);
                            // 	                                if ($info) {
                            // 	                                    $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>$info);
                            // 	                                }
                            // 	                            }
                            // 	                            ecjia::$view_object->assign('virtual_card', $virtual_card);
                            // 	                        }
//                             }
//                         } else {
//                             $msg = '<div>' .  $GLOBALS['_LANG']['please_view_order_detail'] . '</div>';
//                         }
//                         $GLOBALS['_LANG']['pay_success'] .= $msg;
//                     }
	
                    /* 取得未发货虚拟商品 */
//                     $virtual_goods = get_virtual_goods($pay_log['order_id'], false);
//                     if (!empty($virtual_goods)) {
//                         $GLOBALS['_LANG']['pay_success'] .= '<br />' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
//                     }
            }
        }
    }
}

// end