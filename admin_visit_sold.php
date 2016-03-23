<?php
/**
 * 访问购买率
*/
defined('IN_ECJIA') or exit('No permission resources.');
RC_Loader::load_sys_class('ecjia_admin', false);
class admin_visit_sold extends ecjia_admin {
	private $db_goods_view;
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
		
		RC_Lang::load('statistic');
		RC_Loader::load_app_func('global','orders');
		$this->db_goods_view = RC_Loader::load_app_model('goods_viewmodel','orders');
		RC_Script::enqueue_script('visit_sold',RC_App::apps_url('statics/js/visit_sold.js',__FILE__));
	}
	
	public function init() {
		$this->admin_priv('visit_sold_stats');
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('访问购买率')));
		ecjia_screen::get_current_screen()->add_help_tab( array(
		'id'		=> 'overview',
		'title'		=> __('概述'),
		'content'	=>
		'<p>' . __('欢迎访问ECJia智能后台访问购买率页面，系统中所有的访问购买率信息都会显示在此列表中。') . '</p>'
		) );
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
		'<p><strong>' . __('更多信息:') . '</strong></p>' .
		'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:访问购买率" target="_blank">关于访问购买率帮助文档</a>') . '</p>'
		);
		
		$this->assign('ur_here',  __('访问购买率'));
		$this->assign('action_link', array('text' => RC_Lang::lang('download_visit_buy'), 'href' => RC_Uri::url('orders/admin_visit_sold/download')));
		
		/* 变量的初始化 */
		$cat_id   = (!empty($_REQUEST['cat_id']))   ? intval($_REQUEST['cat_id'])   : 0;
		$brand_id = (!empty($_REQUEST['brand_id'])) ? intval($_REQUEST['brand_id']) : 0;
		$show_num = (!empty($_REQUEST['show_num'])) ? intval($_REQUEST['show_num']) : 15;
		if ($show_num < 0) {
			$show_num = 15;
		}
		/* 获取访问购买的比例数据 */
		$click_sold_info = $this->click_sold_info($cat_id, $brand_id, $show_num);
		
		/* 赋值到模板 */
		$this->assign('cat_id', $cat_id);
		$this->assign('show_num', $show_num);
		$this->assign('brand_id', $brand_id);
		$this->assign('click_sold_info', $click_sold_info);
		$this->assign('cat_list', cat_list(0, $cat_id));
		$this->assign('brand_list', get_brand_list());
		
		/* 显示页面 */
		$this->assign_lang();
		$this->display('visit_sold.dwt');
	}
	
	public function download() {
		$this->admin_priv('visit_sold_stats');
		/* 变量的初始化 */
		$cat_id   = !empty($_GET['cat_id'])  ? intval($_GET['cat_id'])   : 0;
		$brand_id = !empty($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
		$show_num = !empty($_GET['show_num']) ? intval($_GET['show_num']) : 15;
		/* 获取访问购买的比例数据 */
		$click_sold_info = $this->click_sold_info($cat_id, $brand_id, $show_num);
		
		$filename = mb_convert_encoding(RC_Lang::lang('visit_buy_statement'),"GBK","UTF-8");
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$filename.xls");

		$data = RC_Lang::lang('order_by')."\t".RC_Lang::lang('goods_name')."\t".RC_Lang::lang('fav_exponential')."\t".RC_Lang::lang('buy_times')."\t".RC_Lang::lang('visit_buy')."\n";
		foreach ($click_sold_info['item'] as $k=>$v) {
			$order_by = $k + 1;
			$data .= "$order_by\t$v[goods_name]\t$v[click_count]\t$v[sold_times]\t$v[scale]\n";
		}
		echo mb_convert_encoding($data."\t","GBK","UTF-8");
		exit;
	}
	
	/*------------------------------------------------------ */
	//--订单统计需要的函数
	/*------------------------------------------------------ */
	/**
	 * 取得访问和购买次数统计数据
	 *
	 * @param   int             $cat_id          分类编号
	 * @param   int             $brand_id        品牌编号
	 * @param   int             $show_num        显示个数
	 * @return  array           $click_sold_info  访问购买比例数据
	 */
	private function click_sold_info($cat_id, $brand_id, $show_num) {
		RC_Loader::load_sys_class('ecjia_page',false);
		
		$where = "og.goods_id" . order_query_sql('finished', 'o.');
	
		if ($cat_id > 0)
		{
			$where .= "AND " . get_children($cat_id);
		}
		if ($brand_id > 0)
		{
			$where .= "AND g.brand_id = $brand_id";
		}
		$click_sold_info = array();
		
		$count = $this->db_goods_view->where($where)->count('g.goods_name');
		
		$page = new ecjia_page($count, !empty($show_num) ? $show_num : 5, 5);
	
		$limit = $page->limit();
		$data = $this->db_goods_view->field('og.goods_id,g.goods_sn,g.goods_name,g.click_count,count(og.goods_id) AS sold_times')->where($where)->group('og.goods_id')->order(array('g.click_count'=>'DESC'))->limit($limit)->select();
		if (!empty($data)) {
			foreach ($data as $item) {
				if ($item['click_count'] <= 0) {
					$item['scale'] = 0;
				} else {
					/* 每一百个点击的订单比率 */
					$item['scale'] = sprintf("%0.2f", ($item['sold_times'] / $item['click_count']) * 100) .'%';
				}
	
				$click_sold_info[] = $item;
			}
		}
		$arr = array('item' => $click_sold_info);
		return $arr;
	}
}
// end