<?php
/**
 * 商品销售排行
*/
defined('IN_ECJIA') or exit('No permission resources.');

class admin_sale_order extends ecjia_admin {
	public function __construct() {
		parent::__construct();
		
		RC_Loader::load_app_func('global', 'orders');
		
		/* 加载所有全局 js/css */
		RC_Script::enqueue_script('bootstrap-placeholder');
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('chosen');
		RC_Script::enqueue_script('jquery-uniform');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('bootstrap-editable-script', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/js/bootstrap-editable.min.js'),array(), false, true);
		RC_Style::enqueue_style('bootstrap-editable-css', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/css/bootstrap-editable.css'));
		RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
		RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
		
		RC_Script::enqueue_script('sale_order', RC_App::apps_url('statics/js/sale_order.js',__FILE__));
	}
	
	public function init() {
		/* 权限检查 */ 
		$this->admin_priv('sale_order_stats', ecjia::MSGTYPE_JSON);
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('system::system.sell_stats')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> RC_Lang::get('orders::statistic.overview'),
			'content'	=> '<p>' . RC_Lang::get('orders::statistic.sale_order_help') . '</p>'
		));
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:销售排行" target="_blank">'. RC_Lang::get('orders::statistic.about_sale_order') .'</a>') . '</p>'
		);
		
		/* 赋值到模板 */
		$this->assign('ur_here', RC_Lang::get('system::system.sell_stats'));
		$this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.download_sale_sort'), 'href' => RC_Uri::url('orders/admin_sale_order/download')));
		
		/*时间参数*/
		$start_date = !empty($_GET['start_date']) 	? $_GET['start_date'] 	: RC_Time::local_date(ecjia::config('date_format'), strtotime('-1 month')-8*3600);
		$end_date 	= !empty($_GET['end_date']) 	? $_GET['end_date'] 	: RC_Time::local_date(ecjia::config('date_format'));
		
		$filter['start_date'] 	= RC_Time::local_strtotime($start_date);
		$filter['end_date'] 	= RC_Time::local_strtotime($end_date);
		
		$filter['sort_by'] 		= empty($_REQUEST['sort_by']) 		? 'goods_num' 	: trim($_REQUEST['sort_by']);
	    $filter['sort_order'] 	= empty($_REQUEST['sort_order']) 	? 'DESC' 		: trim($_REQUEST['sort_order']);
		
		$goods_order_data = $this->get_sales_order(true, $filter);
		
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('filter', $filter);
		
		$this->assign('goods_order_data', $goods_order_data);
		$this->assign('search_action', RC_Uri::url('orders/admin_sale_order/init'));
		
		$this->display('sale_order.dwt');
	}
	
	/**
	 * 商品销售排行报表下载
	 */
	public function download() {
		/* 检查权限 */
		$this->admin_priv('sale_order_stats', ecjia::MSGTYPE_JSON);
		/*时间参数*/
		$start_date = !empty($_GET['start_date']) 	? $_GET['start_date'] 	: RC_Time::local_date(ecjia::config('date_format'), strtotime('-1 month')-8*3600);
		$end_date   = !empty($_GET['end_date']) 	? $_GET['end_date'] 	: RC_Time::local_date(ecjia::config('date_format'), strtotime('today')-8*3600);
		
		$filter['start_date'] 	= RC_Time::local_strtotime($start_date);
		$filter['end_date'] 	= RC_Time::local_strtotime($end_date);
		$filter['sort_by'] 		= empty($_REQUEST['sort_by']) ? 'goods_num' : trim($_REQUEST['sort_by']);
	    $filter['sort_order'] 	= empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		
		$goods_order_data = $this->get_sales_order(false, $filter);
		$filename = mb_convert_encoding(RC_Lang::get('orders::statistic.sale_order_statement'), "GBK", "UTF-8");
		
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$filename.xls");
		
		$data = RC_Lang::get('orders::statistic.order_by')."\t".RC_Lang::get('orders::statistic.goods_name')."\t".RC_Lang::get('orders::statistic.goods_sn')."\t".RC_Lang::get('orders::statistic.sell_amount')."\t".RC_Lang::get('orders::statistic.sell_sum')."\t".RC_Lang::get('orders::statistic.percent_count')."\n";
		
		if (!empty($goods_order_data['item'])) {
			foreach ($goods_order_data['item'] as $k => $v) {
				$order_by = $k + 1;
				$data .= "$order_by\t$v[goods_name]\t$v[goods_sn]\t$v[goods_num]\t$v[turnover]\t$v[wvera_price]\n";
			}
		}
		
		echo mb_convert_encoding($data."\t","GBK","UTF-8");
		exit;
	}
	
	/**
	 * 取得销售排行数据信息
	 * @param   bool  $is_pagination  是否分页
	 * @return  array   销售排行数据
	 */
	private function get_sales_order($is_pagination, $filter) {
	    $where = '1' . order_query_sql('finished', 'oi.');
	    if ($filter['start_date']) {
	    	$where .= " AND oi.add_time >= '" . $filter['start_date'] . "'";
	    }
        if ($filter['end_date']) {
        	$where .= " AND oi.add_time <= '" . $filter['end_date'] . "'";
        }
	    $db_goods = RC_DB::table('goods as g')
	    	->leftJoin('order_goods as og', RC_DB::raw('og.goods_id'), '=', RC_DB::raw('g.goods_id'))
	    	->leftJoin('order_info as oi', RC_DB::raw('oi.order_id'), '=', RC_DB::raw('og.order_id'))
	    	->whereRaw($where);
	    
	    $count = $db_goods->count(RC_DB::raw('distinct(og.goods_id)'));
		$page = new ecjia_page($count, 15, 5);
		if ($is_pagination) {
			$db_goods->take(15)->skip($page->start_id-1);
		}
	    $sales_order_data = $db_goods->select(RC_DB::raw('og.goods_id, og.goods_sn, og.goods_name, oi.order_status, SUM(og.goods_number) AS goods_num, SUM(og.goods_number * og.goods_price) AS turnover'))
	    	->groupby(RC_DB::raw('og.goods_id'))
	    	->orderby($filter['sort_by'], $filter['sort_order'])
	    	->get();
	    
	    if (!empty($sales_order_data)) {
	    	foreach ($sales_order_data as $key => $item) {
	    		$sales_order_data[$key]['wvera_price'] = price_format($item['goods_num'] ? $item['turnover'] / $item['goods_num'] : 0);
	    		$sales_order_data[$key]['short_name']  = sub_str($item['goods_name'], 30, true);
	    		$sales_order_data[$key]['turnover']    = price_format($item['turnover']);
	    		$sales_order_data[$key]['taxis']       = $key + 1;
	    	}
	    }
	    return array('item' => $sales_order_data, 'filter' => $filter, 'desc' => $page->page_desc(), 'page'=>$page->show(5));
	}
}
// end