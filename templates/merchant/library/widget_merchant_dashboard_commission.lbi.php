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
						<div class="price">{$data.formated_money}</div>
					</div>
					<div class="item-column">
						<div class="title">冻结资金（元）</div>
						<div class="price">{$data.formated_frozen_money}</div>
					</div>
					<div class="item-column">
						<div class="title">保证金（元）</div>
						<div class="price">{$data.formated_deposit}</div>
					</div>
					<div class="item-column">
						<div class="title">可用余额（元）</div>
						<div class="price">{$data.formated_amount_available}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<div class="task-progress">
					<h1>订单统计类型</h1>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<div class="task-progress">
					<h1>平台配送</h1>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<div class="task-progress">
					<h1>商家配送</h1>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<div class="task-progress">
					<h1>促销活动</h1>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-body">
				<div class="task-progress">
					<h1>商品热卖榜</h1>
				</div>
			</div>
		</div>
	</div>
</div>