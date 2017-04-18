<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 收货地址
 * @author will
 *
 */
class list_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		if ($_SESSION['admin_id'] <= 0 && $_SESSION['ru_id'] <= 0) {
			EM_Api::outPut(100);
		}
		
		$result_view = $ecjia->admin_priv('order_view');
		$result_edit = $ecjia->admin_priv('order_edit');
		if (is_ecjia_error($result_view)) {
			EM_Api::outPut($result_view);
		} elseif (is_ecjia_error($result_edit)) {
			EM_Api::outPut($result_edit);
		}
		
		$order_id	= _POST('order_id', 0);
		if ($order_id <= 0) {
			EM_Api::outPut(101);
		}
		$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id));
		$address_list = array();
		if ($order_info['user_id'] > 0 ) {
			$user_id = $order_info['user_id'];
			$address_result = RC_Model::model('user/user_address_model')->where(array('user_id' => $user_id))->select();
			if (!empty($address_result)) {
				$db_region = RC_Model::model('shipping/region_model');
				$user_info = RC_Model::model('user/users_model')->where(array('user_id' => $user_id))->find();
				foreach ($address_result as $key => $value) {
					$address_list[$key]['id']			= $value['address_id'];
					$address_list[$key]['consignee']	= $value['consignee'];
					$address_list[$key]['address']		= $value['address'];
						
					$country	= $value['country'];
					$province	= $value['province'];
					$city		= $value['city'];
					$district	= $value['district'];
			
					$region_name = $db_region->where(array('region_id' => array('in'=>$country,$province,$city,$district)))->order('region_type')->select();
			
					$address_list[$key]['country_name']		= $region_name[0]['region_name'];
					$address_list[$key]['province_name']	= $region_name[1]['region_name'];
					$address_list[$key]['city_name']		= $region_name[2]['region_name'];
					$address_list[$key]['district_name']	= $region_name[3]['region_name'];
					$address_list[$key]['tel']				= $value['tel'];
					$address_list[$key]['mobile']			= $value['mobile'];
			
					if ($value['address_id'] == $user_info['address_id']) {
						$address_list[$key]['default_address'] = 1;
					} else {
						$address_list[$key]['default_address'] = 0;
					}
				}
			}
		}
			
		return $address_list;
	} 
}


// end