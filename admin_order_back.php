<?php
/**
 * ECJIA 订单-退货单管理
 */

defined('IN_ECJIA') or exit('No permission resources.');

class admin_order_back extends ecjia_admin {

	private $db_back_order;
	private $db_back_goods;
	private $db_order_region;
// 	private $db_admin_user;
	
	public function __construct() {
		parent::__construct();

        RC_Lang::load('order');
		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('common', 'goods');
		assign_adminlog_content();
		
		$this->db_back_order		= RC_Loader::load_app_model('back_order_model');
		$this->db_back_goods		= RC_Loader::load_app_model('back_goods_model');
		$this->db_order_region		= RC_Loader::load_app_model('order_region_viewmodel');
// 		$this->db_admin_user		= RC_Loader::load_model('admin_user_model');
		
		/* 加载所有全局 js/css */
		RC_Script::enqueue_script('jquery-form');

		/* 列表页 js/css */
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('order_back', RC_Uri::home_url('content/apps/orders/statics/js/order_delivery.js'));
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('chosen');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('jquery-uniform');
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('退货单列表'), RC_Uri::url('orders/admin_order_back/init')));
	}
	
	/**
	 * 退货单列表
	 */
	public function init() {
		/* 检查权限 */
		$this->admin_priv('back_view');
		ecjia_screen::get_current_screen()->remove_last_nav_here();
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('退货单列表')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> __('概述'),
			'content'	=>
			'<p>' . __('欢迎访问ECJia智能后台退货单列表页面，系统中有关退货单都会显示在此列表中。') . '</p>'
		));
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . __('更多信息:') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:退货单列表" target="_blank">关于退货单列表帮助文档</a>') . '</p>'
		);
		
		/* 查询 */
		RC_Loader::load_app_func('function');
		$result = get_back_list();

		/* 模板赋值 */
		$this->assign('ur_here', RC_Lang::lang('10_back_order'));
		$this->assign('os_unconfirmed', OS_UNCONFIRMED);
		$this->assign('cs_await_pay', CS_AWAIT_PAY);
		$this->assign('cs_await_ship', CS_AWAIT_SHIP);
		$this->assign('back_list', $result);
		$this->assign('filter', $result['filter']);
		$this->assign('form_action', RC_Uri::url('orders/admin_order_back/init'));
		$this->assign('del_action', RC_Uri::url('orders/admin_order_back/remove'));
		
		$this->assign_lang();
		$this->display('back_list.dwt');
	}
	
	/**
	 * 退货单详细
	 */
	public function back_info() {
		/* 检查权限 */
		$this->admin_priv('back_view');

		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('退货单操作：查看')));
		
		$back_id = intval(trim($_GET['back_id']));
	
		/* 根据发货单id查询发货单信息 */
		if (!empty($back_id)) {
			RC_Loader::load_app_func('function');
			$back_order = back_order_info($back_id);
		} else {
			$this->showmessage(__('无法找到对应退货单！') , ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR) ;
		}
		if (empty($back_order)) {
			$this->showmessage(__('无法找到对应退货单！') , ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR) ;
		}

		/* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
//		TODO:因未有相关app，办事处暂注释
//		$agency_id = $this->db_admin_user->get_admin_agency_id($_SESSION['admin_id']);
//		if ($agency_id > 0) {
//			if ($back_order['agency_id'] != $agency_id) {
//				$this->showmessage(RC_Lang::lang('priv_error'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR) ;
//			}
//	
//			/* 取当前办事处信息*/
//			$back_order['agency_name'] = $this->db_agency->get_field('agency_name')->find(array('agency_id' => $agency_id));
//		}
	
		/* 取得用户名 */
		if ($back_order['user_id'] > 0) {
			$user = user_info($back_order['user_id']);
			if (!empty($user)) {
				$back_order['user_name'] = $user['user_name'];
			}
		}
	
		/* 取得区域名 */
		$field = array("concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''),'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region");
		$region = $this->db_order_region->field($field)->find('o.order_id = "'.$back_order['order_id'].'"');
		$back_order['region'] = $region['region'] ;
	
		/* 是否保价 */
		$order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;
	
		/* 取得发货单商品 */
		$goods_list = $this->db_back_goods->where(array('back_id' => $back_order['back_id']))->select();
	
		/* 是否存在实体商品 */
		$exist_real_goods = 0;
		if ($goods_list) {
			foreach ($goods_list as $value) {
				if ($value['is_real']) {
					$exist_real_goods++;
				}
			}
		}
	
		/* 模板赋值 */
		$this->assign('back_order', $back_order);
		$this->assign('exist_real_goods', $exist_real_goods);
		$this->assign('goods_list', $goods_list);
		$this->assign('back_id', $back_id); // 发货单id
	
		/* 显示模板 */
		$this->assign('ur_here', RC_Lang::lang('back_operate') . RC_Lang::lang('detail'));
		$this->assign('action_link', array('href' => RC_Uri::url('orders/admin_order_back/init'), 'text' => RC_Lang::lang('10_back_order')));
		
		$this->assign_lang();
		$this->display('back_info.dwt');
	}
	
	
	/* 退货单删除 */
	public function remove() {
		/* 检查权限 */
		$this->admin_priv('order_os_edit', ecjia::MSGTYPE_JSON);
		if(empty($_SESSION['ru_id'])) {
			$back_id = $_REQUEST['back_id'];
			/* 删除退货单 */
			$this->db_back_order->in($back_id)->delete();
		}

		/* 记录日志 */
		ecjia_admin::admin_log($back_id, 'remove', 'back_order');

		$this->showmessage(RC_Lang::lang('tips_back_del'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('orders/admin_order_back/init')));
	}
	
	/*收货人信息*/
	public function consignee_info(){
		$this->admin_priv('back_view' , ecjia::MSGTYPE_JSON);
		$id = $_GET['back_id'];
		if (!empty($id)) {
			$field = "order_id,consignee,address,country,province,city,district,sign_building,email,zipcode,tel,mobile,best_time";
			$row = $this->db_back_order->field($field)->where(array('back_id'=>$id))->find();
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