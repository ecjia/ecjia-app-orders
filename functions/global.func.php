<?php
defined('IN_ECJIA') or exit('No permission resources.');


/**
 * 取得图表颜色
 *
 * @access  public
 * @param   integer $n  颜色顺序
 * @return  void
 */
function chart_color($n)
{
	/* 随机显示颜色代码 */
	$arr = array('33FF66', 'FF6600', '3399FF', '009966', 'CC3399', 'FFCC33', '6699CC', 'CC3366', '33FF66', 'FF6600', '3399FF');

	if ($n > 8)
	{
		$n = $n % 8;
	}

	return $arr[$n];
}
/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '')
{
	if (empty($item_list))
	{
		return $field_name . " IN ('') ";
	}
	else
	{
		if (!is_array($item_list))
		{
			$item_list = explode(',', $item_list);
		}
		$item_list = array_unique($item_list);
		$item_list_tmp = '';
		foreach ($item_list AS $item)
		{
			if ($item !== '')
			{
				$item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
			}
		}
		if (empty($item_list_tmp))
		{
			return $field_name . " IN ('') ";
		}
		else
		{
			return $field_name . ' IN (' . $item_list_tmp . ') ';
		}
	}
}
/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access private
 * @param int $cat_id
 *        	上级分类ID
 * @param array $arr
 *        	含有所有分类的数组
 * @param int $level
 *        	级别
 * @return void
 */
function cat_options($spec_cat_id, $arr) {
	static $cat_options = array ();

	if (isset ( $cat_options [$spec_cat_id] )) {
		return $cat_options [$spec_cat_id];
	}

	if (! isset ( $cat_options [0] )) {
		$level = $last_cat_id = 0;
		$options = $cat_id_array = $level_array = array ();
		$data = false;
		if ($data === false) {
			while ( ! empty ( $arr ) ) {
				foreach ( $arr as $key => $value ) {
					$cat_id = $value ['cat_id'];
					if ($level == 0 && $last_cat_id == 0) {
						if ($value ['parent_id'] > 0) {
							break;
						}

						$options [$cat_id] = $value;
						$options [$cat_id] ['level'] = $level;
						$options [$cat_id] ['id'] = $cat_id;
						$options [$cat_id] ['name'] = $value ['cat_name'];
						unset ( $arr [$key] );

						if ($value ['has_children'] == 0) {
							continue;
						}
						$last_cat_id = $cat_id;
						$cat_id_array = array (
								$cat_id
						);
						$level_array [$last_cat_id] = ++ $level;
						continue;
					}
						
					if ($value ['parent_id'] == $last_cat_id) {
						$options [$cat_id] = $value;
						$options [$cat_id] ['level'] = $level;
						$options [$cat_id] ['id'] = $cat_id;
						$options [$cat_id] ['name'] = $value ['cat_name'];
						unset ( $arr [$key] );

						if ($value ['has_children'] > 0) {
							if (end ( $cat_id_array ) != $last_cat_id) {
								$cat_id_array [] = $last_cat_id;
							}
							$last_cat_id = $cat_id;
							$cat_id_array [] = $cat_id;
							$level_array [$last_cat_id] = ++ $level;
						}
					} elseif ($value ['parent_id'] > $last_cat_id) {
						break;
					}
				}

				$count = count ( $cat_id_array );
				if ($count > 1) {
					$last_cat_id = array_pop ( $cat_id_array );
				} elseif ($count == 1) {
					if ($last_cat_id != end ( $cat_id_array )) {
						$last_cat_id = end ( $cat_id_array );
					} else {
						$level = 0;
						$last_cat_id = 0;
						$cat_id_array = array ();
						continue;
					}
				}

				if ($last_cat_id && isset ( $level_array [$last_cat_id] )) {
					$level = $level_array [$last_cat_id];
				} else {
					$level = 0;
				}
			}
		} else {
			$options = $data;
		}
		$cat_options [0] = $options;
	} else {
		$options = $cat_options [0];
	}

	if (! $spec_cat_id) {
		return $options;
	} else {
		if (empty ( $options [$spec_cat_id] )) {
			return array ();
		}

		$spec_cat_id_level = $options [$spec_cat_id] ['level'];

		foreach ( $options as $key => $value ) {
			if ($key != $spec_cat_id) {
				unset ( $options [$key] );
			} else {
				break;
			}
		}

		$spec_cat_id_array = array ();
		foreach ( $options as $key => $value ) {
			if (($spec_cat_id_level == $value ['level'] && $value ['cat_id'] != $spec_cat_id) || ($spec_cat_id_level > $value ['level'])) {
				break;
			} else {
				$spec_cat_id_array [$key] = $value;
			}
		}
		$cat_options [$spec_cat_id] = $spec_cat_id_array;

		return $spec_cat_id_array;
	}
}
/**
 * 取得品牌列表
 *
 * @return array 品牌列表 id => name
 */
function get_brand_list() {
	$db = RC_Loader::load_app_model ('brand_model', 'orders');

	$res = $db->field('brand_id, brand_name')->order('sort_order asc')->select();
	$brand_list = array ();
	if (! empty ( $res )) {
		foreach ( $res as $row ) {
			$brand_list[$row ['brand_id']]= addslashes($row ['brand_name']);
		}
	}
	return $brand_list;
}

/**
 * 获得指定分类下的子分类的数组
 *
 * @access public
 * @param int $cat_id
 *        	分类的ID
 * @param int $selected
 *        	当前选中分类的ID
 * @param boolean $re_type
 *        	返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param int $level
 *        	限定返回的级数。为0时返回所有级数
 * @param int $is_show_all
 *        	如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return mix
 */
function cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true) {
	// 加载方法
	$db_goods = RC_Loader::load_app_model('goods_model', 'orders');
	$db_category = RC_Loader::load_app_model('sys_category_viewmodel', 'orders');
	$db_goods_cat = RC_Loader::load_app_model('goods_cat_viewmodel', 'orders');
	static $res = NULL;

	if ($res === NULL) {
		$data = false;
		if ($data === false) {
			$res = $db_category->join('category')->group('c.cat_id')->order(array('c.parent_id' => 'asc', 'c.sort_order' => 'asc'))->select();
			$res2 = $db_goods->field ( 'cat_id, COUNT(*)|goods_num' )->where(array('is_delete' => 0,'is_on_sale' => 1))->group ('cat_id asc')->select();
			$res3 = $db_goods_cat->join('goods')->where(array('g.is_delete' => 0,'g.is_on_sale' => 1))->group ('gc.cat_id')->select();
			$newres = array ();
			foreach($res2 as $k => $v) {
				$newres [$v ['cat_id']] = $v ['goods_num'];
				foreach ( $res3 as $ks => $vs ) {
					if ($v ['cat_id'] == $vs ['cat_id']) {
						$newres [$v ['cat_id']] = $v ['goods_num'] + $vs ['goods_num'];
					}
				}
			}
			if (! empty ( $res )) {
				foreach ( $res as $k => $v ) {
					$res [$k] ['goods_num'] = ! empty($newres [$v ['cat_id']]) ? $newres [$v['cat_id']] : 0;
				}
			}
				
		} else {
			$res = $data;
		}
	}
	if (empty ( $res ) == true) {
		return $re_type ? '' : array ();
	}

	$options = cat_options ( $cat_id, $res ); // 获得指定分类下的子分类的数组

	$children_level = 99999; // 大于这个分类的将被删除
	if ($is_show_all == false) {
		foreach ( $options as $key => $val ) {
			if ($val ['level'] > $children_level) {
				unset ( $options [$key] );
			} else {
				if ($val ['is_show'] == 0) {
					unset ( $options [$key] );
					if ($children_level > $val ['level']) {
						$children_level = $val ['level']; // 标记一下，这样子分类也能删除
					}
				} else {
					$children_level = 99999; // 恢复初始值
				}
			}
		}
	}

	/* 截取到指定的缩减级别 */
	if ($level > 0) {
		if ($cat_id == 0) {
			$end_level = $level;
		} else {
			$first_item = reset ( $options ); // 获取第一个元素
			$end_level = $first_item ['level'] + $level;
		}

		/* 保留level小于end_level的部分 */
		foreach ( $options as $key => $val ) {
			if ($val ['level'] >= $end_level) {
				unset ( $options [$key] );
			}
		}
	}

	if ($re_type == true) {
		$select = '';
		if (! empty ( $options )) {
			foreach ( $options as $var ) {
				$select .= '<option value="' . $var ['cat_id'] . '" ';
				$select .= ($selected == $var ['cat_id']) ? "selected='ture'" : '';
				$select .= '>';
				if ($var ['level'] > 0) {
					$select .= str_repeat ( '&nbsp;', $var ['level'] * 4 );
				}
				$select .= htmlspecialchars ( addslashes($var ['cat_name'] ), ENT_QUOTES ) . '</option>';
			}
		}
		return $select;
	} else {
		if (! empty($options )) {
			foreach ($options as $key => $value ) {
				$options [$key] ['url'] = build_uri ('category', array('cid' => $value ['cat_id']), $value ['cat_name']);
			}
		}
		return $options;
	}
}

/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access public
 * @param integer $cat
 *        	指定的分类ID
 * @return string
 */
function get_children($cat = 0) {
	return 'g.cat_id ' . db_create_in (array_unique(array_merge(array($cat), array_keys(cat_list($cat, 0, false )))));
}

/**
 * 生成查询订单总金额的字段
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_amount_field($alias = '')
{
	return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
	" + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
	" + {$alias}card_fee ";
}
/**
 * 生成查询订单的sql
 * @param   string  $type   类型
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_query_sql($type = 'finished', $alias = '')
{
	/* 已完成订单 */
	if ($type == 'finished')
	{
		return " AND {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
		" AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
		" AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
	}
	/* 待发货订单 */
	elseif ($type == 'await_ship')
	{
		return " AND   {$alias}order_status " .
		db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) .
		" AND   {$alias}shipping_status " .
		db_create_in(array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) .
		" AND ( {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " OR {$alias}pay_id " . db_create_in(payment_id_list(true)) . ") ";
	}
	/* 待付款订单 */
	elseif ($type == 'await_pay')
	{
		return " AND   {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
		" AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
		" AND ( {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR {$alias}pay_id " . db_create_in(payment_id_list(false)) . ") ";
	}
	/* 未确认订单 */
	elseif ($type == 'unconfirmed')
	{
		return " AND {$alias}order_status = '" . OS_UNCONFIRMED . "' ";
	}
	/* 未处理订单：用户可操作 */
	elseif ($type == 'unprocessed')
	{
	return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
	" AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
	" AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
	}
	/* 未付款未发货订单：管理员可操作 */
	elseif ($type == 'unpay_unship')
	{
	return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
	" AND {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
	" AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
	}
	/* 已发货订单：不论是否付款 */
	elseif ($type == 'shipped')
	{
	return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
	" AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " ";
	}
	else
	{
	die('函数 order_query_sql 参数错误');
	}
}

/**
 * 取得支付方式id列表
 * @param   bool    $is_cod 是否货到付款
 * @return  array
 */
function payment_id_list($is_cod)
{
	$db = RC_Loader::load_app_model('payment_model','orders');
	$where = '';
	if ($is_cod)
	{
		$where = " is_cod = 1";
	}
	else
	{
		$where = " is_cod = 0";
	}
	$arr = $db->field('pay_id')->where($where)->select();
	return $arr;
}


/**
 * 截取UTF-8编码下字符串的函数
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 *
 * @return  string
 */
function sub_str($str, $length = 0, $append = true)
{
	$str = trim($str);
	$strlength = strlen($str);

	if ($length == 0 || $length >= $strlength)
	{
		return $str;
	}
	elseif ($length < 0)
	{
		$length = $strlength + $length;
		if ($length < 0)
		{
			$length = $strlength;
		}
	}

	if (function_exists('mb_substr'))
	{
		$newstr = mb_substr($str, 0, $length, EC_CHARSET);
	}
	elseif (function_exists('iconv_substr'))
	{
		$newstr = iconv_substr($str, 0, $length, EC_CHARSET);
	}
	else
	{
		//$newstr = trim_right(substr($str, 0, $length));
		$newstr = substr($str, 0, $length);
	}

	if ($append && $str != $newstr)
	{
		$newstr .= '...';
	}

	return $newstr;
}

