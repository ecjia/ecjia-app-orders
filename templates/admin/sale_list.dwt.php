<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->
<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.sale_list.init()
</script>
<!-- {/block} -->
<!-- {block name="main_content"} -->

<!--销售明细-->
<div class="alert alert-info">
	<a class="close" data-dismiss="alert">×</a>
	<strong>{lang key='orders::statistic.tips'}</strong>{lang key='orders::statistic.no_sales_details'}
</div>

<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
		<!-- {if $action_link} --><a class="btn plus_or_reply"  id="sticky_a" href="{$action_link.href}&start_date={$start_date}&end_date={$end_date}"><i class="fontello-icon-download"></i>{t}{$action_link.text}{/t}</a><!-- {/if} -->
	</h3>
</div>

<div class="row-fluid">
	<div class="choose_list f_r">
		<form class="f_r" action="{$search_action}"  method="post" name="theForm">
			<span>{lang key='orders::statistic.select_date_lable'}</span>
			<input class="start_date f_l w110" name="start_date" type="text" placeholder="{lang key='orders::statistic.start_date'}" value="{$start_date}">
			<span class="f_l">-</span>
			<input class="end_date f_l w110" name="end_date" type="text" placeholder="{lang key='orders::statistic.end_date'}" value="{$end_date}">
			<input class="btn screen-btn" type="submit" value="{lang key='orders::statistic.search'}">
		</form>
	</div>
</div>

<div class="row-fluid">
	<table class="table table-striped" id="smpl_tbl">
		<thead>
			<tr>
				<th>{lang key='orders::statistic.goods_name'}</th>
				<th class="w200">{lang key='orders::statistic.order_sn'}</th>
				<th class="w70">{lang key='orders::statistic.amount'}</th>
				<th class="w120">{lang key='orders::statistic.sell_price'}</th>
				<th class="w110">{lang key='orders::statistic.sell_date'}</th>
			</tr>
		</thead>
		<tbody>
			<!-- {foreach from=$sale_list_data.item key=key item=list} -->
			<tr>
				<td>
					<a href='{RC_Uri::url("goods/admin/preview", "id={$list.goods_id}")}' target="_blank">{$list.goods_name}</a>
				</td>
				<td>{$list.order_sn}</td>
				<td>{$list.goods_num}</td>
				<td>{$list.sales_price}</td>
				<td>{$list.sales_time}</td>
			</tr>
			<!-- {foreachelse} -->
	    	<tr><td class="dataTables_empty" colspan="5">{lang key='system::system.no_records'}</td></tr>
	  		<!-- {/foreach} -->
		</tbody>
	</table>
	<!-- {$sale_list_data.page} -->
</div>
<!-- {/block} -->