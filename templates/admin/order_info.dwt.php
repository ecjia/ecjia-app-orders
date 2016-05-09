<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.order.info();
</script>
<!-- {/block} -->

<!-- {block name="main_content"} -->
<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
		<!-- {if $action_link} -->
		<a href="{$action_link.href}" class="btn plus_or_reply data-pjax" ><i class="fontello-icon-reply"></i>{$action_link.text}</a>
		<!-- {/if} -->
	</h3>
</div>
<!-- #BeginLibraryItem "/library/order_operate.lbi" --><!-- #EndLibraryItem -->
<!-- #BeginLibraryItem "/library/order_refund.lbi" --><!-- #EndLibraryItem -->
<div class="modal hide fade" id="consigneeinfo">
	<div class="modal-header">
		<button class="close" data-dismiss="modal">×</button>
		<h3>{t}购货人信息{/t}</h3>
	</div>
	<div class="modal-body">
		<div class="row-fluid">
			<div class="span12">
				<table class="table table-bordered">
					<tr><td colspan="2"><strong>购货人信息</strong></td></tr>
					<tr><td class="w200">{$lang.email}</td><td>{$user.email}</td></tr>
					<tr><td>{$lang.user_money}</td><td>{$user.user_money}</td></tr>
					<tr><td>{$lang.pay_points}</td><td>{$user.pay_points}</td></tr>
					<tr><td>{$lang.rank_points}</td><td>{$user.rank_points}</td></tr>
					<tr><td>{$lang.rank_name}</td><td>{$user.rank_name}</td></tr>
					<tr><td>{$lang.bonus_count}</td><td>{$user.bonus_count}</td></tr>
				</table>
				<!-- {foreach from=$address_list item=address} -->
				<table class="table table-bordered">
					<tr><td colspan="2"><strong>{$lang.consignee}:{$order.consignee|default:$order.user_name}</strong></td></tr>
					<tr><td class="w200">{$lang.email}</td><td>{$address.email}</td></tr>
					<tr><td>{$lang.address}</td><td>{$address.address}</td></tr>
					<tr><td>{$lang.zipcode}</td><td>{$address.zipcode}</td></tr>
					<tr><td>{$lang.tel}</td><td>{$address.tel}</td></tr>
					<tr><td>{$lang.mobile}</td><td>{$address.mobile}</td></tr>
				</table>
				<!-- {/foreach} -->
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<form name="queryinfo" action='{url path="orders/admin/query_info"}' method="post" >
		<div class="span12 ecjiaf-tac">
			<div class="ecjiaf-fl"><h3>{$lang.label_order_sn}{$order.order_sn}</h3></div>
			<span class="choose_list"><input type="text" name="keywords" class="ecjiaf-fn" placeholder="请输入订单号或者订单id" /><button class="btn ecjiaf-fn" type="submit">{t}搜索{/t}</button></span>
			<div class="f_r">
				{if $prev_id}
				<a class="data-pjax ecjiaf-tdn" href='{url path="orders/admin/info" args="order_id={$prev_id}"}' >
				{/if}
					<button class="btn btn-small" type="button" {if !$prev_id}disabled="disabled"{/if}>{$lang.prev}</button>
				{if $prev_id}
				</a>
				{/if}
				{if $next_id}
				<a class="data-pjax ecjiaf-tdn" href='{url path="orders/admin/info" args="order_id={$next_id}"}'>
				{/if}
					<button class="btn btn-small" type="button" {if !$next_id}disabled="disabled"{/if}>{$lang.next}</button>
				{if $next_id}
				</a>
				{/if}
				<button class="btn btn-small" type="button" onclick="window.open('{url path="orders/admin/info" args="order_id={$order.order_id}&print=1"}')">{$lang.print_order}</button>
			</div>
		</div>
	</form>
</div>
<div class="row-fluid">
	<div class="span12">
		<form action="{$form_action}" method="post" name="orderpostForm" id="listForm" data-url='{url path="orders/admin/operate_post" args="order_id={$order.order_id}"}'  data-pjax-url='{url path="orders/admin/info" args="order_id={$order.order_id}"}' data-list-url='{url path="orders/admin/init"}' data-remove-url="{$remove_action}">
			<div id="accordion2" class="foldable-list form-inline">
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseOne"><strong>{$lang.base_info}</strong></a>
					</div>
					<div class="accordion-body in collapse" id="collapseOne">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
								<tr>
									<td><div align="right"><strong>{$lang.label_order_sn}</strong></div></td>
									<!-- TODO 团购链接赞不知，以后修改测试 -->
									<td>
										{$order.order_sn}
										{if $order.extension_code eq "group_buy"}
										<a href='{url path="groupbuy/admin/edit" args="id={$order.extension_id}"}' target="_blank">{$lang.group_buy}</a>
										{elseif $order.extension_code eq "exchange_goods"}
<!-- 										<a href="exchange_goods.php?act=edit&id={$order.extension_id}">{$lang.exchange_goods}</a> -->
										{/if}
									</td>
									<td><div align="right"><strong>{$lang.label_order_status}</strong></div></td>
									<td>{$order.status}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_user_name}</strong></div></td>
									<td>
										{$order.user_name|default:$lang.anonymous} 
										{if $order.user_id gt 0}
										[ <a class="userInfo cursor_pointer" data-toggle="modal" href="#consigneeinfo" title="{$lang.display_buyer}">{$lang.display_buyer}</a> ]
										[ <a href='{url path="feedback/admin/add" args="order_id={$order.order_id}&user_id={$order.user_id}"}' target="_blank">{$lang.send_message}</a> ]
										{/if}
									</td>
									<td><div align="right"><strong>{$lang.label_order_time}</strong></div></td>
									<td>{$order.formated_add_time}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_payment}</strong></div></td>
									<td>
										{$order.pay_name}
										{if $order.shipping_status neq 1}
										<a class="data-pjax" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=shipping"}'>{$lang.edit}</a>
										{/if}
										&nbsp;&nbsp;&nbsp;
										({$lang.action_note}: <span >{if $order.pay_note}{$order.pay_note}{else}N/A{/if}</span>)
									</td>
									<td><div align="right"><strong>{$lang.label_pay_time}</strong></div></td>
									<td>{$order.pay_time}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_shipping}</strong></div></td>
									<td>
										{if $exist_real_goods}
										{if $order.shipping_id gt 0}
										<span>{$order.shipping_name}</span>
										{else}
										<span>{$lang.require_field}</span>
										{/if}
										<a class="data-pjax" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=shipping"}'>{$lang.edit}</a>
										&nbsp;&nbsp;
										<input type="button" class="btn" onclick="window.open('{url path="orders/admin/info" args="order_id={$order.order_id}&shipping_print=1"}')" value="{$lang.print_shipping}"> 
										{if $order.insure_fee gt 0}{$lang.label_insure_fee}{$order.formated_insure_fee}{/if}
										{/if}
									</td>
									<td><div align="right"><strong>{$lang.label_shipping_time}</strong></div></td>
									<td>{$order.shipping_time}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_invoice_no}</strong></div></td>
									<td>{if $order.shipping_id gt 0 and $order.shipping_status gt 0}<span>{if $order.invoice_no}{$order.invoice_no}{else}N/A{/if}</span>&nbsp;<a href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=shipping"}' class="special data-pjax">{$lang.edit}</a>{/if}</td>
									<td><div align="right"><strong>{$lang.from_order}</strong></div></td>
									<td>{$order.referer}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="accordion-group">
					<div class="accordion-heading accordion-heading-url">
						<div class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseTwo-a">
							<strong>{t}发票信息{/t}</strong>
						</div>
						{if $order.shipping_status neq 1}
							<a class="data-pjax accordion-url" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=other"}'>{$lang.edit}</a>
						{/if}
					</div>
					<div class="accordion-body in collapse" id="collapseTwo-a">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
								<tr>
									<td><div align="right"><strong>{$lang.label_inv_type}</strong></div></td>
									<td colspan="3">{$order.inv_type}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_inv_payee}</strong></div></td>
									<td>{$order.inv_payee}</td>
									<td><div align="right"><strong>{$lang.label_inv_content}</strong></div></td>
									<td>{$order.inv_content}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="accordion-group">
					<div class="accordion-heading accordion-heading-url">
						<div class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseTwo">
							<strong>{$lang.other_info}</strong>
						</div>
						{if $order.shipping_status neq 1}
							<a class="data-pjax accordion-url" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=other"}'>{$lang.edit}</a>
						{/if}
					</div>
					<div class="accordion-body in collapse" id="collapseTwo">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
								<tr>
									<td><div align="right"><strong>{$lang.label_postscript}</strong></div></td>
									<td colspan="3">{$order.postscript}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_how_oos}</strong></div></td>
									<td colspan="3">{$order.how_oos}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_pack}</strong></div></td>
									<td>{$order.pack_name}</td>
									<td><div align="right"><strong>{$lang.label_card}</strong></div></td>
									<td>{$order.card_name}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_card_message}</strong></div></td>
									<td colspan="3">{$order.card_message}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_to_buyer}</strong></div></td>
									<td colspan="3">{$order.to_buyer}</td>
								</tr>
							</tbody>
						</table>
					</div>	
				</div>
				<div class="accordion-group">
					<div class="accordion-heading accordion-heading-url">
						<div class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseThree">
							<strong>{$lang.consignee_info}</strong>
						</div>
						{if $order.shipping_status neq 1}
							<a class="data-pjax accordion-url" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=consignee"}'>{$lang.edit}</a>
						{/if}
					</div>
					<div class="accordion-body in collapse" id="collapseThree">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
								<tr>
									<td><div align="right"><strong>{$lang.label_consignee}</strong></div></td>
									<td>{$order.consignee}</td>
									<td><div align="right"><strong>{$lang.label_email}</strong></div></td>
									<td>{$order.email}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_address}</strong></div></td>
									<td>[{$order.region}]{$order.address}</td>
									<td><div align="right"><strong>{$lang.label_zipcode}</strong></div></td>
									<td>{$order.zipcode}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_tel}</strong></div></td>
									<td>{$order.tel}</td>
									<td><div align="right"><strong>{$lang.label_mobile}</strong></div></td>
									<td>{$order.mobile}</td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_sign_building}</strong></div></td>
									<td>{$order.sign_building|escape}</td>
									<td><div align="right"><strong>{$lang.label_best_time}</strong></div></td>
									<td>{$order.best_time|escape}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="accordion-group">
					<div class="accordion-heading accordion-heading-url">
						<div class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseFour">
							<strong>{$lang.goods_info}</strong>
						</div>
						{if $order.shipping_status neq 1}
							<a class="data-pjax accordion-url" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=goods"}'>{$lang.edit}</a>
						{/if}
					</div>
					<div class="accordion-body in collapse" id="collapseFour">
						<table class="table table-striped table_vam m_b0 order-table-list">
							<thead>
								<tr class="table-list">
									<th class="w80">{t}商品缩略图{/t}</th>
									<th class="w80">{t}仓库名称{/t}</th>
									<th>{$lang.goods_name_brand}</th>
									<th class="w80">{$lang.goods_sn}</th>
									<th class="w50">{$lang.product_sn}</th>
									<th class="w100">{$lang.goods_price}</th>
									<th class="w30">{$lang.goods_number}</th>
									<th class="w100">{$lang.goods_attr}</th>
									<th class="w50">{$lang.storage}</th>
									<th class="w100">{$lang.subtotal}</th>
								</tr>
							</thead>
							<tbody>
								<!-- {foreach from=$goods_list item=goods} -->
								<tr class="table-list">
									<td><img src="{$goods.goods_img}" width='50'/></td>
									<td>{$goods.region_name}</td>
									<td>
										{if $goods.goods_id gt 0 and $goods.extension_code neq 'package_buy'}
										<a href='{url path="goods/admin/preview" args="id={$goods.goods_id}"}' target="_blank">{$goods.goods_name} {if $goods.brand_name}[ {$goods.brand_name} ]{/if}{if $goods.is_gift}{if $goods.goods_price gt 0}{$lang.remark_favourable}{else}{$lang.remark_gift}{/if}{/if}{if $goods.parent_id gt 0}{$lang.remark_fittings}{/if}</a>
										{elseif $goods.goods_id gt 0 and $goods.extension_code eq 'package_buy'}
										<!-- <a href="javascript:void(0)" onclick="setSuitShow({$goods.goods_id})">{$goods.goods_name}<span style="color:#FF0000;">{$lang.remark_package}</span></a> -->
										<!-- <div style="display:none">  -->
										<!-- {foreach from=$goods.package_goods_list item=package_goods_list} -->
										<!-- <a href='{url path="goods/admin/preview" args="id={$package_goods_list.goods_id}"}' target="_blank">{$package_goods_list.goods_name}</a><br /> -->
										<!-- {/foreach} -->
										<!-- </div> -->
										{/if}
									</td>
									<td>{$goods.goods_sn}</td>
									<td>{$goods.product_sn}</td>
									<td><div>{$goods.formated_goods_price}</div></td>
									<td><div>{$goods.goods_number}
									</div></td>
									<td>{$goods.goods_attr|nl2br}</td>
									<td><div>{$goods.storage}</div></td>
									<td><div>{$goods.formated_subtotal}</div></td>
								</tr>
								<!-- {foreachelse} -->
								<tr>
									<td class="no-records" colspan="9">{t}该订单暂无商品{/t}</td>
								</tr>
								<!-- {/foreach} -->
								<tr>
									<td colspan="5">{if $order.total_weight}<div align="right"><strong>{$lang.label_total_weight}
									</strong></div>{/if}</td>
									<td colspan="3">{if $order.total_weight}<div align="right">{$order.total_weight}
									</div>{/if}</td>
									<td><div align="right"><strong>{$lang.label_total}</strong></div></td>
									<td><div align="right">{$order.formated_goods_amount}</div></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="accordion-group">
					<div class="accordion-heading accordion-heading-url">
						<div class="accordion-toggle acc-in" data-toggle="collapse"  data-target="#collapseFive">
							<strong>{$lang.fee_info}</strong>
						</div>
						{if $order.shipping_status neq 1}
							<a class="data-pjax accordion-url" href='{url path="orders/admin/edit" args="order_id={$order.order_id}&step=money"}'>{$lang.edit}</a>
						{/if}
					</div>
					<div class="accordion-body in collapse" id="collapseFive">
						<table class="table m_b0">
							<tr>
								<td>
									<div align="right">
										{$lang.label_goods_amount}<strong>{$order.formated_goods_amount}</strong>
										- {$lang.label_discount}<strong>{$order.formated_discount}</strong>     
										+ {$lang.label_tax}<strong>{$order.formated_tax}</strong>
										+ {$lang.label_shipping_fee}<strong>{$order.formated_shipping_fee}</strong>
										+ {$lang.label_insure_fee}<strong>{$order.formated_insure_fee}</strong>
										+ {$lang.label_pay_fee}<strong>{$order.formated_pay_fee}</strong>
										+ {$lang.label_pack_fee}<strong>{$order.formated_pack_fee}</strong>
										+ {$lang.label_card_fee}<strong>{$order.formated_card_fee}</strong>
									</div>
								</td>
							</tr>
							<tr>
								<td><div align="right"> = {$lang.label_order_amount}<strong>{$order.formated_total_fee}</strong></div></td>
							</tr>
							<tr>
								<td>
									<div align="right">
										- {$lang.label_money_paid}<strong>{$order.formated_money_paid}</strong> 
										- {$lang.label_surplus} <strong>{$order.formated_surplus}</strong>
										- {$lang.label_integral} <strong>{$order.formated_integral_money}</strong>
										- {$lang.label_bonus} <strong>{$order.formated_bonus}</strong>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div align="right"> 
										= {if $order.order_amount >= 0}
										{$lang.label_money_dues}<strong>{$order.formated_order_amount}</strong>
										{else}
										{$lang.label_money_refund}<strong>{$order.formated_money_refund}</strong>
										<input class="refund_click btn" type="button" data-href="{$refund_url}" value="{$lang.refund}">
										{/if}
										{if $order.extension_code eq "group_buy"}<br />{$lang.notice_gb_order_amount}{/if}
									</div>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseSix"><strong>{t}操作记录{/t}</strong></a>
					</div>
					<div class="accordion-body in collapse" id="collapseSix">
						<table class="table table-striped m_b0">
							<thead>
								<tr>
									<td><strong>{$lang.action_user}</strong></td>
									<td><strong>{$lang.action_time}</strong></td>
									<td><strong>{$lang.order_status}</strong></td>
									<td><strong>{$lang.pay_status}</strong></td>
									<td><strong>{$lang.shipping_status}</strong></td>
									<td><strong>{$lang.action_note}</strong></td>
								</tr>
							</thead>
							<tbody>
								<!-- {foreach from=$action_list item=action} -->
								<tr>
									<td><div>{$action.action_user}</div></td>
									<td><div>{$action.action_time}</div></td>
									<td><div>{$action.order_status}</div></td>
									<td><div>{$action.pay_status}</div></td>
									<td><div>{$action.shipping_status}</div></td>
									<td>{$action.action_note|nl2br}</td>
								</tr>
								<!-- {foreachelse} -->
								<tr>
									<td class="no-records" colspan="6">{t}该订单暂无操作记录{/t}</td>
								</tr>
								<!-- {/foreach} -->
							</tbody>
						</table>
					</div>
				</div>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle acc-in" data-toggle="collapse" data-target="#collapseSeven"><strong>{t}订单操作{/t}</strong></a>
					</div>
					<div class="accordion-body in collapse" id="collapseSeven">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
								<tr> 
									<td width="15%"><div align="right"><strong>{$lang.label_action_note}</strong></div></td> 
									<td colspan="3"><textarea name="action_note" class="span12 action_note" cols="60" rows="3"></textarea></td>
								</tr>
								<tr>
									<td><div align="right"><strong>{$lang.label_operable_act}</strong></div></td>
									<td colspan="3">
										<input type='hidden' class="operate_note" data-url='{url path="orders/admin/operate_note"}'>
										{if $operable_list.confirm}
										<button class="btn operatesubmit" type="submit" name="confirm">{$lang.op_confirm}</button>
										{/if} {if $operable_list.pay}
										<button class="btn operatesubmit" type="submit" name="pay">{$lang.op_pay}</button>
										{/if} {if $operable_list.unpay}
										<button class="btn operatesubmit" type="submit" name="unpay">{$lang.op_unpay}</button>
										{/if} {if $operable_list.prepare}
										<button class="btn operatesubmit" type="submit" name="prepare">{$lang.op_prepare}</button>
										{/if} {if $operable_list.split}
										<button class="btn operatesubmit" type="submit" name="ship">{$lang.op_split}</button>
										{/if} {if $operable_list.unship}
										<button class="btn operatesubmit" type="submit" name="unship">{$lang.op_unship}</button>
										{/if} {if $operable_list.receive}
										<button class="btn operatesubmit" type="submit" name="receive">{$lang.op_receive}</button>
										{/if} {if $operable_list.cancel}
										<button class="btn operatesubmit" type="submit" name="cancel">{$lang.op_cancel}</button>
										{/if} {if $operable_list.invalid}
										<button class="btn operatesubmit" type="submit" name="invalid">{$lang.op_invalid}</button>
										{/if} {if $operable_list.return}
										<button class="btn operatesubmit" type="submit" name="return">{$lang.op_return}</button>
										{/if} {if $operable_list.to_delivery}
										<button class="btn operatesubmit" type="submit" name="to_delivery">{$lang.op_to_delivery}</button>
										<input name="order_sn" type="hidden" value="{$order.order_sn}" />
										{/if} 
										<button class="btn operatesubmit" type="submit" name="after_service">{$lang.op_after_service}</button>
										{if $operable_list.remove}
										<button class="btn operatesubmit" type="submit" name="remove">{$lang.remove}</button>
										{/if}
										{if $order.extension_code eq "group_buy"}{$lang.notice_gb_ship}{/if}
										{if $agency_list}
										<input name="assign" type="submit" value="{$lang.op_assign}" class="btn operatesubmit" onclick="return assignTo(document.forms['theForm'].elements['agency_id'].value)" />
										<select name="agency_id"><option value="0">{$lang.select_please}</option>
											<!-- {foreach from=$agency_list item=agency} -->
											<option value="{$agency.agency_id}" {if $agency.agency_id eq $order.agency_id}selected{/if}>{$agency.agency_name}</option>
											<!-- {/foreach} -->
										</select>
										{/if}
										<input name="order_id" class="order_id" type="hidden" value="{$order.order_id}">								        
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<!-- {/block} -->