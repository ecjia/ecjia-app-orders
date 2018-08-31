<div class="move-mod-group" id="widget_admin_dashboard_ordertype_stats">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">订单类型统计</h3>
	</div>
	<div class="move-mod-content">
		<div class="mod-content-item">
			<div class="title">配送订单（单）</div>
			<div class="num">{$data.order_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">到店订单（单）</div>
			<div class="num">{$data.storebuy_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">自提订单（单）</div>
			<div class="num">{$data.storepickup_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">团购订单（单）</div>
			<div class="num">{$data.groupbuy_count}</div>
		</div>
	</div>
</div>
<div class="move-mod-group" id="widget_admin_dashboard_user_stats">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">会员统计</h3>
	</div>
	<div class="move-mod-content">
		<div class="mod-content-item">
			<div class="title">今日新增（个）</div>
			<div class="num">{$data.today_num}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">7日新增（个）</div>
			<div class="num">{$data.sevendays_num}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">30天新增（个）</div>
			<div class="num">{$data.thritydays_num}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">总会员数（个）</div>
			<div class="num">{$data.total_num}</div>
		</div>
	</div>
</div>
<div class="move-mod-group" id="widget_admin_dashboard_account_stats">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">待处理财务统计</h3>
	</div>
	<div class="move-mod-content">
		<div class="mod-content-item">
			<div class="title">会员充值（单）</div>
			<div class="num">{$data.recharge_num}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">会员提现（单）</div>
			<div class="num">{$data.withdraw_num}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">会员退款（单）</div>
			<div class="num">{$data.refund_num}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">商家提现（单）</div>
			<div class="num">{$data.merchant_withdraw_num}</div>
		</div>
	</div>
</div>