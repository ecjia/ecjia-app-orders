<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * ECJIA 后台订单菜单API
 * @author royalwang
 *
 */
class orders_admin_menu_api extends Component_Event_Api {
	
	public function call(&$options) {	
		$menus 		= ecjia_admin::make_admin_menu('04_order', __('订单管理'), '', 2);
		$submenus 	= array(
			ecjia_admin::make_admin_menu('02_order_list', __('订单列表'), RC_Uri::url('orders/admin/init'), 1)->add_purview('order_view'),
			ecjia_admin::make_admin_menu('03_order_query', __('订单查询'), RC_Uri::url('orders/admin/order_query'), 2)->add_purview('order_view'),
			ecjia_admin::make_admin_menu('04_merge_order', __('合并订单'), RC_Uri::url('orders/admin/merge'), 3)->add_purview('order_os_edit'),
// 			new ecjia_menu('05_edit_order_print', 	'订单打印', 	'index.php?m=orders&c=admin&a=templates')->add_purview('order_os_edit'),
			ecjia_admin::make_admin_menu('08_add_order', __('添加订单'), RC_Uri::url('orders/admin/add'), 5)->add_purview('order_edit'),
			ecjia_admin::make_admin_menu('09_delivery_order', __('发货单列表'), RC_Uri::url('orders/admin_order_delivery/init'), 6)->add_purview('delivery_view'),
			ecjia_admin::make_admin_menu('10_back_order', __('退货单列表'), RC_Uri::url('orders/admin_order_back/init'), 7)->add_purview('back_view'),
		);
		$menus->add_submenu($submenus);
		
		return $menus;
	}
}

// end