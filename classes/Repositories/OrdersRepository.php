<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//

namespace Ecjia\App\Orders\Repositories;

use Royalcms\Component\Repository\Repositories\AbstractRepository;
use ecjia_page;
use Ecjia\App\Orders\OrderStatus;
use Ecjia\App\Orders\GoodsAttr;

class OrdersRepository extends AbstractRepository
{
    protected $model = 'Ecjia\App\Orders\Models\OrdersModel';
    
    protected $orderBy = ['order_info.order_id' => 'desc'];
    
//     protected $type;
    
//     public function __construct($type)
//     {
//         parent::__construct();
        
//         $this->type = $type;
//     }
    
   
    public function findWhereLimit(array $where, $columns = ['*'], $page = 1, $perPage = 15, callable $callback = null)
    {
        $this->newQuery();
        
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->query->where($field, $condition, $val);
            }
            else {
                $this->query->where($field, '=', $value);
            }
        }
        
        if (is_callable($callback)) {
            $callback($this->query);
        }
        
        if ($page && $perPage) {
            $this->query->forPage($page, $perPage);
        }
        
        return $this->query->get($columns);
    }
    
    
    public function findWhereCount(array $where, $columns = ['*'], callable $callback = null)
    {
        $this->newQuery();
        
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->query->where($field, $condition, $val);
            }
            else {
                $this->query->where($field, '=', $value);
            }
        }
        
        if (is_callable($callback)) {
            $callback($this->query);
        }
        
        return $this->query->count();
    }
    
    
    /**
     *  获取用户指定范围的订单列表
     *
     * @access  public
     * @param   int         $user_id        用户ID号
     * @param   int         $num            列表最大数量
     * @param   int         $start          列表起始位置
     * @return  array       $order_list     订单列表
     */
    public function getUserOrdersList($user_id, $type = null, $page = 1, $size = 15, $keywords = null, $store_id = null)
    {
        $where = [
        	'order_info.user_id' => $user_id,
        	'order_info.is_delete' => 0,
        ];
        
        if ($store_id > 0) {
            $where['order_info.store_id'] = $store_id;
        }

        $field = [
        	'order_info.order_id',
        	'order_info.order_sn',
        	'order_info.order_status',
        	'order_info.shipping_status',
        	'order_info.pay_status',
        	'order_info.add_time',
        	'order_info.goods_amount',
        	'order_info.shipping_fee',
        	'order_info.insure_fee',
        	'order_info.pay_fee',
        	'order_info.pack_fee',
        	'order_info.card_fee',
        	'order_info.tax',
        	'order_info.integral_money',
        	'order_info.bonus',
        	'order_info.discount',
        	'order_info.pay_id',
        	'order_info.order_amount',
        	'order_info.store_id',
        ];
        
        if (!empty($keywords)) {
            $field[] = 'order_goods.goods_id';
            $field[] = 'order_goods.goods_name';
        }
        
        $whereQuery = null;
        if (!empty($type)) {
            if ($type == 'allow_comment') {
                $whereQuery = function ($query) {
                    $query->whereIn('order_info.order_status', [OS_CONFIRMED, OS_SPLITED])
                          ->whereIn('order_info.pay_status', [PS_PAYED, PS_PAYING])
                          ->where('order_info.shipping_status', SS_RECEIVED);
                    
                    $query->whereHas('orderGoods.comment', function ($query) use ($user_id) {
                        $query->where('user_id', $user_id)->where('comment_type', 0)->where('parent_id', 0);
                    }, 0);
                };
            } else {
                $whereQuery = OrderStatus::getQueryOrder($type);
            }
        }
        
        $count = $this->findWhereCount($where, [], function($query) use ($keywords, $whereQuery) {
            if (!empty($keywords)) {
                $query->where(function ($query) use ($keywords) {
                    return $query->where('order_goods.goods_name', 'like', '%' . $keywords .'%')
                          ->orWhere('order_info.order_sn', 'like', '%' . $keywords .'%');
                });
                
                $query->leftJoin('order_goods', function ($join) {
                    $join->on('order_info.order_id', '=', 'order_goods.order_id');
                });
                
                $query->groupby('order_info.order_id');
            }
            
            if (is_callable($whereQuery)) {
                $whereQuery($query);
            }
        });
        
        $orders = $this->findWhereLimit($where, $field, $page, $size, function($query) use ($keywords, $whereQuery, $type, $user_id) {
            $query->with(['orderGoods', 'orderGoods.goods', 'store', 'payment', 'orderGoods.comment' => function ($query) {
                $query->select('comment_id', 'has_image')->where('comment_type', 0)->where('parent_id', 0);
            }]);
            
            if (!empty($keywords)) {
                $query->leftJoin('order_goods', function ($join) {
                    $join->on('order_info.order_id', '=', 'order_goods.order_id');
                });
                    
                $query->where(function ($query) use ($keywords) {
                    $query->where('order_goods.goods_name', 'like', '%' . $keywords .'%')
                          ->orWhere('order_info.order_sn', 'like', '%' . $keywords .'%');
                });
                
                $query->groupby('order_info.order_id');
            }
            
            if (is_callable($whereQuery)) {
                $whereQuery($query);
            }
        });
        
        $orderlist = $orders->map(function ($item) {
            //计算订单总价格
            $total_fee = $item->goods_amount + $item->shipping_fee + $item->insure_fee + $item->pay_fee + $item->pack_fee + $item->card_fee + $item->tax - $item->integral_money - $item->bonus - $item->discount; 
            $goods_number = 0;
            list($label_order_status, $status_code) = OrderStatus::getOrderStatusLabel($item->order_status, $item->shipping_status, $item->pay_status, $item->payment->is_cod);
            
            $data = [
            	'seller_id'        => $item->store->store_id,
            	'seller_name'      => $item->store->merchants_name,
            	'manage_mode'      => $item->store->manage_mode,
                
                'order_id'          => $item->order_id,
                'order_sn'          => $item->order_sn,
                'order_amount'      => $item->order_amount,
                'order_status'      => $item->order_status,
                'shipping_status'   => $item->shipping_status,
                'pay_status'        => $item->pay_status,
                'pay_code'          => $item->payment->pay_code,
                'is_cod'            => $item->payment->is_cod,
                'label_order_status'    => $label_order_status,
                'order_status_code'     => $status_code,
                'order_time'        => ecjia_time_format($item->add_time),
                'total_fee'         => $total_fee,
                'discount'          => $item->discount,
                'goods_number'      => & $goods_number,
                'formated_total_fee'        => ecjia_price_format($total_fee, false),
                'formated_integral_money'   => ecjia_price_format($item->integral_money, false),
                'formated_bonus'            => ecjia_price_format($item->bonus, false),
                'formated_shipping_fee'     => ecjia_price_format($item->shipping_fee, false),
                'formated_discount'         => ecjia_price_format($item->discount, false),
                
                'order_info' => [
            	   'pay_code'      => $item->payment->pay_code,
            	   'order_amount'  => $item->order_amount,
            	   'order_id'      => $item->order_id,
            	   'order_sn'      => $item->order_sn,
                ],
                
                'goods_list' => [],
            ];
            
            $data['goods_list'] = $item->orderGoods->map(function ($item) use (& $goods_number) {
                $attr = GoodsAttr::decodeGoodsAttr($item->goods_attr);
                $subtotal = $item->goods_price * $item->goods_number;
                $goods_number += $item->goods_number;
                
                $data = [
                	'goods_id'         => $item->goods_id,
                	'name'             => $item->goods_name,
                	'goods_attr_id'    => $item->goods_attr_id,
                	'goods_attr'       => $attr,
                	'goods_number'     => $item->goods_number,
                	'subtotal'         => ecjia_price_format($subtotal, false),
                	'formated_shop_price' => ecjia_price_format($item->goods_price, false),
                    'img' => [
                    	'small'    => ecjia_upload_url($item->goods->goods_thumb),
                    	'thumb'    => ecjia_upload_url($item->goods->goods_img),
                    	'url'      => ecjia_upload_url($item->goods->original_img),
                    ],
                    'is_commented' => empty($item->orderGoods->comment->comment_id) ? 0 : 1,
                    'is_showorder' => empty($item->orderGoods->comment->has_image) ? 0 : 1,
                ];
                
                return $data;
            })->toArray();

            return $data;
        });
        
        dd($orderlist->toArray());
//         dd($orders);
        
        return array('order_list' => $orderlist->toArray(), 'count' => $count);
    }
    
    
    
}