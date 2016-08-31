<?php
/**
 * 客户统计
*/
defined('IN_ECJIA') or exit('No permission resources.');

class admin_guest_stats extends ecjia_admin {
	private $db_order_info;
	private $db_users;
	public function __construct() {
		parent::__construct();
		RC_Loader::load_app_func('global','orders');
		RC_Lang::load('statistic');
		$this->db_order_info  = RC_Loader::load_app_model('order_info_model', 'orders');
		$this->db_users  = RC_Loader::load_app_model('users_model', 'user');
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
	}
	/**
	 * 客户统计列表
	 */
	public function init() {
		$this->admin_priv('guest_stats');
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('客户统计')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> __('概述'),
			'content'	=>
			'<p>' . __('欢迎访问ECJia智能后台客户统计页面，系统中所有的客户统计信息都会显示在此页面中。') . '</p>'
		));
		
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . __('更多信息:') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:客户统计" target="_blank">关于客户统计帮助文档</a>') . '</p>'
		);
		
		$this->assign('ur_here', __('客户统计'));
		$this->assign('action_link',array('text' => '客户统计报表下载','href'=>	RC_Uri::url('orders/admin_guest_stats/download')));
		
		/* 取得会员总数 */
		$res = $this->db_users->count();
		$user_num = $res;
		
 		/* 计算订单各种费用之和的语句 */
		$total_fee = " SUM(" . order_amount_field() . ") AS turnover ";
		
		/* 有过订单的会员数 */
		$have_order_usernum = $this->db_order_info->where('user_id > 0 ' . order_query_sql('finished'))->count('DISTINCT user_id');
		
		/* 会员订单总数和订单总购物额 */
		$user_all_order = array();
		$user_all_order = $this->db_order_info->field('COUNT(*) AS order_num , '.$total_fee.'')->find('user_id > 0 ' . order_query_sql('finished').'');
		$user_all_order['turnover'] = floatval($user_all_order['turnover']);
		
 		/* 匿名会员订单总数和总购物额 */
		$guest_all_order = array();
		$guest_all_order = $this->db_order_info->field('COUNT(*) AS order_num , '.$total_fee.'')->find('user_id = 0 ' . order_query_sql('finished').'');
 		
 		/* 匿名会员平均订单额: 购物总额/订单数 */
		$guest_order_amount = ($guest_all_order['order_num'] > 0) ? floatval($guest_all_order['turnover'] / $guest_all_order['order_num']) : '0.00';
		
		/* 赋值到模板 */
		$this->assign('user_num',            $user_num);                    // 会员总数
		$this->assign('have_order_usernum',  $have_order_usernum);          // 有过订单的会员数
		$this->assign('user_order_turnover', $user_all_order['order_num']); // 会员总订单数
		$this->assign('user_all_turnover',   price_format($user_all_order['turnover']));  //会员购物总额
		$this->assign('guest_all_turnover',  price_format($guest_all_order['turnover'])); //匿名会员购物总额
		$this->assign('guest_order_num',     $guest_all_order['order_num']);              //匿名会员订单总数
		/* 每会员订单数 */
		$this->assign('ave_user_ordernum', $user_num > 0 ? sprintf("%0.2f", $user_all_order['order_num'] / $user_num) : 0);
		
		/* 每会员购物额 */
		$this->assign('ave_user_turnover', $user_num > 0 ? price_format($user_all_order['turnover'] / $user_num) : 0);
		
		/* 注册会员购买率 */
		$this->assign('user_ratio', sprintf("%0.2f", ($user_num > 0 ? $have_order_usernum / $user_num : 0) * 100));
		
		/* 匿名会员平均订单额 */
		$this->assign('guest_order_amount', $guest_all_order['order_num'] > 0 ? price_format($guest_all_order['turnover'] / $guest_all_order['order_num']) : 0);
		$this->assign('all_order', $user_all_order);    //所有订单总数以及所有购物总额
		
		$this->assign_lang();
		$this->display('guest_stats.dwt');
	}
	
	/**
	 * 客户统计报表下载
	 */
	public function download() {
		/* 权限判断 */ 
		$this->admin_priv('guest_stats');
		
		/* 取得会员总数 */
		$res = $this->db_users->count();
		$user_num   = $res;
		
		/* 计算订单各种费用之和的语句 */
		$total_fee = " SUM(" . order_amount_field() . ") AS turnover ";
		
		/* 有过订单的会员数 */
		$have_order_usernum = $this->db_order_info->where('user_id > 0 ' . order_query_sql('finished'))->count('DISTINCT user_id');
		
		/* 会员订单总数和订单总购物额 */
		$user_all_order = array();
		$user_all_order = $this->db_order_info->field('COUNT(*) AS order_num , '.$total_fee.'')->find('user_id > 0 ' . order_query_sql('finished').'');		
		$user_all_order['turnover'] = floatval($user_all_order['turnover']);
		
		/*匿名会员订单总数和总购物额 */
		$guest_all_order = array();
		$guest_all_order = $this->db_order_info->field('COUNT(*) AS order_num , '.$total_fee.'')->find('user_id = 0 ' . order_query_sql('finished').'');
		
		/* 匿名会员平均订单额: 购物总额/订单数 */
		$guest_order_amount = ($guest_all_order['order_num'] > 0) ? floatval($guest_all_order['turnover'] / $guest_all_order['order_num']) : '0.00';
		
		$filename = mb_convert_encoding(RC_Lang::lang('guest_statement'),"GBK","UTF-8");
		header("Content-type: application/vnd.ms-excel;charset=utf-8");
		header("Content-Disposition:attachment;filename=$filename.xls");
		
		/* 生成会员购买率 */
		$data  = RC_Lang::lang('percent_buy_member') . "\t\n";
		$data .= RC_Lang::lang('member_count') . "\t" . RC_Lang::lang('order_member_count') . "\t" . RC_Lang::lang('member_order_count') . "\t" . RC_Lang::lang('percent_buy_member') . "\n";
	
		$data .= $user_num . "\t" . $have_order_usernum . "\t" . $user_all_order['order_num'] . "\t" . sprintf("%0.2f", ($user_num > 0 ? ($have_order_usernum / $user_num) : 0) * 100).'%' . "\n\n";
	
		/* 每会员平均订单数及购物额 */
		$data .= RC_Lang::lang('order_turnover_peruser') . "\t\n";
		$data .= RC_Lang::lang('member_sum') . "\t" . RC_Lang::lang('average_member_order') . "\t" . RC_Lang::lang('member_order_sum') . "\n";
		
		$ave_user_ordernum = $user_num > 0 ? sprintf("%0.2f", $user_all_order['order_num'] / $user_num) : 0;
		$ave_user_turnover = $user_num > 0 ? price_format($user_all_order['turnover'] / $user_num) : 0;
		
		$data .= price_format($user_all_order['turnover']) . "\t" . $ave_user_ordernum . "\t" . $ave_user_turnover . "\n\n";
	
		/* 每会员平均订单数及购物额 */
		$data .= RC_Lang::lang('order_turnover_percus') . "\t\n";
		$data .= RC_Lang::lang('guest_member_orderamount') . "\t" . RC_Lang::lang('guest_member_ordercount') . "\t" . RC_Lang::lang('guest_order_sum') . "\n";
		$order_num = $guest_all_order['order_num'] > 0 ? price_format($guest_all_order['turnover'] / $guest_all_order['order_num']) : 0;
		$data .= price_format($guest_all_order['turnover']) . "\t" . $guest_all_order['order_num'] . "\t" . $order_num;
		
		echo mb_convert_encoding($data. "\t", "GBK", "UTF-8");
		exit;
	}
}
// end