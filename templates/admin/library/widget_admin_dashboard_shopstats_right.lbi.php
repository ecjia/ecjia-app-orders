<div class="move-mod-group" id="widget_admin_dashboard_express_stats">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">配送调度统计</h3>
	</div>
	<div class="move-mod-content">
		<div class="mod-content-item">
			<div class="title">待派单（单）</div>
			<div class="num">{if $data.express_count.wait_grab}{$data.express_count.wait_grab}{else}0{/if}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">待取货（单）</div>
			<div class="num">{if $data.express_count.wait_pickup}{$data.express_count.wait_pickup}{else}0{/if}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">配送中（单）</div>
			<div class="num">{if $data.express_count.sending}{$data.express_count.sending}{else}0{/if}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">总配送单（单）</div>
			<div class="num">{if $data.express_count.count}{$data.express_count.count}{else}0{/if}</div>
		</div>
	</div>
</div>
<div class="move-mod-group" id="widget_admin_dashboard_promotion_stats">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">促销活动</h3>
	</div>
	<div class="move-mod-content">
		<div class="mod-content-item">
			<div class="title">促销（个）</div>
			<div class="num">{$data.promotion_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">优惠（个）</div>
			<div class="num">{$data.favourable_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">团购（个）</div>
			<div class="num">{$data.groupbuy_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">买单（个）</div>
			<div class="num">{$data.quickpay_count}</div>
		</div>
	</div>
</div>
<div class="move-mod-group" id="widget_admin_dashboard_article_stats">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">文章统计情况</h3>
	</div>
	<div class="move-mod-content">
		<div class="mod-content-item">
			<div class="title">今日新增（篇）</div>
			<div class="num">{$data.today_article_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">待审核文章（篇）</div>
			<div class="num">{$data.waitcheck_article_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">待审核评论（条）</div>
			<div class="num">{$data.waitcheck_comment_count}</div>
		</div>
		<div class="mod-content-item">
			<div class="title">总文章数（篇）</div>
			<div class="num">{$data.article_count}</div>
		</div>
	</div>
</div>