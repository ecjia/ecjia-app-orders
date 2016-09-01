<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->
<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.visit_sold.init();
</script>
<!-- {/block} -->
<!-- {block name="main_content"} -->

<!--访问购买率-->
<div class="alert alert-info">
	<a class="close" data-dismiss="alert">×</a>
	<strong>{lang key='orders::statistic.tips'}</strong>{lang key='orders::statistic.no_orders_visit_buy'}
</div>

<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
		<!-- {if $action_link} -->
		<a class="btn plus_or_reply" id="sticky_a" href='{$action_link.href}&cat_id={$cat_id}&brand_id={$brand_id}&show_num={$show_num}'><i class="fontello-icon-download"></i>{$action_link.text}</a>
	    <!-- {/if} -->
	</h3>
</div>

<div class="row-fluid batch">
	<div class="choose_list f_r">
		<form name="searchForm" action="{RC_Uri::url('orders/admin_visit_sold/init')}" method="post">
			<select class="w150" name="cat_id">
		      	<option value="0">{lang key='orders::statistic.pls_select_category'}</option><!-- {$cat_list} -->
		    </select>
		    <select class="w150" name="brand_id">
		      	<option value="0">{lang key='orders::statistic.pls_select_brand'}</option>
		      <!-- {html_options options=$brand_list selected=$brand_id} -->
    		</select>
		    <input name="show_num" type="text" class="w70" value="{$show_num}" placeholder="{lang key='orders::statistic.show_num'}" />
		    <input type="hidden" name="order_type" value="{$order_type}" />
		    <input type="submit" name="submit" value="{lang key='orders::statistic.query'}" class="btn" />
		</form>
	</div>
</div>

<div class="row-fluid">
	<table class="table table-striped" id="smpl_tbl">
		<thead>
			<tr>
				<th class="w100">{lang key='orders::statistic.order_by'}</th>
				<th>{lang key='orders::statistic.goods_name'}</th>
				<th class="w150">{lang key='orders::statistic.fav_exponential'}</th>
				<th class="w150">{lang key='orders::statistic.buy_times'}</th>
				<th class="w100">{lang key='orders::statistic.list_visit_buy'}</th>
			</tr>
		</thead>
		<tbody>
			<!-- {foreach from=$click_sold_info.item key=Key item=list} -->
			<tr>
				<td>{$Key+1}</td>
				<td>
					<a href='{RC_Uri::url("goods/admin/preview", "id={$list.goods_id}")}' target="_blank">{$list.goods_name}</a>
				</td>
				<td>{$list.click_count}</td>
				<td>{$list.sold_times}</td>
				<td>{$list.scale}</td>
			</tr>
			<!-- {foreachelse} -->
	    	<tr><td class="dataTables_empty" colspan="5">{lang key='system::system.no_records'}</td></tr>
	  		<!-- {/foreach} -->
		</tbody>
	</table>
</div>
<!-- {/block} -->