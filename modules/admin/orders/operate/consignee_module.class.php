<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 修改收货地址
 * @author will
 *
 */
class consignee_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		$result_edit = $ecjia->admin_priv('order_edit');
		$result_view = $ecjia->admin_priv('order_view');
		if (is_ecjia_error($result_edit)) {
			EM_Api::outPut($result_edit);
		} elseif (is_ecjia_error($result_view)) {
			EM_Api::outPut($result_view);
		}
		
		$order_id		= _POST('order_id', 0);
		
		$address_id		= _POST('address_id', 0);
		$consignee		= _POST('consignee');
		$address		= _POST('address');
		$country_id		= _POST('country_id', 0);
		$province_id	= _POST('province_id', 0);
		$city_id		= _POST('city_id', 0);
		$district_id	= _POST('district_id', 0);
		$mobile			= _POST('mobile');
		
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}
		
		if (isset($_SESSION['ru_id']) && $_SESSION['ru_id'] > 0) {
			$ru_id_group = RC_Model::model('orders/order_goods_model')->where(array('order_id' => $order_id))->group('ru_id')->get_field('ru_id',true);
			if (count($ru_id_group) > 1 || $ru_id_group[0] != $_SESSION['ru_id']) {
				return new ecjia_error('no_authority', '对不起，您没权限对此订单进行操作！');
			}
		}
		
		$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
		if (empty($order_info)) {
			EM_Api::outPut(101);
		}
		
		RC_Loader::load_app_func('order', 'orders');
		/* 判断是非为会员购买*/
		if ($address_id > 0 && $order_info['user_id'] > 0) {
			$db_address = RC_Model::model('user/user_address_model');
			$field = "consignee, email, country, province, city, district, address, zipcode, tel, mobile, sign_building, best_time";
			$orders = $db_address->field($field)->find(array('user_id' => $order_info['user_id'],'address_id' => $address_id));
			update_order($order_id, $orders);
		} else {
			if ((empty($consignee) || empty($address) || empty($country_id) || empty($province_id) || empty($city_id) || empty($mobile))) {
				EM_Api::outPut(101);
			}
			$order = array(
					'consignee' => $consignee,
					'country'	=> $country_id,
					'province'	=> $province_id,
					'city'		=> $city_id,
					'district'	=> $district_id,
					'mobile'	=> $mobile,
					'address'	=> $address,
			);
			update_order($order_id, $order);
		}
		
		/* 记录日志 */
		$sn = '订单号是 ' . $order_info['order_sn'];
		ecjia_admin::admin_log($sn, 'edit', 'order_consignee');
		
		return array();
	} 
}


// end