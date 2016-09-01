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
		RC_Loader::load_app_func('global', 'orders');

		$this->order_info_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
		$this->order_goods_view = RC_Loader::load_app_model('order_goods_viewmodel', 'orders');
		
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
		RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
        RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
        
        RC_Script::enqueue_script('sale_list', RC_App::apps_url('statics/js/sale_list.js', __FILE__));
        RC_Script::localize_script('sale_list', 'js_lang', RC_Lang::get('orders::statistic.js_lang'));
	}
	
	/**
	 * 销售明细
	 */
	public function init() {
		/* 权限判断 */ 
		$this->admin_priv('sale_list_stats');
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.sales_list')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> RC_Lang::get('orders::statistic.overview'),
			'content'	=> '<p>' . RC_Lang::get('orders::statistic.sale_list_help') . '</p>'
		));
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:销售明细" target="_blank">'. RC_Lang::get('orders::statistic.about_sale_list') .'</a>') . '</p>'
		);
		
		$this->assign('ur_here', RC_Lang::get('orders::statistic.sales_list'));
		$this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.download_sale_sort'), 'href' => RC_Uri::url('orders/admin_sale_list/download')));
		
		/* 时间参数 */
		$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'), strtotime('-1 month')-8*3600);
		$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'));
		
		$sale_list_data = $this->get_sale_list();
		
		$this->assign('sale_list_data', $sale_list_data);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('search_action',RC_Uri::url('orders/admin_sale_list/init'));
		
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
		$file_name = mb_convert_encoding(RC_Lang::get('orders::statistic.sales_list_statement'), "GBK", "UTF-8");
		$goods_sales_list = $this->db_goods_view->get_sale_list(false);
		
		/*强制下载,下载类型EXCEL*/
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$file_name.xls");
		
		$data = RC_Lang::get('orders::statistic.goods_name')."\t".RC_Lang::get('orders::statistic.order_sn')."\t".RC_Lang::get('orders::statistic.amount')."\t".RC_Lang::get('orders::statistic.sell_price')."\t".RC_Lang::get('orders::statistic.sell_date')."\n";
		if (!empty($goods_sales_list['item'])) {
			foreach ($goods_sales_list['item'] as $v) {
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
		$limit = null;
		if ($is_pagination) {
			$limit = $page->limit();
		}
	    
	    $sale_list_data = $this->order_goods_view->field('og.goods_id, og.goods_sn, og.goods_name, og.goods_number AS goods_num, og.goods_price '.
           'AS sales_price, oi.add_time AS sales_time, oi.order_id, oi.order_sn ')->where($where)->order(array('sales_time'=> 'DESC', 'goods_num'=> 'DESC'))->limit($limit)->select();
	    
	    if (!empty($sale_list_data)) {
	    	foreach ($sale_list_data as $key => $item) {
	    		$sale_list_data[$key]['sales_price'] = price_format($sale_list_data[$key]['sales_price']);
	    		$sale_list_data[$key]['sales_time']  = RC_Time::local_date(ecjia::config('date_format'), $sale_list_data[$key]['sales_time']);
	    	}
	    }

	    $arr = array('item' => $sale_list_data, 'filter' => $filter, 'desc' => $page->page_desc(), 'page' => $page->show(5));
	    return $arr;
	}
}

// end