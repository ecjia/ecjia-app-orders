<div class="row">
	<div class="col-lg-12 ">
		<div class="panel">
			<div class="panel-body">
				<header class="panel-title">
					店铺资金
					<span class="pull-right">
						<a href="{RC_Uri::url('commission/merchant/init')}">查看更多 >></a>
					</span>
				</header>
				<div class="task-progress-content">
					<div class="item-column">
						<div class="title">账户余额（元）</div>
						<div class="num">{$data.formated_money}</div>
					</div>
					<div class="item-column">
						<div class="title">冻结资金（元）</div>
						<div class="num">{$data.formated_frozen_money}</div>
					</div>
					<div class="item-column">
						<div class="title">保证金（元）</div>
						<div class="num">{$data.formated_deposit}</div>
					</div>
					<div class="item-column">
						<div class="title">可用余额（元）</div>
						<div class="num">{$data.formated_amount_available}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<header class="panel-title">订单统计类型</header>
				<div class="task-progress-content">
					<div class="item-column">
						<div class="title">配送订单（单）</div>
						<div class="num">{$data.order_count}</div>
					</div>
					<div class="item-column">
						<div class="title">自提订单（单）</div>
						<div class="num">{$data.storepickup_count}</div>
					</div>
					<div class="item-column">
						<div class="title">到店订单（单）</div>
						<div class="num">{$data.storebuy_count}</div>
					</div>
					<div class="item-column">
						<div class="title">团购订单（单）</div>
						<div class="num">{$data.groupbuy_count}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<header class="panel-title">平台配送</h1></header>
				<div class="task-progress-content">
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/express.png" />
						<div class="title">提醒派单</div>
						<div class="num">{$data.remind_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/wait_get.png" />
						<div class="title">待取货</div>
						<div class="num">{$data.plaftorm_await_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/shipping.png" />
						<div class="title">配送中</div>
						<div class="num">{$data.platform_shipping_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/finished.png" />
						<div class="title">已完成</div>
						<div class="num">{$data.platform_finished_count}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<header class="panel-title">商家配送</h1></header>
				<div class="task-progress-content">
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/express.png" />
						<div class="title">待派单</div>
						<div class="num">{$data.wait_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/wait_get.png" />
						<div class="title">待取货</div>
						<div class="num">{$data.merchant_get_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/shipping.png" />
						<div class="title">配送中</div>
						<div class="num">{$data.merchant_shipping_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/finished.png" />
						<div class="title">已完成</div>
						<div class="num">{$data.merchant_finished_count}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<header class="panel-title">促销活动</h1></header>
				<div class="task-progress-content">
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/promotion.png" />
						<div class="title">促销</div>
						<div class="num">{$data.promotion_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/favourable.png" />
						<div class="title">优惠</div>
						<div class="num">{$data.favourable_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/groupbuy.png" />
						<div class="title">团购</div>
						<div class="num">{$data.groupbuy_count}</div>
					</div>
					<div class="item-row">
						<img src="{$ecjia_main_static_url}img/merchant_dashboard/quickpay.png" />
						<div class="title">买单</div>
						<div class="num">{$data.quickpay_count}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<header class="panel-title">商品热卖榜</h1></header>
				<table class="table table-striped table-hover table-hide-edit">
					<thead>
						<tr>
							<th class="w100">排行</th>
							<th>商品名称</th>
							<th class="w100">货号</th>
							<th class="w100">销售量</th>
							<th class="w120">销售额</th>
							<th class="w100">单价</th>
						</tr>
					</thead>
					<!-- {foreach from=$goods_list.item item=item key=key} -->
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<!-- {/foreach} -->
				</table>
			</div>
		</div>
	</div>
</div>