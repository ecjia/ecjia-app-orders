<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->
<!-- {block name="footer"} -->
<!-- {/block} -->
<!-- {block name="main_content"} -->
	<!--站外投放JS-->
	<div>
		<h3 class="heading">
			<!-- {if $ur_here}{$ur_here}{/if} -->
			<!-- {if $action_link} -->
				<a class="btn plus_or_reply" id="sticky_a" href='{$action_link.href}'><i class="fontello-icon-forward"></i>{$action_link.text}</a>
			<!-- {/if} -->
			<!-- {if $action_link_download} -->
				<a class="btn plus_or_reply" id="sticky_a" href='{$action_link_download.href}'><i class="fontello-icon-download"></i>{$action_link_download.text}</a>
			<!-- {/if} -->
		</h3>
	</div>
	
	<div class="row-fluid">
		<table class="table table-striped" id="smpl_tbl">
			<thead>
				<tr>
					<th class="w180">{lang key='orders::statistic.adsense_name'}</th>
					<th class="w300">{lang key='orders::statistic.cleck_referer'}</th>
					<th class="w150">{lang key='orders::statistic.click_count'}</th>
					<th class="w100">{lang key='orders::statistic.confirm_order'}</th>
					<th class="w150">{lang key='orders::statistic.gen_order_amount'}</th>
				</tr>
			</thead>
			<tbody>
			<!-- {if $ads_stats} -->
			<!-- {foreach from=$ads_stats item=list} -->
				<tr>
					<td>{$list.ad_name}</td>
					<td>{$list.referer}</td>
					<td>{$list.clicks}</td>
					<td>{$list.order_confirm}</td>
					<td>{$list.order_num}</td>
				</tr>
			<!-- {/foreach} -->
			<!-- {/if} -->
			<!-- {if $goods_stats} -->
			<!-- {foreach from=$goods_stats item=info} -->
				<tr>
				    <td>{$info.ad_name}</td>
				    <td>{$info.referer}</td>
				    <td align="right">{$info.clicks}</td>
				    <td align="right">{$info.order_confirm}</td>
				    <td align="right">{$info.order_num}</td>
			  	</tr>
			<!-- {/foreach} -->
			<!-- {/if} -->
			<!-- {if $ads_stats eq '' AND $goods_stats eq ''} -->
				<tr>
					<td class="dataTables_empty" colspan="5">{lang key='orders::statistic.no_stats_data'}</td>
				</tr>
			<!-- {/if} -->
			</tbody>
		</table>
	</div>
<!-- {/block} -->