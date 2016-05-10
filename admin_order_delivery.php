<?php

/**
 * ECJIA 订单-发货单管理
 */

defined('IN_ECJIA') or exit('No permission resources.');
RC_Loader::load_sys_class('ecjia_admin', false);

class admin_order_delivery extends ecjia_admin {
	private $db_delivery_order;
	private $db_order_region;
	private $db_delivery;
	private $db_order_action;
	private $db_goods;
	private $db_products;
	private $db_order_status_log;
	
// 	private $db_admin_user;
	
	public function __construct() {
		parent::__construct();

		RC_Lang::load('order');

		RC_Loader::load_app_func('order','orders');
		RC_Loader::load_app_func('common','goods');
		RC_Loader::load_app_func('function');
		$this->db_delivery_order	= RC_Loader::load_app_model('delivery_order_model','orders');
		$this->db_order_region		= RC_Loader::load_app_model('order_region_viewmodel');
		$this->db_delivery			= RC_Loader::load_app_model('delivery_goods_model');
		$this->db_order_action		= RC_Loader::load_app_model('order_action_model');
		$this->db_goods				= RC_Loader::load_app_model('goods_model','goods');
		$this->db_products			= RC_Loader::load_app_model('products_model','goods');
		$this->db_order_status_log  = RC_Loader::load_app_model('order_status_log_model','orders');
		
// 		$this->db_admin_user		= RC_Loader::load_model('admin_user_model');

		// 增加操作对象
		assign_adminlog_content();

		/* 加载所有全局 js/css */
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');

		/* 列表页 js/css */
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('order_delivery', RC_Uri::home_url('content/apps/orders/statics/js/order_delivery.js'));
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('chosen');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('jquery-uniform');
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('发货单列表'),RC_Uri::url('orders/admin_order_delivery/init')));
	}
	
	/**
	 * 发货单列表
	 */
	public function init() 
	{
		/* 检查权限 */
		$this->admin_priv('delivery_view');
		ecjia_screen::get_current_screen()->remove_last_nav_here();
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('发货单列表')));
		/* 查询 */
		$result = get_delivery_list();
		
		ecjia_screen::get_current_screen()->add_help_tab( array(
		'id'		=> 'overview',
		'title'		=> __('概述'),
		'content'	=>
		'<p>' . __('欢迎访问ECJia智能后台发货单列表页面，系统中有关发货单都会显示在此列表中。') . '</p>'
		) );
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
		'<p><strong>' . __('更多信息:') . '</strong></p>' .
		'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:发货单列表" target="_blank">关于发货单列表帮助文档</a>') . '</p>'
		);
		
		/* 模板赋值 */
		$this->assign('ur_here'			, RC_Lang::lang('09_delivery_order'));
		$this->assign('os_unconfirmed'	, OS_UNCONFIRMED);
		$this->assign('cs_await_pay'	, CS_AWAIT_PAY);
		$this->assign('cs_await_ship'	, CS_AWAIT_SHIP);
		$this->assign('delivery_list'	, $result);
		$this->assign('filter'			, $result['filter']);
		$this->assign('search_action'	, RC_Uri::url('orders/admin_order_delivery/init'));
		$this->assign('form_action'		, RC_Uri::url('orders/admin_order_delivery/remove'));
		
		$this->assign_lang();
		$this->display('delivery_list.dwt');
	}
	
	/**
	 * 发货单详细
	 */
	public function delivery_info() 
	{
		/* 检查权限 */
		$this->admin_priv('delivery_view');
		$delivery_id = intval(trim($_GET['delivery_id']));
		/* 根据发货单id查询发货单信息 */
		if (!empty($delivery_id)) {
			$delivery_order = delivery_order_info($delivery_id);
		} else {
			$this->showmessage(__('无法找到对应发货单！'), ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR);
		}
		if (empty($delivery_order)) {
			$this->showmessage(__('无法找到对应发货单！'), ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR);
		}
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('发货单操作：查看')));
		/* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
//		TODO:办事处模块赞无，暂时注释
//		$agency_id = $this->db_admin_user->get_admin_agency_id($_SESSION['admin_id']);
//		if ($agency_id > 0) {
//			if ($delivery_order['agency_id'] != $agency_id) {
//				$this->showmessage(RC_Lang::lang('priv_error'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
//			}
//
//			/* 取当前办事处信息 */
//			$delivery_order['agency_name'] = $this->db_agency->find(array('agency_id' => $agency_id))->get_field('agency_name');
//		}

		/* 取得用户名 */
		if ($delivery_order['user_id'] > 0) {
			$user = user_info($delivery_order['user_id']);
			if (!empty($user)) {
				$delivery_order['user_name'] = $user['user_name'];
			}
		}
	
		/* 取得区域名 */
		$field = array("concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''),'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region");
		$region = $this->db_order_region->field($field)->find('o.order_id = "'.$delivery_order['order_id'].'"');
		$delivery_order['region'] = $region['region'] ;
	
		/* 是否保价 */
		$order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;
		/* 取得发货单商品 */
		$goods_list = $this->db_delivery->where(array('delivery_id' => $delivery_order['delivery_id']))->select();
		/* 是否存在实体商品 */
		$exist_real_goods = 0;
		if ($goods_list) {
			foreach ($goods_list as $value) {
				if ($value['is_real']) {
					$exist_real_goods++;
				}
			}
		}
		/* 取得订单操作记录 */
		$act_list = array();
		$data = $this->db_order_action->where(array('order_id' => $delivery_order['order_id'] , 'action_place' => 1))->order(array('log_time' => 'asc' , 'action_id' => 'asc'))->select();
		if(!empty($data)) {
			foreach ($data as $key => $row) {
				$row['order_status']	= RC_Lang::lang('os/'.$row['order_status']);
				$row['pay_status']		= RC_Lang::lang('ps/'.$row['pay_status']);
				$row['shipping_status']	= ($row['shipping_status'] == SS_SHIPPED_ING) ? RC_Lang::lang('ss_admin/'.SS_SHIPPED_ING) : RC_Lang::lang('ss/'.$row['shipping_status']);
				$row['action_time']		= RC_Time::local_date(ecjia::config('time_format'), $row['log_time']);
				$act_list[]				= $row;
			}
		}

		/* 模板赋值 */
		$this->assign('action_list'			, $act_list);
		$this->assign('delivery_order'		, $delivery_order);
		$this->assign('exist_real_goods'	, $exist_real_goods);
		$this->assign('goods_list'			, $goods_list);
		$this->assign('delivery_id'			, $delivery_id); // 发货单id
		/* 显示模板 */
		$this->assign('ur_here'				, RC_Lang::lang('delivery_operate') . RC_Lang::lang('detail'));	
		$this->assign('action_link'			, array('href' => RC_Uri::url('orders/admin_order_delivery/init'), 'text' => RC_Lang::lang('09_delivery_order')));
		$this->assign('action_act'			, ($delivery_order['status'] == 2) ? 'delivery_ship' : 'delivery_cancel_ship');
		$this->assign('form_action'			, ($delivery_order['status'] == 2) ? RC_Uri::url('orders/admin_order_delivery/delivery_ship') : RC_Uri::url('orders/admin_order_delivery/delivery_cancel_ship'));
		
		$this->assign_lang();
		$this->display('delivery_info.dwt');
	}
	
	/**
	 * 发货单发货确认
	 */
	public function delivery_ship() 
	{
		/* 检查权限 */
		$this->admin_priv('delivery_view', ecjia::MSGTYPE_JSON);
		$db_delivery = RC_Loader::load_app_model('delivery_viewmodel','orders');
		
		/* 对编辑红包类型名称进行权限检查  BY：JI  START */
		if (!empty($_SESSION['ru_id'])) {
			$this->showmessage(__('入驻商家没有操作权限，请登陆商家后台操作！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		/* 对编辑红包类型名称进行权限检查  BY：JI END */

		/* 定义当前时间 */
		define('GMTIME_UTC', RC_Time::gmtime()); // 获取 UTC 时间戳
		/* 取得参数 */
		$delivery				= array();
		$order_id				= intval(trim($_POST['order_id']));			// 订单id
		$delivery_id			= intval(trim($_POST['delivery_id']));		// 发货单id
		$delivery['invoice_no']	= isset($_POST['invoice_no']) ? trim($_POST['invoice_no']) : '';
		$action_note			= isset($_POST['action_note']) ? trim($_POST['action_note']) : '';
	
		/* 根据发货单id查询发货单信息 */
		if (!empty($delivery_id)) {
			$delivery_order = delivery_order_info($delivery_id);
		} else {
			$this->showmessage( __('无法找到对应发货单！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		if (empty($delivery_order)) {
			$this->showmessage( __('无法找到对应发货单！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		/* 查询订单信息 */
		$order = order_info($order_id);
	
		/* 检查此单发货商品库存缺货情况 */
		$virtual_goods			= array();
		$delivery_stock_result	= $db_delivery->join(array('goods', 'products'))->where(array('dg.delivery_id' => $delivery_id))->group(array('dg.product_id', 'dg.goods_id'))->select();
		
		/* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
		if(!empty($delivery_stock_result)) {
			foreach ($delivery_stock_result as $value) {
				if (($value['sums'] > $value['storage'] || $value['storage'] <= 0) && 
					((ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) || 
					(ecjia::config('use_storage') == '0' && $value['is_real'] == 0))) {
					
					/* 操作失败 */
					$links[] = array('text' => RC_Lang::lang('order_info'), 'href' => RC_Uri::url('orders/admin_order_delivery/delivery_info', 'delivery_id=' . $delivery_id));
					$this->showmessage('['.$value['goods_name'].']'.RC_Lang::lang('act_good_vacancy'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('links' => $links));
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
					/* 操作失败 */
					$links[] = array('text' => RC_Lang::lang('order_info'), 'href' => RC_Uri::url('orders/order_delilvery/delivery_info', 'delivery_id=' . $delivery_id));
					$this->showmessage('['.$value['goods_name'].']'.RC_Lang::lang('act_good_vacancy'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('links' => $links));
					break;
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
				virtual_card_shipping($virtual_value,$order['order_sn'], $msg, 'split');
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
						$this->db_products->where(array('product_id' => $value['product_id']))->update($data);
					} else {
						$data = array(
								'goods_number' => $value['storage'] - $value['sums'],
						);
						$this->db_goods->where(array('goods_id' => $value['goods_id']))->update($data);
					}
				}
			}
		}
	
		/* 修改发货单信息 */
		$invoice_no = str_replace(',', '<br>', $delivery['invoice_no']);
		$invoice_no = trim($invoice_no, '<br>');
		$_delivery['invoice_no']	= $invoice_no;
		$_delivery['status']		= 0;	/* 0，为已发货 */
		$result = $this->db_delivery_order->where(array('delivery_id' => $delivery_id))-> update($_delivery);
		if ($result) {
			$data = array(
					'order_status' => '已发货',
					'message'       => '订单号为'.$order['order_sn'].'的商品已发货，请您耐心等待。',
					'order_id'       => $order_id,
					'add_time'       => RC_Time::gmtime(),
			);
			$this->db_order_status_log->insert($data);
		}
		
		if (!$result) {
			/* 操作失败 */
			$links[] = array('text' => RC_Lang::lang('delivery_sn') . RC_Lang::lang('detail'), 'href' => RC_Uri::url('orders/admin_order_delivery/delivery_info','delivery_id=' . $delivery_id));
			$this->showmessage(RC_Lang::lang('act_false'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('links' => $links));
		}
	
		/* 标记订单为已确认 “已发货” */
		/* 更新发货时间 */
		$order_finish				= get_all_delivery_finish($order_id);
		$shipping_status			= ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
		$arr['shipping_status']		= $shipping_status;
		$arr['shipping_time']		= GMTIME_UTC; // 发货时间
		$arr['invoice_no']			= trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');
		update_order($order_id, $arr);
	
		/* 发货单发货记录log */
		order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, null, 1);
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
						'change_desc'	=> sprintf(RC_Lang::lang('order_gift_integral'), $order['order_sn'])
				);
				RC_Api::api('user', 'account_change_log',$options);
				/* 发放红包 */
				send_order_bonus($order_id);
			}
	
			/* 发送邮件 */
			$cfg = ecjia::config('send_ship_email');
			if ($cfg == '1') {
				$order['invoice_no'] = $invoice_no;
				//$tpl = get_mail_template('deliver_notice');
				$tpl_name = 'deliver_notice';
				$tpl   = RC_Api::api('mail', 'mail_template', $tpl_name);
				
				$this->assign('order'			, $order);
				$this->assign('send_time'		, RC_Time::local_date(ecjia::config('time_format')));
				$this->assign('shop_name'		, ecjia::config('shop_name'));
				$this->assign('send_date'		, RC_Time::local_date(ecjia::config('date_format')));
				$this->assign('confirm_url'		, SITE_URL . 'receive.php?id=' . $order['order_id'] . '&con=' . rawurlencode($order['consignee']));	
				$this->assign('send_msg_url'	, SITE_URL . RC_Uri::url('user/admin/message_list','order_id=' . $order['order_id']));

				$content = $this->fetch_string($tpl['template_content']);

				if (!RC_Mail::send_mail($order['consignee'], $order['email'] , $tpl['template_subject'], $content, $tpl['is_html'])) {
					$this->showmessage(RC_Lang::lang('send_mail_fail'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
				}
			}
			$result = ecjia_app::validate_application('sms');
			if (!is_ecjia_error($result)) {	
				/* 如果需要，发短信 */
				if (ecjia::config('sms_order_shipped') == '1' && $order['mobile'] != '') {
					//发送短信
					$tpl_name = 'order_shipped_sms';
					$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
					if (!empty($tpl)) {
						$this->assign('order_sn', $order['order_sn']);
						$this->assign('shipped_time', RC_Time::local_date(RC_Lang::lang('sms_time_format')));
						$this->assign('mobile', $order['mobile']);
						
						$content = $this->fetch_string($tpl['template_content']);
						
						$options = array(
								'mobile' 		=> $order['mobile'],
								'msg'			=> $content,
								'template_id' 	=> $tpl['template_id'],
						);
						$response = RC_Api::api('sms', 'sms_send', $options);
					}
				}
			}
// 				if (ecjia::config('sms_order_shipped') == '1' && $order['mobile'] != '') {
// 					RC_Loader::load_sys_class('sms',null,false);
// 					$sms = new sms();
// 					$sms->send($order['mobile'], sprintf(RC_Lang::lang('order_shipped_sms'), $order['order_sn'],
// 					RC_Time::local_date(RC_Lang::lang('sms_time_format')), RC_Lang::lang('shop_name')), 0);
// 				}
		}
		
		/* 操作成功 */
		$links[] = array('text' => RC_Lang::lang('09_delivery_order'), 'href' => RC_Uri::url('orders/admin_order_delivery/init'));
		$links[] = array('text' => RC_Lang::lang('delivery_sn') . RC_Lang::lang('detail'), 'href' => RC_Uri::url('orders/admin_order_delivery/delivery_info', 'delivery_id=' . $delivery_id));
		$this->showmessage(RC_Lang::lang('act_ok'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('links' => $links));
		
// 				log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf(RC_Lang::lang('order_gift_integral'), $order['order_sn']));
	}
	
	/**
	 * 发货单取消发货
	 */
	public function delivery_cancel_ship() 
	{
		/* 检查权限 */
		$this->admin_priv('delivery_view', ecjia::MSGTYPE_JSON);
		
		if (!empty($_SESSION['ru_id'])) {
			$this->showmessage(__('入驻商家没有操作权限，请登陆商家后台操作！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}

		/* 取得参数 */
		$delivery				= '';
		$order_id				= intval(trim($_POST['order_id']));			// 订单id
		$delivery_id			= intval(trim($_POST['delivery_id']));		// 发货单id
		$delivery['invoice_no']	= isset($_POST['invoice_no'])	? trim($_POST['invoice_no']) : '';
		$action_note			= isset($_POST['action_note'])	? trim($_POST['action_note']) : '';
	
		/* 根据发货单id查询发货单信息 */
		if (!empty($delivery_id)) {
			$delivery_order = delivery_order_info($delivery_id);
		} else {
			$this->showmessage( __('无法找到对应发货单！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
//			die('order does not exist');
		}
		if (empty($delivery_order)) {
			$this->showmessage( __('无法找到对应发货单！') , ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		/* 查询订单信息 */
		$order = order_info($order_id);
	
		/* 取消当前发货单物流单号 */
		$_delivery['invoice_no']	= '';
		$_delivery['status']		= 2;
		$result = $this->db_delivery_order->where(array('delivery_id' => $delivery_id))->update($_delivery);
		if (!$result) {
			/* 操作失败 */
			$links[] = array('text' => RC_Lang::lang('delivery_sn') . RC_Lang::lang('detail'), 'href' => RC_Uri::url('orders/admin_order_delivery/delivery_info', 'delivery_id=' . $delivery_id));
			$this->showmessage(RC_Lang::lang('act_false'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	
		/* 修改定单发货单号 */
		$invoice_no_order		= explode('<br>', $order['invoice_no']);
		$invoice_no_delivery	= explode('<br>', $delivery_order['invoice_no']);
		foreach ($invoice_no_order as $key => $value) {
			$delivery_key = array_search($value, $invoice_no_delivery);
			if ($delivery_key !== false) {
				unset($invoice_no_order[$key], $invoice_no_delivery[$delivery_key]);
				if (count($invoice_no_delivery) == 0) {
					break;
				}
			}
		}
		$_order['invoice_no'] = implode('<br>', $invoice_no_order);
	
		/* 更新配送状态 */
		$order_finish				= get_all_delivery_finish($order_id);
		$shipping_status			= ($order_finish == -1) ? SS_SHIPPED_PART : SS_SHIPPED_ING;
		$arr['shipping_status']		= $shipping_status;
		if ($shipping_status == SS_SHIPPED_ING) {
			$arr['shipping_time']	= ''; // 发货时间
		}
		$arr['invoice_no']			= $_order['invoice_no'];
		update_order($order_id, $arr);
	
		/* 发货单取消发货记录log */
		order_action($order['order_sn'], $order['order_status'], $shipping_status, $order['pay_status'], $action_note, null, 1);
		ecjia_admin::admin_log('取消发货，订单号是'.$order['order_sn'], 'setup', 'order');
		
		/* 如果使用库存，则增加库存 */
		if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_SHIP) {
			// 检查此单发货商品数量
			$virtual_goods = array();
			$delivery_stock_result = $this->db_delivery->field('goods_id, product_id, is_real, SUM(send_number)|sums')->where(array('delivery_id' => $delivery_id))->group('goods_id')->select();
				
			foreach ($delivery_stock_result as $key => $value) {
				/* 虚拟商品 */
				if ($value['is_real'] == 0) {
					continue;
				}
	
				//（货品）
				if (!empty($value['product_id'])) {
					$data = array(
							'product_number' => product_number + $value['sums'],
					);
					$this->db_products->where(array('product_id' => $value['product_id']))->update($data);
				} else {
					$data = array(
							'goods_number' => goods_number + $value['sums'],
					);
					$this->db_goods->where(array('goods_id' => $value['goods_id']))->update($data);
				}
			}
		}
	
		/* 发货单全退回时，退回其它 */
		if ($order['order_status'] == SS_SHIPPED_ING) {
			/* 如果订单用户不为空，计算积分，并退回 */
			if ($order['user_id'] > 0) {
				/* 取得用户信息 */
				$user = user_info($order['user_id']);
				/* 计算并退回积分 */
				$integral = integral_to_give($order);
				$options = array(
						'user_id'		=> $order['user_id'],
						'rank_points'	=> (-1) * intval($integral['rank_points']),
						'pay_points'	=> (-1) * intval($integral['custom_points']),
						'change_desc'	=> sprintf(RC_Lang::lang('return_order_gift_integral'), $order['order_sn'])
				);
				RC_Api::api('user', 'account_change_log',$options);
				/* todo 计算并退回红包 */
				return_order_bonus($order_id);
			}
		}
	
		/* 操作成功 */
		$links[] = array('text' => RC_Lang::lang('delivery_sn') . RC_Lang::lang('detail'), 'href' => RC_Uri::url('orders/admin_order_delivery/delivery_info', 'delivery_id=' . $delivery_id));
		$this->showmessage(RC_Lang::lang('act_ok'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS);
// 		log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf(RC_Lang::lang('return_order_gift_integral'), $order['order_sn']));
	}
	
	/* 发货单删除 */
	public function remove() 
	{
		/* 检查权限 */
		$this->admin_priv('order_os_edit', ecjia::MSGTYPE_JSON);
		
		if (!empty($_SESSION['ru_id'])) {
			$this->showmessage(__('入驻商家没有操作权限，请登陆商家后台操作！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}

		$delivery_id = $_REQUEST['delivery_id'];
		if (!is_array($delivery_id)) {
			if (strpos($delivery_id , ',') === false) {
				$delivery_id = array($delivery_id);
			} else {
				$delivery_id = explode(',', $delivery_id);
			}
		}
		
		foreach ($delivery_id as $value_is) {
			$value_is = intval(trim($value_is));
			/* 查询：发货单信息 */
			$delivery_order = delivery_order_info($value_is);

			/* 如果status不是退货 */
			if ($delivery_order['status'] != 1) {
				/* 处理退货 */
				delivery_return_goods($delivery_id, $delivery_order);
			}
	
			/* 如果status是已发货并且发货单号不为空 */
			if ($delivery_order['status'] == 0 && $delivery_order['invoice_no'] != '') {
				/* 更新：删除订单中的发货单号 */
				del_order_invoice_no($delivery_order['order_id'], $delivery_order['invoice_no']);
			}
		}
		/* 更新：删除发货单 */
		$this->db_delivery_order->in($delivery_id)->delete();
		
		/* 记录日志 */
		ecjia_admin::admin_log($_REQUEST['delivery_id'], 'remove', 'delivery_order');

		$this->showmessage(RC_Lang::lang('tips_delivery_del'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('orders/admin_order_delivery/init')));
	}
	
	/*收货人信息*/
	public function consignee_info()
	{
		$this->admin_priv('delivery_view', ecjia::MSGTYPE_JSON);
		$id = $_GET['delivery_id'];
		if (!empty($id)) {
			$field = "order_id,consignee,address,country,province,city,district,sign_building,email,zipcode,tel,mobile,best_time";
			$row = $this->db_delivery_order->field($field)->where(array('delivery_id'=>$id))->find();
			if (!empty($row)) {
				$field = array("concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''),'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region");
				$region = $this->db_order_region->field($field)->find('o.order_id = "'.$row['order_id'].'"');
				
				$row['region'] = $region['region'];
			} else {
				$this->showmessage(__('无法找到响应的发货单收货人！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} else {
			$this->showmessage(__('操作有误！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		die(json_encode($row));
	}
}

// end