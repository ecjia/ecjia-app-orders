<?php
/**
 * 会员排行
*/
defined('IN_ECJIA') or exit('No permission resources.');

class admin_users_order extends ecjia_admin {
	private $users_view;
	public function __construct() {
		parent::__construct();
		
		/* 加载所有全局 js/css */
		RC_Script::enqueue_script('bootstrap-placeholder');
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('chosen');
		RC_Script::enqueue_script('jquery-uniform');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('bootstrap-editable-script', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/js/bootstrap-editable.min.js'));
		RC_Style::enqueue_style('bootstrap-editable-css', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/css/bootstrap-editable.css'));
		//时间控件
		RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
		RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
		/*自定义js*/
		RC_Script::enqueue_script('user_order', RC_App::apps_url('statics/js/user_order.js', __FILE__));
		
		RC_Lang::load('statistic');
		RC_Loader::load_app_func('global', 'orders');
		$this->users_view = RC_Loader::load_app_model('users_viewmodel', 'orders');
	}
	
	/**
	 *	会员排行列表
	 */
	public function init() {
		/* 权限判断 */ 
		$this->admin_priv('users_order_stats');
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('会员排行')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> __('概述'),
			'content'	=>
			'<p>' . __('欢迎访问ECJia智能后台会员排行页面，系统中所有的会员排行信息都会显示在此列表中。') . '</p>'
		));
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . __('更多信息:') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:会员排行" target="_blank">关于会员排行帮助文档</a>') . '</p>'
		);
		
		$this->assign('ur_here', '会员排行');
		$this->assign('action_link', array('text' => '下载购物金额报表', 'href' => RC_Uri::url('orders/admin_users_order/download')));
		
		/* 时间参数 */
		$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'),strtotime('-7 days')-8*3600);
		$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'));
		
		$filter['start_date'] = RC_Time::local_strtotime($start_date);
		$filter['end_date'] = RC_Time::local_strtotime($end_date);
		$filter['sort_by'] = empty($_GET['sort_by']) ? 'order_num' : trim($_GET['sort_by']);
		$filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
		
		$users_order_data = $this->get_users_order($filter, true);
		/* 赋值到模板 */
		$this->assign('users_order_data', $users_order_data);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('filter', $filter);
		$this->assign('search_action', RC_Uri::url('orders/admin_users_order/init'));
		$this->assign_lang();
		
		$this->display('users_order.dwt');
	}

	/**
	 * 下载会员排行
	 */
	public function download() {
		/* 检查权限 */
		$this->admin_priv('users_order_stats');
		
		/* 时间参数 */
		$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'),RC_Time::local_strtotime('-7 days'));
		$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'),RC_Time::local_strtotime('today'));
		
		$filter['start_date'] = RC_Time::local_strtotime($start_date);
		$filter['end_date'] = RC_Time::local_strtotime($end_date);
		
		$filter['sort_by'] = empty($_GET['sort_by']) ? 'order_num' : trim($_GET['sort_by']);
		$filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
		
		/*文件名*/
		$file_name = mb_convert_encoding(RC_Lang::lang('users_order_statement'),"GBK","UTF-8");
		$users_order_data = $this->get_users_order($filter, false);
		
		/*强制下载,下载类型EXCEL*/
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$file_name.xls");
		
		$data = RC_Lang::lang('order_by')."\t".RC_Lang::lang('member_name')."\t".RC_Lang::lang('order_amount')."\t".RC_Lang::lang('buy_sum')."\n";
		if (!empty($users_order_data['item'])) {
			foreach ($users_order_data['item'] as $k => $v) {
				$order_by = $k + 1;
				$data .= "$order_by\t$v[user_name]\t$v[order_num]\t$v[turnover]\n";
			}
		}
		echo mb_convert_encoding($data."\t","GBK","UTF-8");
		exit;
	}

	/**
	 * 取得会员排行数据信息
	 * @param   bool  $is_pagination  是否分页
	 * @return  array   销会员排行数据
	 */
	private function get_users_order($filter, $paging = true) {
	    $where = "u.user_id > 0 " .order_query_sql('finished', 'o.');
	    
		if ($filter['start_date']) {
	        $where .= " AND o.add_time >= '" . $filter['start_date'] . "'";
	    }
	    if ($filter['end_date']) {
	        $where .= " AND o.add_time <= '" . $filter['end_date'] . "'";
	    }
	    $count = $this->users_view->where($where)->count('distinct(u.user_id)');

	    $page = new ecjia_page($count, 10, 5);
	    if ($paging) {
	    	$limit = $page->limit();
	    } else {
	    	$limit = '';
	    }
	    /* 计算订单各种费用之和的语句 */
	    if (!empty($limit)) {
	    	$users_order_data = $this->users_view->field('u.user_id, u.user_name, COUNT(*) AS order_num, SUM(o.goods_amount + o.tax + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee) AS turnover ')->where($where)->order(array($filter['sort_by'] => $filter['sort_order']))->group('u.user_id')->limit($limit)->select();
	    } else {
	    	$users_order_data = $this->users_view->field('u.user_id, u.user_name, COUNT(*) AS order_num, SUM(o.goods_amount + o.tax + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee) AS turnover ')->where($where)->order(array($filter['sort_by'] => $filter['sort_order']))->group('u.user_id')->select();
	    }
	    if (!empty($users_order_data)) {
	    	foreach ($users_order_data as $key => $item) {
	    		$users_order_data[$key]['turnover']  = price_format($users_order_data[$key]['turnover']);
	    	}
	    }

	    $arr = array('item' => $users_order_data, 'filter' => $filter, 'desc' => $page->page_desc(), 'page'=>$page->show(5));
	    return $arr;
	}
}
// end