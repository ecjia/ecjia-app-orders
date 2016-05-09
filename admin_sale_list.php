<?php
/**
 * 销售明细列表程序
*/

defined('IN_ECJIA') or exit('No permission resources.');

class admin_sale_list extends ecjia_admin {
	private $order_goods_view;
	private $order_info_view;
	public function __construct() {
		parent::__construct();
		RC_Loader::load_app_func('global','orders');
		RC_Lang::load('statistic');
		$this->order_info_view = RC_Loader::load_app_model('order_info_viewmodel','orders');
		$this->order_goods_view = RC_Loader::load_app_model('order_goods_viewmodel','orders');
		/* 加载所有全局 js/css */
		RC_Script::enqueue_script('bootstrap-placeholder');
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('chosen');
		RC_Script::enqueue_script('jquery-uniform');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('bootstrap-editable-script',RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/js/bootstrap-editable.min.js'));
        RC_Style::enqueue_style('bootstrap-editable-css',RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/css/bootstrap-editable.css'));
		/*自定义js*/
		RC_Script::enqueue_script('sale_list',RC_App::apps_url('statics/js/sale_list.js',__FILE__));
		//时间控件
		RC_Style::enqueue_style('datepicker',RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
        RC_Script::enqueue_script('bootstrap-datepicker',RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
	}
	
	/**
	 * 销售明细
	 */
	public function init() {
		/* 权限判断 */ 
		$this->admin_priv('sale_list_stats');
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('销售明细')));
		ecjia_screen::get_current_screen()->add_help_tab( array(
		'id'		=> 'overview',
		'title'		=> __('概述'),
		'content'	=>
		'<p>' . __('欢迎访问ECJia智能后台销售明细页面，系统中所有的销售明细信息都会显示在此列表中。') . '</p>'
		) );
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
		'<p><strong>' . __('更多信息:') . '</strong></p>' .
		'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:销售明细" target="_blank">关于销售明细帮助文档</a>') . '</p>'
		);
		
		$this->assign('ur_here',RC_Lang::lang('sale_list'));
		$this->assign('action_link', array('text' => '销售排行报表下载', 'href' => RC_Uri::url('orders/admin_sale_list/download')));
		
		/* 时间参数 */
		$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'),strtotime('-1 month')-8*3600);
		$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'));
		
		$sale_list_data = $this->get_sale_list();
		/* 赋值到模板 */
		$this->assign('sale_list_data',$sale_list_data);
		
		$this->assign('start_date',$start_date);
		$this->assign('end_date',$end_date);
		
		$this->assign('search_action',RC_Uri::url('orders/admin_sale_list/init'));
		
		$this->assign_lang();
		$this->display('sale_list.dwt');
	}

	/**
	 * 下载销售明细
	 */
	public function download() {
		/* 检查权限 */
		$this->admin_priv('sale_list_stats');
		/* 时间参数 */
		$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'),RC_Time::local_strtotime('-7 days'));
		$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'),RC_Time::local_strtotime('today'));

		/*文件名*/
		$file_name = mb_convert_encoding(RC_Lang::lang('sales_list_statement'),"GBK","UTF-8");
		$goods_sales_list = $this->get_sale_list(false);
		/*强制下载,下载类型EXCEL*/
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$file_name.xls");
		
		$data = RC_Lang::lang('goods_name')."\t".RC_Lang::lang('order_sn')."\t".RC_Lang::lang('amount')."\t".RC_Lang::lang('sell_price')."\t".RC_Lang::lang('sell_date')."\n";
		
		foreach ($goods_sales_list as $row) {
			foreach ($row as $v) {
				$data .= mb_convert_encoding("$v[goods_name]\t$v[order_sn]\t$v[goods_num]\t$v[sales_price]\t$v[sales_time]\n",'UTF-8','auto');
			}
		}
		echo mb_convert_encoding($data."\t","GBK","UTF-8");
		exit;
	}

	/**
	 * 取得销售明细数据信息
	 * @param   bool  $is_pagination  是否分页
	 * @return  array   销售明细数据
	 */
	private function get_sale_list($is_pagination = true) {
		/* 时间参数 */
	    $filter['start_date'] = empty($_REQUEST['start_date']) ? RC_Time::local_strtotime('-7 days') : RC_Time::local_strtotime($_REQUEST['start_date']);
	    $filter['end_date'] = empty($_REQUEST['end_date']) ? RC_Time::local_strtotime('today') : RC_Time::local_strtotime($_REQUEST['end_date']);
	    $where = "1" .order_query_sql('finished', 'oi.') ." AND oi.add_time >= '".$filter['start_date']."' AND oi.add_time < '" . ($filter['end_date'] + 86400) . "'";

	    $count = $this->order_goods_view->where($where)->count('og.goods_id');
		$page = new ecjia_page($count,10,5);
	    if ($is_pagination) {
           $limit = $page->limit();
	    }
	    $sale_list_data = $this->order_goods_view->field('og.goods_id, og.goods_sn, og.goods_name, og.goods_number AS goods_num, og.goods_price '.
           'AS sales_price, oi.add_time AS sales_time, oi.order_id, oi.order_sn ')->where($where)->order(array('sales_time'=> 'DESC', 'goods_num'=> 'DESC'))->limit($limit)->select();
	    
	    foreach ($sale_list_data as $key => $item) {
	        $sale_list_data[$key]['sales_price'] = price_format($sale_list_data[$key]['sales_price']);
	        $sale_list_data[$key]['sales_time']  = RC_Time::local_date(ecjia::config('date_format'), $sale_list_data[$key]['sales_time']);
	    }
	    $arr = array('item' => $sale_list_data, 'filter' => $filter, 'desc' => $page->page_desc(), 'page'=>$page->show(5));
	    return $arr;
	}
}
// end