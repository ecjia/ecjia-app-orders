<?php

/**
 * ECJIA 订单-退货单管理
 */

defined('IN_ECJIA') or exit('No permission resources.');
//RC_Loader::load_sys_class('ecjia_admin', false);

class mh_back extends ecjia_merchant {

//	private $db_back_order;
//	private $db_back_goods;
//	private $db_order_region;
// 	private $db_admin_user;
//	private $back_order_viewmodel;
	
	public function __construct() {
		parent::__construct();
//		RC_Lang::load('order');
		RC_Loader::load_app_func('order', 'orders');
		RC_Loader::load_app_func('global', 'goods');
//		$this->db_back_order		= RC_Loader::load_app_model('back_order_model');
//		$this->db_back_goods		= RC_Loader::load_app_model('back_goods_model');
//		$this->db_order_region		= RC_Loader::load_app_model('order_region_viewmodel');
// 		$this->db_admin_user		= RC_Loader::load_model('admin_user_model');
//		$this->back_order_viewmodel = RC_Loader::load_app_model('back_order_viewmodel', 'orders');

		/* 加载所有全局 js/css */
		RC_Script::enqueue_script('jquery-form');

		/* 列表页 js/css */
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('order_delivery', RC_App::apps_url('statics/js/merchant_order_delivery.js', __FILE__));
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('chosen');
		RC_Style::enqueue_style('uniform-aristo');
		
		ecjia_merchant_screen::get_current_screen()->set_parentage('order', 'order/mh_back.php');
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('订单管理'), RC_Uri::url('orders/merchant/init')));
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::order.order_back_list'),RC_Uri::url('orders/mh_back/init')));
		
	}
	
	/**
	 * 退货单列表
	 */
	public function init() {
		/* 检查权限 */
		$this->admin_priv('back_view', ecjia::MSGTYPE_JSON);

		ecjia_screen::get_current_screen()->remove_last_nav_here();
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::order.order_back_list')));

		/* 查询 */
		RC_Loader::load_app_func('function');
		$result = get_merchant_back_list();
        
		/* 模板赋值 */
		$this->assign('ur_here'			, RC_Lang::lang('10_back_order'));
//		$this->assign('ur_here'			, RC_Lang::get('orders::orders.back_order_info'));
		$this->assign('form_action', 		RC_Uri::url('order/admin_order_back/product_add_execute'));
//		$this->assign('os_unconfirmed'	, OS_UNCONFIRMED);
//		$this->assign('cs_await_pay'	, CS_AWAIT_PAY);
//		$this->assign('cs_await_ship'	, CS_AWAIT_SHIP);
		$this->assign('back_list'		, $result);
		$this->assign('filter'			, $result['filter']);
		$this->assign('form_action'		, RC_Uri::url('orders/mh_back/init'));
		$this->assign('del_action'		, RC_Uri::url('orders/mh_back/remove'));

		$this->assign_lang();
		$this->display('back_list.dwt');
	}

	/**
	 * 退货单详细
	 */
	public function back_info() {
		/* 检查权限 */
		$this->admin_priv('back_view', ecjia::MSGTYPE_JSON);

		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::order.return_look')));
// 		$seller_id = $_SESSION['seller_id'];
        $store_id = $_SESSION['store_id'];

		$back_id = intval(trim($_GET['back_id']));
//	    $count = $this->back_order_viewmodel->where(array('bo.back_id' => $back_id , 'og.seller_id' => $_SESSION['seller_id']))->count();
		$count = RC_DB::table('back_order as bo')
				->leftJoin('order_goods as og', RC_DB::raw('bo.order_id'), '=', RC_DB::raw('og.order_id'))
				->whereRaw("bo.back_id = $back_id")
//				->whereRaw("og.seller_id = $seller_id")
				->count();
        
//	    if(empty($count)){
//	        return $this->showmessage(__('没有找到相对应的数据'), ecjia_admin::MSGTYPE_HTML, ecjia_admin::MSGSTAT_ERROR);
//	    }
		/* 根据发货单id查询发货单信息 */
		if (!empty($back_id)) {
			RC_Loader::load_app_func('function');
			$back_order = back_order_info($back_id, $store_id);
		} else {
			return $this->showmessage(RC_Lang::get('orders::order.return_form').'！' , ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR) ;
		}
		if (empty($back_order)) {
			return $this->showmessage(RC_Lang::get('orders::order.return_form').'！' , ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR) ;
		}

		/* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
//		TODO:因未有相关app，办事处暂注释
//		$agency_id = $this->db_admin_user->get_admin_agency_id($_SESSION['admin_id']);
//		if ($agency_id > 0) {
//			if ($back_order['agency_id'] != $agency_id) {
//				return $this->showmessage(RC_Lang::lang('priv_error') , ecjia_admin::MSGTYPE_JSON | ecjia_admin::MSGSTAT_ERROR) ;
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
//		$region = $this->db_order_region->field($field)->find('o.order_id = "'.$back_order['order_id'].'"');
//		$region = RC_DB::table('order_info as o')->leftJoin('region as p', 'o.province', '=', 'p.region_id')
//				->selectRaw($field)
//				->where('o.order_id', '.$back_order['order_id'].')
//				->first();
//		$back_order['region'] = $region['region'] ;
	
		/* 是否保价 */
		$order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;
	
		/* 取得发货单商品 */
//		$goods_list = $this->db_back_goods->where(array('back_id' => $back_order['back_id']))->select();
		$goods_list	= RC_DB::table('back_goods')->where('back_id', $back_order['back_id'])->get();
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
		$this->assign('back_order'			, $back_order);
		$this->assign('ur_here'				, RC_Lang::get('orders::order.return_look'));
		
		$this->assign('exist_real_goods'	, exist_real_goods);
		$this->assign('goods_list'			, $goods_list);
		$this->assign('back_id'				, $back_id); // 发货单id
		/* 显示模板 */
		$this->assign('action_link'			, array('href' => RC_Uri::url('orders/mh_back/init'), 'text' => RC_Lang::get('system::system.10_back_order')));
		
		$this->assign_lang();
		$this->display('back_info.dwt');
	}
	
	
	/* 退货单删除 */
	public function remove() {
		/* 检查权限 */
		$this->admin_priv('order_os_edit' , ecjia::MSGTYPE_JSON);
		$back_id = $_REQUEST['back_id'];
		/* 记录日志 */
		ecjia_admin_log::instance()->add_object('order_back', RC_Lang::get('orders::order.back_sn'));
		$type = htmlspecialchars($_GET['type']);
		$back = explode(',',$back_id);
// 		if($type != 'batch'){
// 			foreach($back as $val ){
//				$order_sn = $this->db_back_order->where(array('back_id' =>$val))->get_field('order_sn');
// 				$order_sn = RC_DB::table('back_order')->where('back_id', $val)->pluck('order_sn');
// 				ecjia_merchant::admin_log($order_sn, 'remove', 'order_back');
// 			}
// 		}else{
//			$order_sn = $this->db_back_order->where(array('back_id' =>$back_id))->get_field('order_sn');
// 			$order_sn = RC_DB::table('back_order')->where('back_id', $back_id)->pluck('order_sn');
// 			ecjia_merchant::admin_log($order_sn, 'batch_remove', 'order_back');
// 		}
//		$this->db_back_order->in(array('back_id' => $back_id))->delete();
        
		$db_back_order = RC_DB::table('back_order')->whereIn('back_id', $back);
		$db_back_order_get = $db_back_order->first();

	    if ($db_back_order_get['store_id'] != $_SESSION['store_id']) {
			return $this->showmessage(__('无法找到对应的订单！'), ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR);
		}
		
		$db_back_order->delete();
		return $this->showmessage(RC_Lang::lang('tips_back_del'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS,array('pjaxurl'=>RC_Uri::url('orders/mh_back/init')));
	}

	/*收货人信息*/
	public function consignee_info(){
		$this->admin_priv('back_view' ,ecjia::MSGTYPE_JSON);
		$id = $_GET['back_id'];
//		$count = $this->back_order_viewmodel->where(array('bo.back_id' => $id , 'og.seller_id' => $_SESSION['seller_id']))->count();
//		if(empty($count)){
//		    return $this->showmessage(__('没有找到相对应的数据'), ecjia_admin::MSGTYPE_HTML, ecjia_admin::MSGSTAT_ERROR);
//		}
		if (!empty($id)) {
			$field = array('order_id', 'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building', 'email', 'zipcode', 'tel', 'mobile', 'best_time');
			$row = RC_DB::table('back_order')->select($field)->where('back_id', $id)->first();
			if (!empty($row)) {
				$region = RC_DB::table('order_info as o')
					->leftJoin('region as c', RC_DB::raw('o.country'), '=', RC_DB::raw('c.region_id'))
					->leftJoin('region as p', RC_DB::raw('o.province'), '=', RC_DB::raw('p.region_id'))
					->leftJoin('region as t', RC_DB::raw('o.city'), '=', RC_DB::raw('t.region_id'))
					->leftJoin('region as d', RC_DB::raw('o.district'), '=', RC_DB::raw('d.region_id'))
					->select(RC_DB::raw("concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''),'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region"))
					->where(RC_DB::raw('o.order_id'), $row['order_id'])
					->first();
				$row['region'] = $region['region'];
			} else {
				return $this->showmessage(RC_Lang::get('orders::order.no_invoice').'！', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} else {
			return $this->showmessage(RC_Lang::get('orders::order.a_mistake').'！', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		die(json_encode($row));
	}
}

// end