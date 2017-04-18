<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单取消派单
 * @author will
 *
 */
class cancelgrab_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		
		$order_id = _POST('order_id', 0);
		
		if ($order_id <= 0) {
			EM_Api::outPut(101);
		}
		
		/*验证订单是否属于此入驻商*/
		if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
			$ru_id_group = RC_Model::model('orders/order_goods_model')->where(array('order_id' => $order_id))->group('ru_id')->get_field('ru_id', true);
			if (count($ru_id_group) > 1 || $ru_id_group[0] != $_SESSION['ru_id']) {
				return new ecjia_error('no_authority', '对不起，您没权限对此订单进行操作！');
			}
		}
		
		RC_Model::model('orders/store_order_model')->where(array('order_id' => $order_id))->delete();
		
		return array();
	} 
}



// end