<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 后台权限API
 * @author royalwang
 *
 */
class orders_admin_purview_api extends Component_Event_Api {
    
    public function call(&$options) {
        $purviews = array(
            array('action_name' => __('编辑发货状态'), 'action_code' => 'order_ss_edit', 'relevance'   => ''),
            array('action_name' => __('编辑付款状态'), 'action_code' => 'order_ps_edit', 'relevance'   => ''),
            array('action_name' => __('编辑订单状态'), 'action_code' => 'order_os_edit', 'relevance'   => ''),
            array('action_name' => __('添加编辑订单'), 'action_code' => 'order_edit', 'relevance'   => ''),
            array('action_name' => __('查看未完成订单'), 'action_code' => 'order_view', 'relevance'   => ''),
            array('action_name' => __('查看已完成订单'), 'action_code' => 'order_view_finished', 'relevance'   => ''),
            array('action_name' => __('退款申请管理'), 'action_code' => 'repay_manage', 'relevance'   => ''),
            array('action_name' => __('缺货登记管理'), 'action_code' => 'booking', 'relevance'   => ''),
            array('action_name' => __('订单销售统计'), 'action_code' => 'sale_order_stats', 'relevance'   => ''),
            array('action_name' => __('客户流量统计'), 'action_code' => 'client_flow_stats', 'relevance'   => ''),
            array('action_name' => __('查看发货单'), 'action_code' => 'delivery_view', 'relevance'   => ''),
            array('action_name' => __('查看退货单'), 'action_code' => 'back_view', 'relevance'   => ''),	
        		
        	array('action_name' => __('客户统计'), 'action_code' => 'guest_stats', 'relevance'   => ''),
        	array('action_name' => __('订单统计'), 'action_code' => 'order_stats', 'relevance'   => ''),
        	array('action_name' => __('销售概况'), 'action_code' => 'sale_general_stats', 'relevance'   => ''),
        	array('action_name' => __('会员排行'), 'action_code' => 'users_order_stats', 'relevance'   => ''),
        	array('action_name' => __('销售明细'), 'action_code' => 'sale_list_stats', 'relevance'   => ''),
        	array('action_name' => __('销售排行'), 'action_code' => 'sale_order_stats', 'relevance'   => ''),
        	array('action_name' => __('访问购买率'), 'action_code' => 'visit_sold_stats', 'relevance'   => ''),
        	array('action_name' => __('广告转化率'), 'action_code' => 'adsense_conversion_stats', 'relevance'   => '')
        );
        
        return $purviews;
    }
}

// end