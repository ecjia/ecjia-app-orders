<?php

/**
 * 广告转化率的统计程序
*/

defined('IN_ECJIA') or exit('No permission resources.');
RC_Loader::load_sys_class('ecjia_admin', false);

class admin_adsense extends ecjia_admin {
	private $db_adview;
	private $db_order_info;
	private $db_adsense;
	public function __construct() {
		parent::__construct();
		RC_Loader::load_app_func('global','orders');
		RC_Lang::load('statistic');
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
		$this->db_adview  = RC_Loader::load_app_model('ad_viewmodel','orders');
		$this->db_order_info  = RC_Loader::load_app_model('order_info_model','orders');
		$this->db_adsense  = RC_Loader::load_app_model('adsense_model','orders');
	}
	
	/**
	 * 站外投放广告的统计
	 */
	public function init() {
		$this->admin_priv('adsense_conversion_stats');
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('广告转化率')));
		ecjia_screen::get_current_screen()->add_help_tab( array(
		'id'		=> 'overview',
		'title'		=> __('概述'),
		'content'	=>
		'<p>' . __('欢迎访问ECJia智能后台广告转化率页面，系统中所有的广告转化率都会显示在此列表中。') . '</p>'
		) );
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
		'<p><strong>' . __('更多信息:') . '</strong></p>' .
		'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:广告转化率" target="_blank">关于广告转化率帮助文档</a>') . '</p>'
		);
		
		$this->assign('ur_here',__('广告转化率'));
		$this->assign('action_link', array('href' => RC_Uri::url('adsense/admin/init'), 'text' => '广告列表'));
		$this->assign('action_link_download', array('href' => RC_Uri::url('orders/admin_adsense/download'), 'text' => '下载广告转化率报表'));
		
		$ads_stats = $this->get_ads_stats();
		if (!empty($ads_stats)) {
			$this->assign('ads_stats',$ads_stats);
		}
		
		$goods_stats = $this->get_goods_stats();
		if (!empty($goods_stats)) {
			$this->assign('goods_stats', $goods_stats);
		}
		
		$this->assign_lang();
		$this->display('adsense.dwt');
	}
	
	/**
	 * 广告转化率报表下载
	 */
	public function download() {
		$this->admin_priv('adsense_conversion_stats');
		
		$ads_stats = $this->get_ads_stats();
		$goods_stats = $this->get_goods_stats();
		
		$filename = mb_convert_encoding(RC_Lang::lang('adsense_statement'),"GBK","UTF-8");
		
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$filename.xls");
		$data = RC_Lang::lang('adsense_name')."\t".RC_Lang::lang('cleck_referer')."\t".RC_Lang::lang('click_count')."\t".RC_Lang::lang('confirm_order')."\t".RC_Lang::lang('gen_order_amount')."\n";
		$res = array_merge($ads_stats, $goods_stats);
		foreach ($res AS $row) {
			$data .= "$row[ad_name]\t$row[referer]\t$row[clicks]\t$row[order_confirm]\t$row[order_num]\n";
		}
		echo mb_convert_encoding($data."\t","GBK","UTF-8");
		exit;
	}
	
	
	/*获取广告数据 */
	private function get_ads_stats() {
		$ads_stats = array();
		$res = $this->db_adview->order(array('a.ad_name'=>'DESC'))->select();
		if (!empty($res)) {
			foreach ($res as $rows) {
				/*获取当前广告所产生的订单总数 */
				$rows['referer']=addslashes($rows['referer']);
		
				$rows['order_num'] = $this->db_order_info->where('from_ad = '.$rows[ad_id].' AND referer = "'.$rows[referer].'"')->count('order_id');
					
				/*当前广告所产生的已完成的有效订单 */
				$rows['order_confirm'] = $this->db_order_info->where('from_ad = '.$rows[ad_id].' AND referer = "'.$rows[referer]. order_query_sql('finished').'"')->count('order_id');
		
				$ads_stats[] = $rows;
			}
		}
		return $ads_stats;
	}
	
	/*广告转化率商品的统计数据 */
	private function get_goods_stats() {
		$goods_stats    = array();
		$goods_res = $this->db_adsense->field('from_ad, referer, clicks')->where('from_ad = "-1"')->order(array('referer' => 'DESC'))->select();
		if (!empty($goods_res)) {
			foreach ($goods_res as $rows2) {
				/*获取当前广告所产生的订单总数 */
				$rows2['referer']=addslashes($rows2['referer']);
		
				$rows2['order_num'] = $this->db_order_info->where('referer = "'.$rows2[referer].'"')->count('order_id');
				/*当前广告所产生的已完成的有效订单 */
					
				$rows2['order_confirm'] = $this->db_order_info->where('referer = "'.$rows2[referer]. order_query_sql('finished').'"')->count('order_id');
		
				$rows2['ad_name']  = RC_Lang::lang('adsense_js_goods');
				$goods_stats[]  = $rows2;
			}
		}
		return $goods_stats;
	}
}

// end